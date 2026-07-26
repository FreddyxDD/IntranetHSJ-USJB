(() => {
    "use strict";

    const config = window.CIE10_CONFIG;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || "";
    const state = { page: 1, rows: [], activeBatch: null };
    const $ = (selector) => document.querySelector(selector);
    const escapeHtml = (value) => String(value ?? "").replace(/[&<>"']/g, (character) => ({
        "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#039;",
    })[character]);

    async function api(url, options = {}) {
        const headers = { Accept: "application/json", ...(options.headers || {}) };
        if (!(options.body instanceof FormData)) headers["Content-Type"] = "application/json";
        if (options.method && options.method !== "GET") headers["X-CSRF-TOKEN"] = csrf;
        const response = await fetch(url, { ...options, headers });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok) {
            const validation = payload.errors ? Object.values(payload.errors).flat().join(" ") : "";
            throw new Error(validation || payload.message || "No fue posible completar la operación.");
        }
        return payload;
    }

    function queryString(values) {
        const params = new URLSearchParams();
        Object.entries(values).forEach(([key, value]) => {
            if (value !== "" && value !== null && value !== undefined) params.set(key, value);
        });
        return params.toString();
    }

    async function loadCatalog(page = 1) {
        const form = new FormData($("#catalog-search"));
        state.page = page;
        $("#catalog-status").textContent = "Consultando catálogo…";
        try {
            const payload = await api(`${config.catalogUrl}?${queryString({
                q: form.get("q"), estado: form.get("estado"),
                cotejo_sexo: form.get("cotejo_sexo"), page,
            })}`);
            state.rows = payload.data;
            renderCatalog(payload.data, payload.meta);
        } catch (error) {
            $("#catalog-status").textContent = error.message;
        }
    }

    function renderCatalog(rows, meta) {
        $("#catalog-status").textContent = `${meta.total.toLocaleString("es-PE")} código(s) encontrados`;
        $("#catalog-body").innerHTML = rows.length ? rows.map((row) => `
            <tr class="align-top">
                <td class="whitespace-nowrap px-4 py-3 font-black text-blue-800">${escapeHtml(row.codigo)}</td>
                <td class="min-w-72 px-4 py-3">${escapeHtml(row.descripcion)}</td>
                <td class="px-4 py-3"><span class="rounded-full px-2.5 py-1 text-xs font-bold ${row.estado === "ACTIVO" ? "bg-emerald-100 text-emerald-800" : "bg-slate-200 text-slate-700"}">${escapeHtml(row.estado)}</span></td>
                <td class="px-4 py-3 text-xs font-semibold text-slate-600">${escapeHtml(row.cotejo_sexo)}</td>
                <td class="whitespace-nowrap px-4 py-3 text-right">
                    <button type="button" data-edit="${row.id}" class="rounded-lg border border-blue-200 px-3 py-1.5 text-xs font-bold text-blue-700 hover:bg-blue-50">Editar</button>
                    ${row.estado === "ACTIVO" ? `<button type="button" data-deactivate="${row.id}" class="ml-1 rounded-lg border border-rose-200 px-3 py-1.5 text-xs font-bold text-rose-700 hover:bg-rose-50">Desactivar</button>` : ""}
                </td>
            </tr>`).join("") : '<tr><td colspan="5" class="px-4 py-10 text-center text-slate-500">No se encontraron códigos.</td></tr>';
        $("#catalog-pagination").innerHTML = `
            <span>Página ${meta.current_page} de ${Math.max(meta.last_page, 1)}</span>
            <span class="flex gap-2">
                <button type="button" data-page="${meta.current_page - 1}" ${meta.current_page <= 1 ? "disabled" : ""} class="rounded-lg border px-3 py-1.5 disabled:opacity-40">Anterior</button>
                <button type="button" data-page="${meta.current_page + 1}" ${meta.current_page >= meta.last_page ? "disabled" : ""} class="rounded-lg border px-3 py-1.5 disabled:opacity-40">Siguiente</button>
            </span>`;
    }

    function resetForm() {
        const form = $("#catalog-form");
        form.reset();
        form.elements.id.value = "";
        form.elements.version.value = "";
        form.elements.codigo.readOnly = false;
        form.elements.codigo.classList.remove("bg-slate-100");
        $("#form-title").textContent = "Nuevo código CIE-10";
        $("#form-status").textContent = "";
        $("#cancel-edit").classList.add("hidden");
    }

    function editRow(id) {
        const row = state.rows.find((item) => item.id === Number(id));
        if (!row) return;
        const form = $("#catalog-form");
        form.elements.id.value = row.id;
        form.elements.version.value = row.version;
        form.elements.codigo.value = row.codigo;
        form.elements.descripcion.value = row.descripcion;
        form.elements.estado.value = row.estado;
        form.elements.cotejo_sexo.value = row.cotejo_sexo;
        form.elements.codigo.readOnly = true;
        form.elements.codigo.classList.add("bg-slate-100");
        $("#form-title").textContent = `Editar ${row.codigo}`;
        $("#form-status").textContent = "";
        $("#cancel-edit").classList.remove("hidden");
        form.scrollIntoView({ behavior: "smooth", block: "start" });
    }

    async function saveCode(event) {
        event.preventDefault();
        const form = event.currentTarget;
        const id = form.elements.id.value;
        const data = Object.fromEntries(new FormData(form).entries());
        $("#form-status").className = "mt-4 min-h-5 text-sm font-semibold text-slate-600";
        $("#form-status").textContent = "Guardando…";
        try {
            const payload = await api(id ? `${config.catalogUrl}/${id}` : config.catalogUrl, {
                method: id ? "PUT" : "POST",
                body: JSON.stringify(data),
            });
            $("#form-status").className = "mt-4 min-h-5 text-sm font-semibold text-emerald-700";
            $("#form-status").textContent = payload.message;
            resetForm();
            await loadCatalog(id ? state.page : 1);
        } catch (error) {
            $("#form-status").className = "mt-4 min-h-5 text-sm font-semibold text-rose-700";
            $("#form-status").textContent = error.message;
        }
    }

    async function deactivate(id) {
        const row = state.rows.find((item) => item.id === Number(id));
        if (!row || !window.confirm(`¿Desactivar ${row.codigo}? Seguirá disponible en el historial clínico.`)) return;
        try {
            const payload = await api(`${config.catalogUrl}/${row.id}`, {
                method: "DELETE",
                body: JSON.stringify({ version: row.version }),
            });
            window.alert(payload.message);
            resetForm();
            await loadCatalog(state.page);
        } catch (error) {
            window.alert(error.message);
        }
    }

    async function analyzeImport(event) {
        event.preventDefault();
        const form = event.currentTarget;
        $("#import-status").className = "mr-auto text-sm font-semibold text-slate-600";
        $("#import-status").textContent = "Analizando todas las filas…";
        try {
            const payload = await api(config.importsUrl, { method: "POST", body: new FormData(form) });
            state.activeBatch = payload.data;
            $("#import-status").className = "mr-auto text-sm font-semibold text-emerald-700";
            $("#import-status").textContent = payload.message;
            renderAnalysis(payload.data);
            await Promise.all([loadImportRows(payload.data.id), loadImports()]);
        } catch (error) {
            $("#import-status").className = "mr-auto text-sm font-semibold text-rose-700";
            $("#import-status").textContent = error.message;
        }
    }

    function renderAnalysis(batch) {
        const canConfirm = batch.estado === "analizado" && Number(batch.errores) === 0;
        const panel = $("#import-analysis");
        panel.classList.remove("hidden");
        panel.innerHTML = `
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div><h2 class="font-black text-blue-950">Resultado del lote #${batch.id}</h2><p class="mt-1 text-sm text-slate-500">${escapeHtml(batch.archivo)}</p></div>
                <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-800">${escapeHtml(batch.estado)}</span>
            </div>
            <div class="mt-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
                ${summaryCard("Nuevos", batch.nuevos, "text-emerald-700")}
                ${summaryCard("Actualizar", batch.actualizaciones, "text-amber-700")}
                ${summaryCard("Sin cambios", batch.sin_cambios, "text-slate-600")}
                ${summaryCard("Errores", batch.errores, "text-rose-700")}
            </div>
            <div class="mt-4 flex flex-wrap items-center justify-end gap-3">
                <p class="mr-auto text-sm ${canConfirm ? "text-slate-600" : "font-semibold text-rose-700"}">${canConfirm ? "Revise la muestra y confirme para aplicar los cambios." : "Un lote con errores no puede aplicarse."}</p>
                ${batch.estado === "analizado" ? `<button type="button" id="confirm-import" ${canConfirm ? "" : "disabled"} class="rounded-xl bg-emerald-600 px-5 py-2.5 font-bold text-white disabled:cursor-not-allowed disabled:opacity-40">Confirmar actualización</button>` : ""}
            </div>`;
    }

    function summaryCard(label, value, color) {
        return `<div class="rounded-xl bg-slate-50 p-3"><p class="text-xs font-bold uppercase text-slate-500">${label}</p><p class="mt-1 text-2xl font-black ${color}">${Number(value).toLocaleString("es-PE")}</p></div>`;
    }

    async function loadImportRows(id, page = 1) {
        try {
            const payload = await api(`${config.importsUrl}/${id}?page=${page}`);
            const rows = payload.data.filas.data;
            const container = $("#import-rows");
            container.classList.remove("hidden");
            container.innerHTML = `
                <div class="border-b border-slate-200 px-4 py-3"><h3 class="font-black text-blue-950">Detalle del análisis</h3><p class="text-xs text-slate-500">Página ${payload.data.filas.current_page} de ${payload.data.filas.last_page}</p></div>
                <div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-sm"><thead class="bg-slate-50 text-left text-xs uppercase text-slate-500"><tr><th class="px-4 py-3">Fila</th><th class="px-4 py-3">Código</th><th class="px-4 py-3">Descripción</th><th class="px-4 py-3">Resultado</th><th class="px-4 py-3">Observación</th></tr></thead>
                <tbody class="divide-y divide-slate-100">${rows.map((row) => `<tr class="align-top"><td class="px-4 py-3">${row.fila}</td><td class="px-4 py-3 font-bold text-blue-800">${escapeHtml(row.codigo)}</td><td class="min-w-72 px-4 py-3">${escapeHtml(row.datos?.descripcion)}</td><td class="px-4 py-3 font-bold">${escapeHtml(row.estado)}</td><td class="min-w-64 px-4 py-3 text-xs text-rose-700">${escapeHtml((row.mensajes || []).map((item) => item.message).join(" "))}</td></tr>`).join("")}</tbody></table></div>`;
        } catch (error) {
            $("#import-rows").classList.remove("hidden");
            $("#import-rows").innerHTML = `<p class="p-5 text-rose-700">${escapeHtml(error.message)}</p>`;
        }
    }

    async function confirmImport() {
        const batch = state.activeBatch;
        if (!batch || !window.confirm("¿Confirma aplicar los códigos nuevos y las actualizaciones identificadas?")) return;
        const button = $("#confirm-import");
        if (button) { button.disabled = true; button.textContent = "Aplicando…"; }
        try {
            const payload = await api(`${config.importsUrl}/${batch.id}/confirmar`, { method: "POST", body: "{}" });
            state.activeBatch = payload.data;
            renderAnalysis(payload.data);
            await Promise.all([loadImportRows(batch.id), loadImports(), loadCatalog(1)]);
            window.alert(payload.message);
        } catch (error) {
            window.alert(error.message);
            if (button) { button.disabled = false; button.textContent = "Confirmar actualización"; }
        }
    }

    async function loadImports() {
        try {
            const payload = await api(config.importsUrl);
            $("#imports-list").innerHTML = payload.data.length ? payload.data.map((batch) => `
                <button type="button" data-batch="${batch.id}" class="block w-full rounded-xl border border-slate-200 p-3 text-left hover:border-blue-300 hover:bg-blue-50">
                    <span class="flex items-center justify-between gap-2"><strong class="text-sm text-blue-950">Lote #${batch.id}</strong><span class="text-xs font-bold text-slate-500">${escapeHtml(batch.estado)}</span></span>
                    <span class="mt-1 block truncate text-xs text-slate-500">${escapeHtml(batch.archivo)}</span>
                    <span class="mt-2 block text-xs">+${batch.nuevos} nuevos · ${batch.actualizaciones} cambios · ${batch.errores} errores</span>
                </button>`).join("") : '<p class="text-sm text-slate-500">Aún no existen lotes.</p>';
        } catch (error) {
            $("#imports-list").innerHTML = `<p class="text-sm text-rose-700">${escapeHtml(error.message)}</p>`;
        }
    }

    document.addEventListener("click", async (event) => {
        const page = event.target.closest("[data-page]")?.dataset.page;
        if (page && Number(page) > 0) loadCatalog(Number(page));
        const edit = event.target.closest("[data-edit]")?.dataset.edit;
        if (edit) editRow(edit);
        const remove = event.target.closest("[data-deactivate]")?.dataset.deactivate;
        if (remove) deactivate(remove);
        const batchId = event.target.closest("[data-batch]")?.dataset.batch;
        if (batchId) {
            const payload = await api(`${config.importsUrl}/${batchId}`);
            state.activeBatch = payload.data.importacion;
            renderAnalysis(state.activeBatch);
            loadImportRows(batchId);
        }
        if (event.target.closest("#confirm-import")) confirmImport();
    });

    document.querySelectorAll(".cie-tab").forEach((button) => button.addEventListener("click", () => {
        document.querySelectorAll(".cie-tab").forEach((tab) => {
            tab.classList.toggle("bg-blue-600", tab === button);
            tab.classList.toggle("text-white", tab === button);
            tab.classList.toggle("text-slate-600", tab !== button);
        });
        document.querySelectorAll(".cie-panel").forEach((panel) => panel.classList.toggle("hidden", panel.id !== `panel-${button.dataset.panel}`));
        if (button.dataset.panel === "imports") loadImports();
    }));
    $("#catalog-search").addEventListener("submit", (event) => { event.preventDefault(); loadCatalog(1); });
    $("#catalog-form").addEventListener("submit", saveCode);
    $("#new-code").addEventListener("click", resetForm);
    $("#cancel-edit").addEventListener("click", resetForm);
    $("#import-form").addEventListener("submit", analyzeImport);
    loadCatalog();
})();
