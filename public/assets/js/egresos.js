(() => {
    'use strict';

    const config = window.EGRESOS_CONFIG;
    const state = { page: 1, query: '', selected: null };
    const $ = (selector) => document.querySelector(selector);
    const element = (tag, classes, text) => {
        const node = document.createElement(tag);
        if (classes) node.className = classes;
        if (text !== undefined) node.textContent = text;
        return node;
    };
    const formatDate = (value) => value ? new Intl.DateTimeFormat('es-PE', { timeZone: 'UTC' }).format(new Date(`${String(value).slice(0, 10)}T00:00:00Z`)) : '—';
    const request = async (url, options = {}) => {
        const response = await fetch(url, {
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                ...options.headers,
            },
            ...options,
        });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(payload.message || 'No fue posible completar la operación.');
        return payload;
    };
    const openModal = () => {
        if (window.HSOverlay) window.HSOverlay.open('#detail-modal');
        else {
            $('#detail-modal')?.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        }
    };

    async function loadDashboard() {
        try {
            const { data } = await request(config.dashboardUrl);
            Object.entries(data).forEach(([key, value]) => {
                const target = document.querySelector(`[data-metric="${key}"]`);
                if (target) target.textContent = Number(value).toLocaleString('es-PE');
            });
        } catch (error) {
            document.querySelectorAll('[data-metric]').forEach((node) => { node.textContent = 'N/D'; });
        }
    }

    function recordRow(item) {
        const row = element('tr', 'hover:bg-blue-50/50');
        const identity = element('td', 'whitespace-nowrap px-4 py-3');
        identity.append(element('div', 'font-bold text-blue-950', `HC ${item.numhc || '—'}`), element('div', 'text-xs text-slate-500', item.doc_iden || 'Sin documento'));
        const patient = element('td', 'min-w-56 px-4 py-3 font-semibold', item.paciente || 'Sin nombre');
        const dates = element('td', 'whitespace-nowrap px-4 py-3 text-slate-600', `${formatDate(item.fecing)} / ${formatDate(item.fecegr)}`);
        const ups = element('td', 'whitespace-nowrap px-4 py-3', item.ups || '—');
        const diagnosis = element('td', 'min-w-64 px-4 py-3');
        const main = item.diagnosticos?.[0];
        diagnosis.append(element('div', 'font-semibold', main?.codigo || '—'), element('div', 'line-clamp-2 text-xs text-slate-500', main?.descripcion || 'Sin diagnóstico'));
        const actions = element('td', 'whitespace-nowrap px-4 py-3 text-right');
        const button = element('button', 'rounded-lg bg-blue-50 px-3 py-2 text-xs font-bold text-blue-700 hover:bg-blue-100', 'Ver detalle');
        button.addEventListener('click', () => showDetail(item.id));
        actions.append(button);
        row.append(identity, patient, dates, ups, diagnosis, actions);
        return row;
    }

    async function loadRecords(page = 1) {
        const body = $('#records-body');
        const status = $('#records-status');
        body.replaceChildren();
        status.textContent = 'Consultando la base consolidada…';
        try {
            const url = new URL(config.recordsUrl, window.location.origin);
            url.searchParams.set('page', page);
            url.searchParams.set('per_page', 20);
            if (state.query) url.searchParams.set('q', state.query);
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
        container.replaceChildren();
        container.append(element('span', 'text-slate-500', `Página ${meta.current_page} de ${meta.last_page}`));
        const buttons = element('div', 'flex gap-2');
        [['Anterior', meta.current_page - 1, meta.current_page <= 1], ['Siguiente', meta.current_page + 1, meta.current_page >= meta.last_page]].forEach(([label, page, disabled]) => {
            const button = element('button', 'rounded-lg border border-slate-300 px-3 py-2 font-semibold disabled:cursor-not-allowed disabled:opacity-40', label);
            button.disabled = disabled;
            button.addEventListener('click', () => loadRecords(page));
            buttons.append(button);
        });
        container.append(buttons);
    }

    async function showDetail(id) {
        const content = $('#detail-content');
        content.textContent = 'Cargando detalle…';
        openModal();
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
                info.append(element('div', 'font-bold text-blue-950', `Constancia N.° ${String(item.numero).padStart(4, '0')}-${item.anio}`), element('div', 'mt-1 text-sm text-slate-600', `${item.paciente || 'Paciente'} · HC ${item.numhc || '—'}`), element('div', 'mt-1 text-xs text-slate-500', `Estado: ${item.estado} · ${item.issuer_display_name || item.issuer_username || 'Importación histórica'}`));
                const link = element('a', 'rounded-xl border border-blue-200 px-4 py-2 text-center text-sm font-bold text-blue-700 hover:bg-blue-50', 'Ver / imprimir');
                link.href = `/egresos/constancias/${item.id}/imprimir`;
                link.target = '_blank';
                card.append(info, link);
                list.append(card);
            });
            if (!response.data.data.length) list.append(element('div', 'rounded-xl bg-white p-8 text-center text-slate-500', 'No se encontraron constancias.'));
        } catch (error) {
            list.replaceChildren(element('div', 'rounded-xl bg-rose-50 p-5 font-semibold text-rose-700', error.message));
        }
    }

    $('#search-form')?.addEventListener('submit', (event) => {
        event.preventDefault();
        state.query = $('#search-query').value.trim();
        loadRecords(1);
    });
    $('#history-form')?.addEventListener('submit', (event) => { event.preventDefault(); loadHistory(); });
    $('#create-certificate')?.addEventListener('click', createCertificate);
    document.querySelectorAll('.eg-tab').forEach((tab) => tab.addEventListener('click', () => {
        document.querySelectorAll('.eg-tab').forEach((item) => item.className = 'eg-tab whitespace-nowrap rounded-xl px-4 py-2 text-sm font-bold text-slate-600 hover:bg-slate-100');
        tab.className = 'eg-tab whitespace-nowrap rounded-xl bg-blue-600 px-4 py-2 text-sm font-bold text-white';
        document.querySelectorAll('.eg-panel').forEach((panel) => panel.classList.add('hidden'));
        $(`#panel-${tab.dataset.panel}`)?.classList.remove('hidden');
        if (tab.dataset.panel === 'history') loadHistory();
    }));

    loadDashboard();
    loadRecords();
})();
