(() => {
    'use strict';

    const config = window.EGRESOS_CONFIG;
    const state = {
        page: 1, query: '', dateFrom: '', dateTo: '',
        selectedCertificate: null, editingRecord: null, auditPage: 1,
        activeImport: null,
    };
    const $ = (selector) => document.querySelector(selector);
    const element = (tag, classes, text) => {
        const node = document.createElement(tag);
        if (classes) node.className = classes;
        if (text !== undefined) node.textContent = text;
        return node;
    };
    const formatDate = (value) => value
        ? new Intl.DateTimeFormat('es-PE', { timeZone: 'UTC' }).format(new Date(`${String(value).slice(0, 10)}T00:00:00Z`))
        : '—';
    const formatDateTime = (value) => value
        ? new Intl.DateTimeFormat('es-PE', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value))
        : '—';
    const request = async (url, options = {}) => {
        const headers = {
            Accept: 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            ...options.headers,
        };
        if (!(options.body instanceof FormData)) headers['Content-Type'] = 'application/json';
        const response = await fetch(url, { headers, ...options });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok) {
            const validation = payload.errors ? Object.values(payload.errors).flat()[0] : null;
            throw new Error(validation || payload.message || 'No fue posible completar la operación.');
        }
        return payload;
    };
    const openOverlay = (selector) => {
        if (window.HSOverlay) window.HSOverlay.open(selector);
        else $(selector)?.classList.remove('hidden');
    };
    const closeOverlay = (selector) => {
        if (window.HSOverlay) window.HSOverlay.close(selector);
        else $(selector)?.classList.add('hidden');
    };

    async function loadDashboard() {
        try {
            const { data } = await request(config.dashboardUrl);
            Object.entries(data).forEach(([key, value]) => {
                const target = document.querySelector(`[data-metric="${key}"]`);
                if (target) target.textContent = Number(value).toLocaleString('es-PE');
            });
        } catch {
            document.querySelectorAll('[data-metric]').forEach((node) => { node.textContent = 'N/D'; });
        }
    }

    function recordRow(item) {
        const row = element('tr', 'cursor-pointer transition hover:bg-blue-50/70 focus-within:bg-blue-50/70');
        row.tabIndex = 0;
        row.setAttribute('role', 'button');
        row.setAttribute('aria-label', `Ver línea de tiempo de ${item.paciente || 'paciente'}`);
        const identity = element('td', 'whitespace-nowrap px-4 py-3');
        identity.append(
            element('div', 'font-bold text-blue-950', `HC ${item.numhc || '—'}`),
            element('div', 'text-xs text-slate-500', item.doc_iden || 'Sin documento')
        );
        const diagnosis = element('td', 'min-w-64 px-4 py-3');
        const main = item.diagnosticos?.[0];
        diagnosis.append(
            element('div', 'font-semibold', main?.codigo || '—'),
            element('div', 'line-clamp-2 text-xs text-slate-500', main?.descripcion || 'Sin diagnóstico')
        );
        const actions = element('td', 'whitespace-nowrap px-4 py-3 text-right');
        const button = element('button', 'rounded-lg bg-blue-50 px-3 py-2 text-xs font-bold text-blue-700 hover:bg-blue-100', 'Línea de tiempo');
        button.addEventListener('click', (event) => {
            event.stopPropagation();
            showTimeline(item.id);
        });
        actions.append(button);
        row.append(
            identity,
            element('td', 'min-w-56 px-4 py-3 font-semibold', item.paciente || 'Sin nombre'),
            element('td', 'whitespace-nowrap px-4 py-3 text-slate-600', `${formatDate(item.fecing)} / ${formatDate(item.fecegr)}`),
            element('td', 'whitespace-nowrap px-4 py-3', item.ups || '—'),
            diagnosis,
            actions
        );
        row.addEventListener('click', () => showTimeline(item.id));
        row.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                showTimeline(item.id);
            }
        });
        return row;
    }

    async function loadRecords(page = 1) {
        const body = $('#records-body');
        const status = $('#records-status');
        body.replaceChildren();
        status.className = 'mt-3 text-sm text-slate-500';
        status.textContent = 'Consultando los últimos episodios incorporados…';
        try {
            const url = new URL(config.recordsUrl, window.location.origin);
            url.searchParams.set('page', page);
            url.searchParams.set('per_page', 20);
            if (state.query) url.searchParams.set('q', state.query);
            if (state.dateFrom) url.searchParams.set('date_from', state.dateFrom);
            if (state.dateTo) url.searchParams.set('date_to', state.dateTo);
            const response = await request(url);
            response.data.forEach((item) => body.append(recordRow(item)));
            state.page = response.meta.current_page;
            status.textContent = `${response.meta.total.toLocaleString('es-PE')} registro(s) encontrado(s).`;
            if (!response.data.length) {
                const row = element('tr');
                const cell = element('td', 'px-4 py-12 text-center text-slate-500', 'No se encontraron egresos con esos criterios.');
                cell.colSpan = 6;
                row.append(cell);
                body.append(row);
            }
            renderPagination(response.meta);
        } catch (error) {
            status.textContent = error.message;
            status.className = 'mt-3 text-sm font-semibold text-rose-700';
        }
    }

    function renderPagination(meta) {
        const container = $('#records-pagination');
        container.replaceChildren(element('span', 'text-slate-500', `Página ${meta.current_page} de ${meta.last_page}`));
        const buttons = element('div', 'flex gap-2');
        [
            ['Anterior', meta.current_page - 1, meta.current_page <= 1],
            ['Siguiente', meta.current_page + 1, meta.current_page >= meta.last_page],
        ].forEach(([label, page, disabled]) => {
            const button = element('button', 'rounded-lg border border-slate-300 px-3 py-2 font-semibold disabled:opacity-40', label);
            button.disabled = disabled;
            button.addEventListener('click', () => loadRecords(page));
            buttons.append(button);
        });
        container.append(buttons);
    }

    async function showTimeline(id, page = 1, append = false) {
        const content = $('#timeline-content');
        if (!append) {
            content.replaceChildren(element('div', 'rounded-xl bg-slate-50 p-6 text-center text-slate-500', 'Cargando línea de tiempo…'));
            openOverlay('#timeline-modal');
        }
        try {
            const response = await request(`${config.recordsUrl}/${id}/timeline?page=${page}&per_page=8`);
            const { patient, episodes, meta } = response.data;
            let timelineList = $('#patient-timeline-list');
            if (!append) {
                const patientHeader = element('section', 'rounded-2xl bg-gradient-to-r from-blue-950 to-blue-700 p-5 text-white');
                patientHeader.append(
                    element('div', 'text-xs font-bold uppercase tracking-wider text-blue-200', `${meta.total} episodio(s) registrado(s)`),
                    element('h3', 'mt-1 text-xl font-black', patient.paciente || 'Paciente sin nombre'),
                    element('p', 'mt-2 text-sm text-blue-100', `HC ${patient.numhc || '—'} · Documento ${patient.documento || '—'}`)
                );
                const explanation = element('div', 'mt-4 rounded-xl border border-cyan-100 bg-cyan-50 p-4 text-sm text-cyan-900');
                explanation.textContent = 'Los últimos episodios aparecen primero. El historial se carga en bloques de 8 únicamente cuando se solicita.';
                timelineList = element('ol', 'relative mt-6 ml-3 space-y-5 border-l-2 border-blue-100 pl-7');
                timelineList.id = 'patient-timeline-list';
                content.replaceChildren(patientHeader, explanation, timelineList);
            }

            episodes.forEach((episode) => {
                const card = element('li', `relative rounded-2xl border p-4 shadow-sm ${episode.is_selected ? 'border-blue-400 bg-blue-50/60 ring-2 ring-blue-100' : 'border-slate-200 bg-white'}`);
                const dot = element('span', `absolute -left-[38px] top-5 grid size-5 place-items-center rounded-full ring-4 ring-white ${episode.is_latest ? 'bg-emerald-500' : 'bg-blue-600'}`);
                const header = element('div', 'flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between');
                const title = element('div');
                title.append(
                    element('div', `font-black ${episode.is_latest ? 'text-emerald-800' : 'text-blue-950'}`, episode.is_latest ? 'Ingreso más reciente' : `Episodio de hospitalización N.° ${episode.episode_number}`),
                    element('div', 'mt-1 text-sm font-semibold text-slate-700', `${formatDate(episode.fecing)} → ${formatDate(episode.fecegr)}`),
                    element('div', 'mt-1 text-xs text-slate-500', `UPS ${episode.ups || '—'} · Condición ${episode.condicion || '—'} · Financiamiento ${episode.financia || '—'}`)
                );
                const badges = element('div', 'flex flex-wrap gap-2');
                if (episode.is_selected) badges.append(element('span', 'rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-800', 'Episodio seleccionado'));
                badges.append(element('span', 'rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600', episode.source_system || 'Sin fuente'));
                header.append(title, badges);
                card.append(dot, header);

                const diagnoses = element('div', 'mt-4 grid gap-2 sm:grid-cols-2');
                (episode.diagnosticos || []).forEach((diagnosis) => {
                    diagnoses.append(element('div', 'rounded-xl bg-slate-50 px-3 py-2 text-xs text-slate-700', `${diagnosis.codigo} — ${diagnosis.descripcion}`));
                });
                if (!(episode.diagnosticos || []).length) diagnoses.append(element('div', 'text-xs text-slate-400', 'Sin diagnósticos registrados.'));
                card.append(diagnoses);

                const actions = element('div', 'mt-4 flex flex-wrap justify-end gap-2 border-t border-slate-100 pt-3');
                if (config.abilities.createCertificates) {
                    const certificate = element('button', 'rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white hover:bg-emerald-700', 'Generar constancia de este episodio');
                    certificate.addEventListener('click', () => createCertificate(episode, certificate));
                    actions.append(certificate);
                }
                if (config.abilities.updateRecords) {
                    const edit = element('button', 'rounded-xl border border-amber-300 px-4 py-2 text-xs font-bold text-amber-700 hover:bg-amber-50', 'Corregir episodio');
                    edit.addEventListener('click', () => openRecordForm(episode));
                    actions.append(edit);
                }
                card.append(actions);
                timelineList.append(card);
            });

            $('#timeline-load-more')?.remove();
            if (meta.has_more) {
                const loadMore = element('button', 'mt-5 w-full rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm font-bold text-blue-700 hover:bg-blue-100', `Cargar episodios anteriores (${meta.total - (meta.current_page * meta.per_page)} restantes)`);
                loadMore.id = 'timeline-load-more';
                loadMore.addEventListener('click', () => {
                    loadMore.disabled = true;
                    showTimeline(id, meta.current_page + 1, true);
                });
                content.append(loadMore);
            }
            if (!episodes.length && !append) {
                timelineList.append(element('li', 'text-sm text-slate-500', 'No se encontraron episodios para este paciente.'));
            }
        } catch (error) {
            if (!append) {
                content.replaceChildren(element('div', 'rounded-xl bg-rose-50 p-5 font-semibold text-rose-700', error.message));
            } else {
                const errorBox = element('div', 'mt-4 rounded-xl bg-rose-50 p-4 text-sm font-semibold text-rose-700', error.message);
                content.append(errorBox);
            }
        }
    }

    function openRecordForm(item = null) {
        const form = $('#record-form');
        state.editingRecord = item;
        form.reset();
        $('#record-modal-title').textContent = item ? 'Corregir egreso' : 'Registrar egreso excepcional';
        $('#record-status').textContent = '';
        if (item) {
            ['numhc', 'doc_iden', 'doc_tipo_id', 'nomb', 'apell', 'sexo', 'edad', 'fecing', 'fecegr', 'ups', 'condicion', 'financia', 'coddiag1', 'coddiag2', 'coddiag3', 'coddiag4'].forEach((name) => {
                const field = form.elements[name];
                if (!field) return;
                const value = item[name] ? String(item[name]) : '';
                field.value = name.startsWith('fec') ? value.slice(0, 10) : value;
            });
        }
        closeOverlay('#timeline-modal');
        openOverlay('#record-modal');
    }

    async function saveRecord(event) {
        event.preventDefault();
        const form = event.currentTarget;
        const button = form.querySelector('button[type="submit"]');
        const payload = Object.fromEntries(new FormData(form).entries());
        const url = state.editingRecord ? `${config.recordsUrl}/${state.editingRecord.id}` : config.recordsUrl;
        button.disabled = true;
        $('#record-status').textContent = 'Validando y guardando…';
        try {
            const response = await request(url, {
                method: state.editingRecord ? 'PUT' : 'POST',
                body: JSON.stringify(payload),
            });
            closeOverlay('#record-modal');
            await Promise.all([loadRecords(state.page), loadDashboard()]);
            window.alert(response.message);
        } catch (error) {
            $('#record-status').textContent = error.message;
        } finally {
            button.disabled = false;
        }
    }

    async function lookupPatient() {
        const form = $('#record-form');
        const query = form.elements.numhc.value.trim() || form.elements.doc_iden.value.trim();
        const status = $('#patient-search-status');
        if (query.length < 3) {
            status.textContent = 'Ingrese una HC o documento válido.';
            return;
        }
        status.textContent = 'Consultando…';
        try {
            const response = await request(`${config.patientSearchUrl}?q=${encodeURIComponent(query)}`);
            const patient = response.data[0];
            if (!patient) {
                status.textContent = 'Paciente no encontrado en la copia local.';
                return;
            }
            form.elements.numhc.value = patient.historia_clinica || '';
            form.elements.doc_iden.value = patient.documento || '';
            form.elements.doc_tipo_id.value = patient.tipo_documento_id || '';
            form.elements.nomb.value = patient.nombres || '';
            form.elements.apell.value = patient.apellidos || '';
            status.textContent = `Datos recuperados de ${patient.source}.`;
        } catch (error) {
            status.textContent = error.message;
        }
    }

    async function createCertificate(episode, button) {
        if (!episode) return;
        button.disabled = true;
        button.textContent = 'Generando…';
        try {
            const result = await request(config.certificateUrl, {
                method: 'POST',
                body: JSON.stringify({ egreso_id: episode.id }),
            });
            window.open(result.print_url, '_blank', 'noopener');
            await loadDashboard();
        } catch (error) {
            window.alert(error.message);
        } finally {
            button.disabled = false;
            button.textContent = 'Generar constancia de este episodio';
        }
    }

    async function loadImports() {
        const list = $('#imports-list');
        if (!list) return;
        list.replaceChildren(element('p', 'text-sm text-slate-500', 'Cargando…'));
        try {
            const response = await request(config.importsUrl);
            list.replaceChildren();
            response.data.forEach((item) => {
                const card = element('article', 'rounded-xl border border-slate-200 p-3');
                const actions = element('div', 'mt-3 flex justify-end');
                const review = element('button', 'rounded-lg border border-blue-200 px-3 py-2 text-xs font-bold text-blue-700 hover:bg-blue-50', item.estado === 'pending' ? 'Revisar y confirmar' : 'Ver resultado');
                review.addEventListener('click', () => showImportAnalysis(item));
                actions.append(review);
                card.append(
                    element('div', 'truncate text-sm font-bold text-blue-950', item.archivo),
                    element('div', 'mt-1 text-xs text-slate-500', importSummaryText(item)),
                    element('div', `mt-1 text-xs font-semibold ${item.estado === 'completed' ? 'text-emerald-700' : item.estado === 'pending' ? 'text-amber-700' : 'text-rose-700'}`, item.estado),
                    actions
                );
                list.append(card);
            });
            if (!response.data.length) list.append(element('p', 'text-sm text-slate-500', 'Todavía no hay importaciones.'));
        } catch (error) {
            list.replaceChildren(element('p', 'text-sm font-semibold text-rose-700', error.message));
        }
    }

    function importSummaryText(item) {
        const summary = item.detalle?.resumen_final || item.detalle?.resumen;
        if (!summary) return `${item.insertados} insertados · ${item.omitidos} omitidos · ${item.errores} observados`;
        return `${summary.nuevo || 0} nuevos · ${summary.reingreso || 0} reingresos · ${summary.duplicado || 0} duplicados · ${(summary.observado || 0) + (summary.error || 0)} por revisar`;
    }

    const importStatusMeta = {
        nuevo: ['Nuevo episodio', 'bg-blue-50 text-blue-800 border-blue-200'],
        reingreso: ['Reingreso', 'bg-cyan-50 text-cyan-800 border-cyan-200'],
        duplicado: ['Duplicado', 'bg-slate-100 text-slate-700 border-slate-200'],
        observado: ['Requiere revisión', 'bg-amber-50 text-amber-800 border-amber-200'],
        error: ['Error bloqueante', 'bg-rose-50 text-rose-800 border-rose-200'],
        insertado: ['Insertado', 'bg-emerald-50 text-emerald-800 border-emerald-200'],
    };

    async function showImportAnalysis(item) {
        state.activeImport = item;
        const result = $('#import-result');
        result.classList.remove('hidden');
        result.replaceChildren();

        const title = element('div', 'flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between');
        title.append(
            element('div', 'font-black text-blue-950', `Análisis del lote #${item.id}`),
            element('div', `text-xs font-bold ${item.estado === 'pending' ? 'text-amber-700' : 'text-emerald-700'}`, item.estado === 'pending' ? 'Pendiente de confirmación' : 'Carga finalizada')
        );
        result.append(title, element('p', 'mt-1 text-xs text-slate-500', item.archivo));

        const summary = item.detalle?.resumen_final || item.detalle?.resumen || {};
        const cards = element('div', 'mt-4 grid grid-cols-2 gap-2 sm:grid-cols-5');
        ['nuevo', 'reingreso', 'duplicado', 'observado', 'error'].forEach((status) => {
            const [label, classes] = importStatusMeta[status];
            const card = element('div', `rounded-xl border p-3 ${classes}`);
            card.append(element('div', 'text-xl font-black', Number(summary[status] || 0).toLocaleString('es-PE')), element('div', 'text-xs font-bold', label));
            cards.append(card);
        });
        result.append(cards);

        if (item.detalle?.mensaje_fuente) {
            result.append(element('div', 'mt-3 rounded-xl bg-amber-50 p-3 text-xs font-semibold text-amber-800', item.detalle.mensaje_fuente));
        }

        const controls = element('div', 'mt-4 flex flex-col gap-2 sm:flex-row sm:items-center');
        const filter = element('select', 'min-h-10 rounded-xl border border-slate-300 px-3 text-sm');
        filter.append(element('option', '', 'Todas las filas'));
        filter.firstChild.value = '';
        Object.entries(importStatusMeta).forEach(([status, [label]]) => {
            const option = element('option', '', label);
            option.value = status;
            filter.append(option);
        });
        filter.addEventListener('change', () => loadImportRows(item.id, filter.value));
        controls.append(filter);
        if (item.estado === 'pending') {
            const eligible = Number(summary.nuevo || 0) + Number(summary.reingreso || 0);
            const confirm = element('button', 'sm:ml-auto rounded-xl bg-emerald-600 px-4 py-2 font-bold text-white hover:bg-emerald-700 disabled:opacity-50', `Confirmar ${eligible} episodio(s)`);
            confirm.disabled = eligible === 0;
            confirm.addEventListener('click', () => confirmImport(item, confirm));
            controls.append(confirm);
        }
        result.append(controls);
        result.append(element('div', 'mt-4 space-y-2', 'Cargando detalle de filas…'));
        result.lastChild.id = 'import-rows';
        await loadImportRows(item.id);
        result.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    async function loadImportRows(importId, status = '', page = 1) {
        const container = $('#import-rows');
        if (!container) return;
        container.replaceChildren(element('div', 'text-slate-500', 'Cargando filas…'));
        try {
            const url = new URL(`${config.importsUrl}/${importId}`, window.location.origin);
            url.searchParams.set('page', page);
            if (status) url.searchParams.set('estado', status);
            const response = await request(url);
            const pageData = response.data.filas;
            container.replaceChildren();
            pageData.data.forEach((row) => {
                const [label, classes] = importStatusMeta[row.estado] || [row.estado, 'bg-slate-50 text-slate-700 border-slate-200'];
                const card = element('article', 'rounded-xl border border-slate-200 bg-white p-3');
                const header = element('div', 'flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between');
                const data = row.datos || {};
                const identity = element('div');
                identity.append(
                    element('div', 'font-bold text-blue-950', `Fila ${row.fila} · ${[data.nomb, data.apell].filter(Boolean).join(' ') || 'Paciente sin nombre'}`),
                    element('div', 'mt-1 text-xs text-slate-500', `HC ${row.numhc || '—'} · Documento ${row.doc_iden || '—'} · ${formatDate(data.fecing)} → ${formatDate(data.fecegr)} · UPS ${data.ups || '—'}`)
                );
                header.append(identity, element('span', `rounded-full border px-3 py-1 text-xs font-bold ${classes}`, label));
                card.append(header);
                const messages = row.mensajes || [];
                if (messages.length) {
                    const list = element('ul', 'mt-3 space-y-1 text-xs');
                    messages.forEach((message) => {
                        const color = message.severity === 'error' ? 'text-rose-700' : message.severity === 'warning' ? 'text-amber-700' : 'text-slate-600';
                        list.append(element('li', color, `• ${message.message}`));
                    });
                    card.append(list);
                }
                container.append(card);
            });
            if (!pageData.data.length) container.append(element('div', 'rounded-xl bg-slate-50 p-5 text-center text-slate-500', 'No hay filas en este estado.'));
            if (pageData.last_page > 1) {
                const pager = element('div', 'flex items-center justify-between pt-2');
                pager.append(element('span', 'text-xs text-slate-500', `Página ${pageData.current_page} de ${pageData.last_page}`));
                const actions = element('div', 'flex gap-2');
                [['Anterior', pageData.current_page - 1, pageData.current_page <= 1], ['Siguiente', pageData.current_page + 1, pageData.current_page >= pageData.last_page]].forEach(([label, targetPage, disabled]) => {
                    const button = element('button', 'rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-bold disabled:opacity-40', label);
                    button.disabled = disabled;
                    button.addEventListener('click', () => loadImportRows(importId, status, targetPage));
                    actions.append(button);
                });
                pager.append(actions);
                container.append(pager);
            }
        } catch (error) {
            container.replaceChildren(element('div', 'rounded-xl bg-rose-50 p-4 font-semibold text-rose-700', error.message));
        }
    }

    async function confirmImport(item, button) {
        const summary = item.detalle?.resumen || {};
        const eligible = Number(summary.nuevo || 0) + Number(summary.reingreso || 0);
        if (!window.confirm(`Se insertarán ${eligible} episodio(s). Los duplicados y filas observadas no se cargarán. ¿Desea continuar?`)) return;
        button.disabled = true;
        button.textContent = 'Confirmando carga…';
        try {
            const response = await request(`${config.importsUrl}/${item.id}/confirmar`, {
                method: 'POST',
                body: JSON.stringify({}),
            });
            window.alert(response.message);
            await Promise.all([loadImports(), loadRecords(1), loadDashboard()]);
            await showImportAnalysis(response.data);
        } catch (error) {
            window.alert(error.message);
            button.disabled = false;
            button.textContent = `Confirmar ${eligible} episodio(s)`;
        }
    }

    async function importRecords(event) {
        event.preventDefault();
        const form = event.currentTarget;
        const button = form.querySelector('button[type="submit"]');
        const status = $('#import-status');
        const result = $('#import-result');
        button.disabled = true;
        status.textContent = 'Analizando filas, pacientes y episodios…';
        result.classList.add('hidden');
        try {
            const response = await request(config.importsUrl, { method: 'POST', body: new FormData(form) });
            const item = response.data;
            status.textContent = response.message;
            status.className = 'mr-auto text-sm font-semibold text-blue-700';
            form.reset();
            await loadImports();
            await showImportAnalysis(item);
        } catch (error) {
            status.className = 'mr-auto text-sm font-semibold text-rose-700';
            status.textContent = error.message;
        } finally {
            button.disabled = false;
        }
    }

    function reportParams() {
        return $('#report-form') ? new URLSearchParams(new FormData($('#report-form'))) : new URLSearchParams();
    }

    async function loadReports(event = null) {
        event?.preventDefault();
        const params = reportParams();
        const monthly = $('#monthly-report');
        const services = $('#services-report');
        monthly.replaceChildren(element('p', 'text-sm text-slate-500', 'Cargando…'));
        services.replaceChildren(element('p', 'text-sm text-slate-500', 'Cargando…'));
        $('#export-csv').href = `${config.reportCsvUrl}?${params}`;
        $('#export-xlsx').href = `${config.reportXlsxUrl}?${params}`;
        try {
            const [monthlyResponse, servicesResponse] = await Promise.all([
                request(`${config.monthlyUrl}?${params}`),
                request(`${config.servicesUrl}?${params}`),
            ]);
            monthly.replaceChildren();
            monthlyResponse.data.slice(-18).reverse().forEach((item) => monthly.append(reportBar(`${String(item.mes).padStart(2, '0')}/${item.anio}`, item.total)));
            services.replaceChildren();
            servicesResponse.data.forEach((item) => services.append(reportBar(item.ups, item.total)));
        } catch (error) {
            monthly.replaceChildren(element('p', 'text-sm font-semibold text-rose-700', error.message));
            services.replaceChildren();
        }
    }

    function reportBar(label, value) {
        const row = element('div', 'flex items-center justify-between gap-3 rounded-lg bg-slate-50 px-3 py-2 text-sm');
        row.append(
            element('span', 'truncate font-semibold', label || 'Sin dato'),
            element('span', 'font-black text-blue-800', Number(value).toLocaleString('es-PE'))
        );
        return row;
    }

    async function loadHistory() {
        const list = $('#history-list');
        const nextNumber = $('#history-next-number');
        if (!list) return;
        list.replaceChildren(element('div', 'rounded-xl bg-white p-5 text-slate-500', 'Cargando historial…'));
        if (nextNumber) nextNumber.textContent = 'Consultando…';
        try {
            const url = new URL(config.historyUrl, window.location.origin);
            const query = $('#history-query')?.value.trim();
            if (query) url.searchParams.set('q', query);
            const response = await request(url);
            if (nextNumber) {
                nextNumber.textContent = `N.° ${String(response.summary?.next_number || 1).padStart(4, '0')}-${response.summary?.year || new Date().getFullYear()}`;
            }
            list.replaceChildren();
            response.data.data.forEach((item) => {
                const card = element('article', 'flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between');
                const info = element('div');
                const stateClass = item.estado === 'anulada' ? 'text-rose-700' : 'text-emerald-700';
                info.append(
                    element('div', 'font-bold text-blue-950', `Constancia N.° ${String(item.numero).padStart(4, '0')}-${item.anio}`),
                    element('div', 'mt-1 text-sm text-slate-700', `${item.paciente || 'Paciente'} · HC ${item.numhc || '—'} · Documento ${item.doc_iden || '—'}`),
                    element('div', 'mt-1 text-xs text-slate-500', `Generada: ${formatDateTime(item.issued_at)} · Egreso: ${formatDate(item.fecegr)} · Servicio: ${item.servicio || item.ups || '—'}`),
                    element('div', `mt-1 text-xs font-semibold ${stateClass}`, `Estado: ${item.estado} · Emitida por: ${item.issuer_display_name || item.issuer_username || 'Importación histórica'}`)
                );
                const grouped = item.patient_group?.certificates || [];
                if (grouped.length > 1) {
                    const details = element('details', 'mt-3 rounded-xl border border-blue-100 bg-blue-50/60 p-3');
                    details.append(element('summary', 'cursor-pointer text-sm font-bold text-blue-800', `Ver agrupamiento del paciente (${item.patient_group.total} constancias)`));
                    const relatedList = element('div', 'mt-3 space-y-2');
                    grouped.forEach((related) => {
                        const current = Number(related.id) === Number(item.id);
                        const row = element('div', `grid gap-1 rounded-lg px-3 py-2 text-xs sm:grid-cols-[auto_1fr_auto] sm:items-center ${current ? 'bg-blue-100 font-bold text-blue-950' : 'bg-white text-slate-600'}`);
                        row.append(
                            element('span', '', `N.° ${String(related.numero).padStart(4, '0')}-${related.anio}`),
                            element('span', '', `${formatDateTime(related.issued_at)} · ${related.servicio || 'Sin servicio'} · Egreso ${formatDate(related.fecegr)}`),
                            element('span', related.estado === 'anulada' ? 'font-bold text-rose-700' : 'font-bold text-emerald-700', current ? `Actual · ${related.estado}` : related.estado)
                        );
                        relatedList.append(row);
                    });
                    details.append(relatedList);
                    info.append(details);
                }
                const actions = element('div', 'flex flex-wrap justify-end gap-2');
                const cancelled = item.estado === 'anulada';
                const link = element(
                    'a',
                    `rounded-xl border px-4 py-2 text-center text-sm font-bold ${cancelled ? 'border-slate-300 text-slate-700 hover:bg-slate-50' : 'border-blue-200 text-blue-700 hover:bg-blue-50'}`,
                    cancelled ? 'Visualizar anulada' : 'Ver / imprimir'
                );
                link.href = cancelled
                    ? `/egresos/constancias/${item.id}`
                    : `/egresos/constancias/${item.id}/imprimir`;
                link.target = '_blank';
                if (cancelled) {
                    link.title = 'Disponible solo para consulta histórica; la reimpresión está bloqueada.';
                }
                actions.append(link);
                if (config.abilities.updateCertificates && item.estado !== 'anulada') {
                    const edit = element('button', 'rounded-xl border border-amber-200 px-4 py-2 text-sm font-bold text-amber-700', 'Editar');
                    edit.addEventListener('click', () => openCertificateEdit(item));
                    actions.append(edit);
                }
                if (config.abilities.cancelCertificates && item.estado !== 'anulada') {
                    const cancel = element('button', 'rounded-xl border border-rose-200 px-4 py-2 text-sm font-bold text-rose-700', 'Anular');
                    cancel.addEventListener('click', () => cancelCertificate(item));
                    actions.append(cancel);
                }
                card.append(info, actions);
                list.append(card);
            });
            if (!response.data.data.length) list.append(element('div', 'rounded-xl bg-white p-8 text-center text-slate-500', 'No se encontraron constancias.'));
        } catch (error) {
            list.replaceChildren(element('div', 'rounded-xl bg-rose-50 p-5 font-semibold text-rose-700', error.message));
            if (nextNumber) nextNumber.textContent = 'No disponible';
        }
    }

    function openCertificateEdit(item) {
        state.selectedCertificate = item;
        const form = $('#edit-certificate-form');
        ['paciente', 'numhc', 'doc_iden', 'servicio', 'fecing', 'fecegr', 'ups', 'sigla_servicio', 'coddiag1', 'coddiag2', 'coddiag3', 'coddiag4', 'observacion'].forEach((name) => {
            if (form.elements[name]) {
                const value = item[name] ? String(item[name]) : '';
                form.elements[name].value = name.startsWith('fec') ? value.slice(0, 10) : value;
            }
        });
        $('#edit-status').textContent = '';
        openOverlay('#edit-certificate-modal');
    }

    async function saveCertificate(event) {
        event.preventDefault();
        const form = event.currentTarget;
        const button = form.querySelector('button[type="submit"]');
        button.disabled = true;
        try {
            const response = await request(`${config.certificateUrl}/${state.selectedCertificate.id}`, {
                method: 'PUT',
                body: JSON.stringify(Object.fromEntries(new FormData(form).entries())),
            });
            closeOverlay('#edit-certificate-modal');
            await loadHistory();
            window.alert(response.message);
        } catch (error) {
            $('#edit-status').textContent = error.message;
        } finally {
            button.disabled = false;
        }
    }

    async function cancelCertificate(item) {
        const reason = window.prompt(`Indique el motivo de anulación de la constancia ${String(item.numero).padStart(4, '0')}-${item.anio}:`);
        if (reason === null) return;
        if (reason.trim().length < 5) return window.alert('El motivo debe tener al menos 5 caracteres.');
        if (!window.confirm('La constancia quedará anulada y registrada en auditoría. ¿Desea continuar?')) return;
        try {
            const response = await request(`${config.certificateUrl}/${item.id}`, {
                method: 'DELETE', body: JSON.stringify({ motivo: reason.trim() }),
            });
            window.alert(response.message);
            await Promise.all([loadHistory(), loadDashboard()]);
        } catch (error) {
            window.alert(error.message);
        }
    }

    async function loadConfiguration(message = 'Formulario limpio para crear un nuevo registro.') {
        const form = $('#configuration-form');
        if (!form) return;
        const status = $('#configuration-status');
        form.reset();
        updateConfigurationPreview();
        status.textContent = message;
        try {
            const response = await request(config.configurationUrl);
            renderConfigurationRecords(response.data);
        } catch (error) {
            status.textContent = error.message;
        }
    }

    function renderConfigurationRecords(data) {
        const list = $('#configuration-history');
        const active = $('#configuration-active');
        if (!list || !active) return;
        const current = data?.active || {};
        active.textContent = current.updated_at
            ? `Configuración activa desde ${formatDateTime(current.updated_at)}`
            : 'Aún no existe una configuración activa';
        list.replaceChildren();
        (data?.records || []).forEach((item) => {
            const card = element('article', 'grid gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 sm:grid-cols-[1fr_auto] sm:items-center');
            const values = element('div');
            values.append(
                element('div', 'font-bold text-blue-950', `Registro #${item.id} · ${item.nombre_director || item.cargo_director || 'Configuración institucional'}`),
                element('div', 'mt-1 text-xs text-slate-600', `${item.actor_display_name || item.actor_username || 'Usuario central'} · ${formatDateTime(item.created_at)}`),
                element('div', 'mt-2 text-xs text-slate-500', `Iniciales: ${item.iniciales_jefe || item.iniciales_director || '—'} / ${item.iniciales_ccp || '—'}${item.observacion ? ` · ${item.observacion}` : ''}`)
            );
            card.append(values, element('span', 'rounded-full bg-emerald-100 px-3 py-1 text-center text-xs font-bold text-emerald-800', item.id === data.records[0]?.id ? 'Última versión' : 'Histórica'));
            list.append(card);
        });
        if (!(data?.records || []).length) {
            list.append(element('div', 'rounded-xl border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500', 'No hay configuraciones registradas todavía.'));
        }
    }

    async function saveConfiguration(event) {
        event.preventDefault();
        const form = event.currentTarget;
        const status = $('#configuration-status');
        const button = form.querySelector('button[type="submit"]');
        button.disabled = true;
        try {
            const response = await request(config.configurationUrl, {
                method: 'PUT', body: JSON.stringify(Object.fromEntries(new FormData(form).entries())),
            });
            status.textContent = response.message;
            form.reset();
            updateConfigurationPreview();
            await loadConfiguration(response.message);
            if ($('#panel-audit') && !$('#panel-audit').classList.contains('hidden')) loadAudit();
        } catch (error) {
            status.textContent = error.message;
        } finally {
            button.disabled = false;
        }
    }

    function updateConfigurationPreview() {
        const form = $('#configuration-form');
        if (!form) return;
        const value = (name) => form.elements[name]?.value.trim() || '';
        const previewValues = {
            iniciales_jefe: value('iniciales_jefe') || value('iniciales_director') || 'MASG',
            iniciales_ccp: value('iniciales_ccp') || 'KRJ',
            cargo_director: value('cargo_director') || 'DIRECCIÓN EJECUTIVA',
            nombre_director: value('nombre_director'),
        };
        Object.entries(previewValues).forEach(([name, content]) => {
            const target = document.querySelector(`[data-preview="${name}"]`);
            if (target) target.textContent = content.toUpperCase();
        });
    }

    const auditLabels = {
        'certificate.generar': 'Constancia generada',
        'certificate.editar': 'Constancia modificada',
        'certificate.anular': 'Constancia anulada',
        'certificate.imprimir': 'Impresión de constancia habilitada',
        'certificate_configuration.updated': 'Configuración actualizada',
        'certificate_configuration.registered': 'Configuración registrada',
        'record.create': 'Egreso registrado',
        'record.update': 'Egreso corregido',
        'import.previewed': 'Archivo analizado',
        'import.completed': 'Importación completada',
        'patients.reconciled': 'Pacientes conciliados',
    };

    function auditChanges(item) {
        const before = item.data_before || {};
        const after = item.data_after || {};
        const ignored = new Set(['updated_at', 'created_at', 'source_fingerprint']);
        return [...new Set([...Object.keys(before), ...Object.keys(after)])]
            .filter((key) => !ignored.has(key) && JSON.stringify(before[key] ?? null) !== JSON.stringify(after[key] ?? null))
            .slice(0, 8)
            .map((key) => {
                const oldValue = before[key] ?? '—';
                const newValue = after[key] ?? '—';
                return `${key}: ${String(oldValue)} → ${String(newValue)}`;
            });
    }

    function auditCard(item) {
        const card = element('article', 'rounded-2xl border border-slate-200 bg-white p-4 shadow-sm');
        const header = element('div', 'flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between');
        const title = element('div');
        title.append(
            element('div', 'font-black text-blue-950', auditLabels[item.event_type] || item.event_type),
            element('div', 'mt-1 text-xs text-slate-500', `Evento #${item.id} · Sujeto ${item.subject_id || '—'} · IP ${item.ip || '—'}`)
        );
        const actor = element('div', 'text-left text-xs sm:text-right');
        actor.append(
            element('div', 'font-bold text-slate-700', item.actor_display_name || item.actor_username || 'Proceso del sistema'),
            element('div', 'mt-1 text-slate-500', formatDateTime(item.occurred_at))
        );
        header.append(title, actor);
        card.append(header);
        const changes = auditChanges(item);
        if (changes.length) {
            const details = element('details', 'mt-3 rounded-xl bg-slate-50 p-3');
            details.append(element('summary', 'cursor-pointer text-sm font-bold text-blue-700', `Ver cambios registrados (${changes.length})`));
            const list = element('ul', 'mt-2 space-y-1 break-all text-xs text-slate-600');
            changes.forEach((change) => list.append(element('li', '', change)));
            details.append(list);
            card.append(details);
        }
        return card;
    }

    async function loadAudit(page = 1) {
        const list = $('#audit-list');
        const pagination = $('#audit-pagination');
        if (!list) return;
        list.replaceChildren(element('div', 'rounded-xl bg-white p-5 text-slate-500', 'Cargando auditoría…'));
        try {
            const url = new URL(config.auditUrl, window.location.origin);
            url.searchParams.set('page', page);
            const filters = {
                q: $('#audit-query')?.value.trim(),
                event_type: $('#audit-type')?.value,
                date_from: $('#audit-from')?.value,
                date_to: $('#audit-to')?.value,
            };
            Object.entries(filters).forEach(([key, value]) => {
                if (value) url.searchParams.set(key, value);
            });
            const response = await request(url);
            state.auditPage = response.meta.current_page;
            list.replaceChildren();
            response.data.forEach((item) => list.append(auditCard(item)));
            if (!response.data.length) list.append(element('div', 'rounded-xl bg-white p-8 text-center text-slate-500', 'No existen eventos con esos criterios.'));
            pagination.replaceChildren(element('span', 'text-slate-500', `${response.meta.total} evento(s) · Página ${response.meta.current_page} de ${response.meta.last_page}`));
            const controls = element('div', 'flex gap-2');
            [
                ['Anterior', response.meta.current_page - 1, response.meta.current_page <= 1],
                ['Siguiente', response.meta.current_page + 1, response.meta.current_page >= response.meta.last_page],
            ].forEach(([label, targetPage, disabled]) => {
                const button = element('button', 'rounded-lg border border-slate-300 px-3 py-2 font-semibold disabled:opacity-40', label);
                button.disabled = disabled;
                button.addEventListener('click', () => loadAudit(targetPage));
                controls.append(button);
            });
            pagination.append(controls);
        } catch (error) {
            list.replaceChildren(element('div', 'rounded-xl bg-rose-50 p-5 font-semibold text-rose-700', error.message));
            pagination.replaceChildren();
        }
    }

    $('#search-form')?.addEventListener('submit', (event) => {
        event.preventDefault();
        state.query = $('#search-query').value.trim();
        state.dateFrom = $('#search-from').value;
        state.dateTo = $('#search-to').value;
        loadRecords(1);
    });
    $('#new-record')?.addEventListener('click', () => openRecordForm());
    $('#record-form')?.addEventListener('submit', saveRecord);
    $('#lookup-patient')?.addEventListener('click', lookupPatient);
    $('#import-form')?.addEventListener('submit', importRecords);
    $('#report-form')?.addEventListener('submit', loadReports);
    $('#history-form')?.addEventListener('submit', (event) => { event.preventDefault(); loadHistory(); });
    $('#edit-certificate-form')?.addEventListener('submit', saveCertificate);
    $('#configuration-form')?.addEventListener('submit', saveConfiguration);
    $('#configuration-form')?.addEventListener('input', updateConfigurationPreview);
    $('#audit-form')?.addEventListener('submit', (event) => { event.preventDefault(); loadAudit(1); });
    document.querySelectorAll('.eg-tab').forEach((tab) => tab.addEventListener('click', () => {
        document.querySelectorAll('.eg-tab').forEach((item) => { item.className = 'eg-tab whitespace-nowrap rounded-xl px-4 py-2 text-sm font-bold text-slate-600 hover:bg-slate-100'; });
        tab.className = 'eg-tab whitespace-nowrap rounded-xl bg-blue-600 px-4 py-2 text-sm font-bold text-white';
        document.querySelectorAll('.eg-panel').forEach((panel) => panel.classList.add('hidden'));
        $(`#panel-${tab.dataset.panel}`)?.classList.remove('hidden');
        if (tab.dataset.panel === 'history') loadHistory();
        if (tab.dataset.panel === 'configuration') loadConfiguration();
        if (tab.dataset.panel === 'audit') loadAudit();
        if (tab.dataset.panel === 'imports') loadImports();
        if (tab.dataset.panel === 'reports') loadReports();
    }));

    loadDashboard();
    loadRecords();
})();
