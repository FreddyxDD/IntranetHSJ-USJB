(() => {
    'use strict';

    const config = window.EGRESOS_CONFIG;
    const state = {
        page: 1, query: '', dateFrom: '', dateTo: '',
        selected: null, selectedCertificate: null, editingRecord: null,
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
        const row = element('tr', 'hover:bg-blue-50/50');
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
        const button = element('button', 'rounded-lg bg-blue-50 px-3 py-2 text-xs font-bold text-blue-700 hover:bg-blue-100', 'Ver detalle');
        button.addEventListener('click', () => showDetail(item.id));
        actions.append(button);
        row.append(
            identity,
            element('td', 'min-w-56 px-4 py-3 font-semibold', item.paciente || 'Sin nombre'),
            element('td', 'whitespace-nowrap px-4 py-3 text-slate-600', `${formatDate(item.fecing)} / ${formatDate(item.fecegr)}`),
            element('td', 'whitespace-nowrap px-4 py-3', item.ups || '—'),
            diagnosis,
            actions
        );
        return row;
    }

    async function loadRecords(page = 1) {
        const body = $('#records-body');
        const status = $('#records-status');
        body.replaceChildren();
        status.className = 'mt-3 text-sm text-slate-500';
        status.textContent = 'Consultando la base consolidada…';
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

    async function showDetail(id) {
        const content = $('#detail-content');
        content.textContent = 'Cargando detalle…';
        openOverlay('#detail-modal');
        try {
            const { data } = await request(`${config.recordsUrl}/${id}`);
            state.selected = data;
            const grid = element('div', 'grid gap-4 sm:grid-cols-2');
            [
                ['Paciente', data.paciente], ['Historia clínica', data.numhc], ['Documento', data.doc_iden],
                ['Ingreso', formatDate(data.fecing)], ['Egreso', formatDate(data.fecegr)], ['Servicio / UPS', data.ups],
                ['Condición', data.condicion], ['Financiamiento', data.financia],
            ].forEach(([label, value]) => {
                const card = element('div', 'rounded-xl bg-slate-50 p-3');
                card.append(element('div', 'text-xs font-bold uppercase text-slate-500', label), element('div', 'mt-1 font-semibold', value || '—'));
                grid.append(card);
            });
            const diagnosis = element('div', 'mt-5');
            diagnosis.append(element('h3', 'font-bold text-blue-950', 'Diagnósticos CIE-10'));
            const list = element('ul', 'mt-2 space-y-2');
            (data.diagnosticos || []).forEach((item) => list.append(element('li', 'rounded-xl border border-slate-200 p-3 text-sm', `${item.codigo} — ${item.descripcion}`)));
            if (!(data.diagnosticos || []).length) list.append(element('li', 'text-sm text-slate-500', 'No se registraron diagnósticos.'));
            diagnosis.append(list);
            content.replaceChildren(grid, diagnosis);
        } catch (error) {
            content.textContent = error.message;
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
        closeOverlay('#detail-modal');
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

    async function createCertificate() {
        if (!state.selected) return;
        const button = $('#create-certificate');
        button.disabled = true;
        button.textContent = 'Generando…';
        try {
            const result = await request(config.certificateUrl, {
                method: 'POST',
                body: JSON.stringify({ egreso_id: state.selected.id }),
            });
            window.open(result.print_url, '_blank', 'noopener');
            await loadDashboard();
        } catch (error) {
            window.alert(error.message);
        } finally {
            button.disabled = false;
            button.textContent = 'Generar constancia';
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
                card.append(
                    element('div', 'truncate text-sm font-bold text-blue-950', item.archivo),
                    element('div', 'mt-1 text-xs text-slate-500', `${item.insertados} insertados · ${item.omitidos} omitidos · ${item.errores} observados`),
                    element('div', `mt-1 text-xs font-semibold ${item.estado === 'completed' ? 'text-emerald-700' : 'text-rose-700'}`, item.estado)
                );
                list.append(card);
            });
            if (!response.data.length) list.append(element('p', 'text-sm text-slate-500', 'Todavía no hay importaciones.'));
        } catch (error) {
            list.replaceChildren(element('p', 'text-sm font-semibold text-rose-700', error.message));
        }
    }

    async function importRecords(event) {
        event.preventDefault();
        const form = event.currentTarget;
        const button = form.querySelector('button[type="submit"]');
        const status = $('#import-status');
        const result = $('#import-result');
        button.disabled = true;
        status.textContent = 'Procesando y validando el archivo…';
        result.classList.add('hidden');
        try {
            const response = await request(config.importsUrl, { method: 'POST', body: new FormData(form) });
            const item = response.data;
            status.textContent = response.message;
            status.className = 'mr-auto text-sm font-semibold text-emerald-700';
            result.classList.remove('hidden');
            result.replaceChildren(
                element('div', 'font-bold text-blue-950', 'Resumen de importación'),
                element('div', 'mt-2', `${item.insertados} insertados · ${item.omitidos} duplicados · ${item.errores} observados`)
            );
            const observations = item.detalle?.observaciones || [];
            if (observations.length) {
                const details = element('details', 'mt-3');
                details.append(element('summary', 'cursor-pointer font-semibold text-amber-700', `Ver observaciones (${observations.length})`));
                const list = element('ul', 'mt-2 max-h-56 space-y-1 overflow-y-auto text-xs text-slate-600');
                observations.forEach((observation) => list.append(element('li', '', `Fila ${observation.fila}: ${observation.errores.join(' ')}`)));
                details.append(list);
                result.append(details);
            }
            form.reset();
            await Promise.all([loadImports(), loadRecords(1), loadDashboard()]);
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
        if (!list) return;
        list.replaceChildren(element('div', 'rounded-xl bg-white p-5 text-slate-500', 'Cargando historial…'));
        try {
            const url = new URL(config.historyUrl, window.location.origin);
            const query = $('#history-query')?.value.trim();
            if (query) url.searchParams.set('q', query);
            const response = await request(url);
            list.replaceChildren();
            response.data.data.forEach((item) => {
                const card = element('article', 'flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between');
                const info = element('div');
                const stateClass = item.estado === 'anulada' ? 'text-rose-700' : 'text-emerald-700';
                info.append(
                    element('div', 'font-bold text-blue-950', `Constancia N.° ${String(item.numero).padStart(4, '0')}-${item.anio}`),
                    element('div', 'mt-1 text-sm text-slate-600', `${item.paciente || 'Paciente'} · HC ${item.numhc || '—'}`),
                    element('div', `mt-1 text-xs font-semibold ${stateClass}`, `Estado: ${item.estado} · ${item.issuer_display_name || item.issuer_username || 'Importación histórica'}`)
                );
                const actions = element('div', 'flex flex-wrap justify-end gap-2');
                const link = element('a', 'rounded-xl border border-blue-200 px-4 py-2 text-center text-sm font-bold text-blue-700 hover:bg-blue-50', 'Ver / imprimir');
                link.href = `/egresos/constancias/${item.id}/imprimir`;
                link.target = '_blank';
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

    async function loadConfiguration() {
        const form = $('#configuration-form');
        if (!form) return;
        const status = $('#configuration-status');
        status.textContent = 'Cargando configuración…';
        try {
            const response = await request(config.configurationUrl);
            Object.entries(response.data).forEach(([name, value]) => {
                if (form.elements[name]) form.elements[name].value = value || '';
            });
            status.textContent = 'Configuración cargada.';
        } catch (error) {
            status.textContent = error.message;
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
        } catch (error) {
            status.textContent = error.message;
        } finally {
            button.disabled = false;
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
    $('#edit-record')?.addEventListener('click', () => state.selected && openRecordForm(state.selected));
    $('#record-form')?.addEventListener('submit', saveRecord);
    $('#lookup-patient')?.addEventListener('click', lookupPatient);
    $('#import-form')?.addEventListener('submit', importRecords);
    $('#report-form')?.addEventListener('submit', loadReports);
    $('#history-form')?.addEventListener('submit', (event) => { event.preventDefault(); loadHistory(); });
    $('#edit-certificate-form')?.addEventListener('submit', saveCertificate);
    $('#configuration-form')?.addEventListener('submit', saveConfiguration);
    $('#create-certificate')?.addEventListener('click', createCertificate);
    document.querySelectorAll('.eg-tab').forEach((tab) => tab.addEventListener('click', () => {
        document.querySelectorAll('.eg-tab').forEach((item) => { item.className = 'eg-tab whitespace-nowrap rounded-xl px-4 py-2 text-sm font-bold text-slate-600 hover:bg-slate-100'; });
        tab.className = 'eg-tab whitespace-nowrap rounded-xl bg-blue-600 px-4 py-2 text-sm font-bold text-white';
        document.querySelectorAll('.eg-panel').forEach((panel) => panel.classList.add('hidden'));
        $(`#panel-${tab.dataset.panel}`)?.classList.remove('hidden');
        if (tab.dataset.panel === 'history') loadHistory();
        if (tab.dataset.panel === 'configuration') loadConfiguration();
        if (tab.dataset.panel === 'imports') loadImports();
        if (tab.dataset.panel === 'reports') loadReports();
    }));

    loadDashboard();
    loadRecords();
})();
