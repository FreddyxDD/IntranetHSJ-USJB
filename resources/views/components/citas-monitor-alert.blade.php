@auth
    <div id="citas-global-alert" class="pointer-events-none fixed inset-x-0 top-3 z-50 hidden px-3" role="alert">
        <div class="pointer-events-auto mx-auto max-w-5xl rounded-xl border border-sky-200 bg-white p-4 text-sm text-sky-950 shadow-xl shadow-sky-950/10 dark:border-sky-800 dark:bg-zinc-950 dark:text-sky-100">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex min-w-0 gap-3">
                    <span class="mt-0.5 grid size-9 shrink-0 place-items-center rounded-lg bg-sky-100 text-sky-700 ring-1 ring-sky-200 dark:bg-sky-950 dark:text-sky-200 dark:ring-sky-800">
                        <flux:icon icon="bell-alert" class="size-5" />
                    </span>
                    <div class="min-w-0">
                        <div class="font-semibold">Hay cambios nuevos en la agenda de citas</div>
                        <div id="citas-global-alert-message" class="mt-1 text-sky-800/80 dark:text-sky-100/80"></div>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <label class="inline-flex items-center gap-2 rounded-md px-3 py-1.5 text-xs font-medium text-sky-800 ring-1 ring-sky-200 dark:text-sky-100 dark:ring-sky-800">
                        <input id="citas-global-alert-sound" type="checkbox" class="size-4 rounded border-sky-300">
                        Sonido
                    </label>
                    <a id="citas-global-alert-link" href="{{ route('citas.index', ['recientes' => 1]) }}" class="inline-flex items-center gap-1.5 rounded-md bg-sky-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-sky-800">
                        <flux:icon icon="arrow-right" class="size-3.5" />
                        Revisar
                    </a>
                    <button type="button" id="citas-global-alert-dismiss" class="inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-xs font-medium text-sky-800 hover:bg-sky-50 dark:text-sky-100 dark:hover:bg-sky-950">
                        <flux:icon icon="x-mark" class="size-3.5" />
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const alertElement = document.getElementById('citas-global-alert');
            const messageElement = document.getElementById('citas-global-alert-message');
            const dismissButton = document.getElementById('citas-global-alert-dismiss');
            const soundToggle = document.getElementById('citas-global-alert-sound');
            const statusUrl = @json(route('citas.status'));
            const citasIndexUrl = @json(route('citas.index'));
            const recentUrl = @json(route('citas.index', ['recientes' => 1]));
            const intervalSeconds = 180;
            const soundPreferenceKey = 'citas.update.sound.enabled';
            const snapshotKey = 'citas.global.snapshot';
            const dismissedKey = 'citas.global.dismissed.signature';
            const alertLink = document.getElementById('citas-global-alert-link');
            let audioContext = null;

            const todayKey = () => new Date().toISOString().slice(0, 10);

            const soundEnabled = () => localStorage.getItem(soundPreferenceKey) === '1';

            const setStoredSnapshot = (payload) => {
                localStorage.setItem(snapshotKey, JSON.stringify({
                    date: todayKey(),
                    total: payload.total,
                    recent_count: payload.recent_count,
                    additional_count: payload.additional_count,
                    signature: payload.signature,
                    monitor_items: payload.monitor_items || [],
                }));
            };

            const storedSnapshot = () => {
                try {
                    const snapshot = JSON.parse(localStorage.getItem(snapshotKey) || 'null');

                    if (!snapshot || snapshot.date !== todayKey()) {
                        return null;
                    }

                    return snapshot;
                } catch (error) {
                    return null;
                }
            };

            const playAlertSound = () => {
                if (!soundEnabled()) {
                    return;
                }

                try {
                    audioContext = audioContext || new (window.AudioContext || window.webkitAudioContext)();

                    if (audioContext.state === 'suspended') {
                        audioContext.resume();
                    }

                    const startAt = audioContext.currentTime;

                    const ring = (offset, frequency, duration, volume) => {
                        const oscillator = audioContext.createOscillator();
                        const gain = audioContext.createGain();
                        const bell = audioContext.createBiquadFilter();
                        const when = startAt + offset;

                        oscillator.type = 'triangle';
                        oscillator.frequency.setValueAtTime(frequency, when);
                        oscillator.frequency.exponentialRampToValueAtTime(frequency * 0.72, when + duration);

                        bell.type = 'bandpass';
                        bell.frequency.setValueAtTime(frequency, when);
                        bell.Q.setValueAtTime(8, when);

                        gain.gain.setValueAtTime(0.0001, when);
                        gain.gain.exponentialRampToValueAtTime(volume, when + 0.015);
                        gain.gain.exponentialRampToValueAtTime(0.0001, when + duration);

                        oscillator.connect(bell);
                        bell.connect(gain);
                        gain.connect(audioContext.destination);
                        oscillator.start(when);
                        oscillator.stop(when + duration + 0.03);
                    };

                    ring(0, 1046.5, 0.42, 0.28);
                    ring(0.18, 1568, 0.46, 0.22);
                } catch (error) {
                    console.debug('No se pudo reproducir sonido de alerta.', error);
                }
            };

            const itemMap = (items) => {
                const map = new Map();

                (items || []).forEach((item) => {
                    map.set(String(item.id_cita), item);
                });

                return map;
            };

            const cleanScope = (value) => {
                const text = String(value || '').trim();

                return text || 'Sin especialidad';
            };

            const shortItem = (prefix, item) => {
                return {
                    text: `${prefix}: ${cleanScope(item?.summary_scope || item?.service)}`,
                    cita_id: Number(item?.id_cita || 0),
                };
            };

            const groupedDetails = (details) => {
                const groups = new Map();

                details.forEach((detail) => {
                    const key = detail.text;
                    const group = groups.get(key) || {
                        text: detail.text,
                        count: 0,
                        cita_id: detail.cita_id,
                    };

                    group.count++;

                    if (!group.cita_id && detail.cita_id) {
                        group.cita_id = detail.cita_id;
                    }

                    groups.set(key, group);
                });

                return Array.from(groups.values());
            };

            const renderMessage = (checkedAt, details) => {
                const grouped = groupedDetails(details);
                messageElement.innerHTML = '';

                const header = document.createElement('div');
                header.textContent = `Revision ${checkedAt}:`;
                messageElement.appendChild(header);

                if (grouped.length === 1) {
                    const item = grouped[0];
                    const line = document.createElement('div');
                    line.className = 'mt-0.5 font-medium';
                    line.textContent = item.count > 1 ? `${item.text} (${item.count})` : item.text;
                    messageElement.appendChild(line);

                    return grouped;
                }

                const list = document.createElement('ul');
                list.className = 'mt-1 grid gap-1 sm:grid-cols-2';

                grouped.slice(0, 6).forEach((item) => {
                    const li = document.createElement('li');
                    li.className = 'rounded-md bg-sky-50 px-2 py-1 text-xs font-medium ring-1 ring-sky-100 dark:bg-sky-950 dark:ring-sky-800';
                    li.textContent = item.count > 1 ? `${item.text} (${item.count})` : item.text;
                    list.appendChild(li);
                });

                if (grouped.length > 6) {
                    const li = document.createElement('li');
                    li.className = 'rounded-md bg-sky-50 px-2 py-1 text-xs font-medium ring-1 ring-sky-100 dark:bg-sky-950 dark:ring-sky-800';
                    li.textContent = `+${grouped.length - 6} cambios mas`;
                    list.appendChild(li);
                }

                messageElement.appendChild(list);

                return grouped;
            };

            const buildDetails = (previous, current) => {
                const details = [];
                const previousItems = itemMap(previous.monitor_items);
                const currentItems = itemMap(current.monitor_items);

                currentItems.forEach((item, id) => {
                    if (id === '0') {
                        details.push(shortItem(item.summary_label || 'Cita modificada', item));

                        return;
                    }

                    if (!previousItems.has(id)) {
                        details.push(shortItem(Number(item.adicional || 0) === 1 ? 'Cita Adicional' : 'Cita Nueva', item));
                    }
                });

                previousItems.forEach((item, id) => {
                    if (id !== '0' && !currentItems.has(id)) {
                        details.push(shortItem('Cita anulada o reemplazada', item));
                    }
                });

                currentItems.forEach((item, id) => {
                    const previousItem = previousItems.get(id);

                    if (id !== '0' && previousItem && previousItem.item_hash !== item.item_hash) {
                        details.push(shortItem('Cita modificada', item));
                    }
                });

                if (details.length > 0) {
                    return details;
                }

                if (current.total !== previous.total) {
                    details.push({ text: `Total visible ${previous.total} -> ${current.total}`, cita_id: 0 });
                }

                if (current.additional_count !== previous.additional_count) {
                    details.push({ text: `Citas adicionales ${previous.additional_count} -> ${current.additional_count}`, cita_id: 0 });
                }

                if (current.recent_count !== previous.recent_count) {
                    details.push({ text: `Citas recientes ${previous.recent_count} -> ${current.recent_count}`, cita_id: 0 });
                }

                if (current.signature !== previous.signature && details.length === 0) {
                    details.push({ text: 'Cita modificada: revisar paciente, financiamiento, horario, medico o servicio', cita_id: 0 });
                }

                if (current.simulation_label) {
                    details.unshift({ text: `Simulacion: ${current.simulation_label}`, cita_id: 0 });
                }

                return details;
            };

            const detailLink = (current, details) => {
                const firstCita = details.find((detail) => Number(detail.cita_id || 0) > 0);
                const params = new URLSearchParams();

                if (current.fecha) {
                    params.set('fecha', current.fecha);
                }

                if (firstCita) {
                    params.set('q', String(firstCita.cita_id));

                    return `${citasIndexUrl}?${params.toString()}`;
                }

                return recentUrl;
            };

            const checkScheduleChanges = async () => {
                try {
                    const response = await fetch(statusUrl, { headers: { 'Accept': 'application/json' } });

                    if (!response.ok) {
                        return;
                    }

                    const data = await response.json();
                    const previous = storedSnapshot();

                    if (!previous) {
                        setStoredSnapshot(data);

                        return;
                    }

                    if (data.signature === previous.signature) {
                        return;
                    }

                    const signature = data.signature || `${data.total}-${data.recent_count}-${data.additional_count}`;

                    if (localStorage.getItem(dismissedKey) === signature) {
                        return;
                    }

                    const details = buildDetails(previous, data);
                    const grouped = renderMessage(data.checked_at, details);
                    alertElement.dataset.signature = signature;
                    alertLink.href = detailLink(data, grouped);
                    alertElement.classList.remove('hidden');
                    setStoredSnapshot(data);
                    playAlertSound();
                } catch (error) {
                    console.debug('No se pudo revisar cambios de agenda.', error);
                }
            };

            if (soundToggle) {
                soundToggle.checked = soundEnabled();

                soundToggle.addEventListener('change', () => {
                    localStorage.setItem(soundPreferenceKey, soundToggle.checked ? '1' : '0');

                    if (soundToggle.checked) {
                        playAlertSound();
                    }
                });
            }

            dismissButton?.addEventListener('click', () => {
                if (alertElement?.dataset.signature) {
                    localStorage.setItem(dismissedKey, alertElement.dataset.signature);
                }

                alertElement?.classList.add('hidden');
            });

            window.setTimeout(checkScheduleChanges, 3000);
            window.setInterval(checkScheduleChanges, intervalSeconds * 1000);
        });
    </script>
@endauth
