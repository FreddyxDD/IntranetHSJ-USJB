<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel Administrador UEeI - Hospital San José</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="{{ asset('assets/js/csrf.js') }}" defer></script>

    <link rel="stylesheet" href="{{ asset('assets/css/tailwind.css') }}?v=4">
    <link rel="stylesheet" href="{{ asset('assets/css/admin-ueei.css') }}?v=1.1.0">
</head>
<body>

<div class="min-h-screen bg-[#eef3fb]">

<!-- SIDEBAR TAILWIND DESPLEGABLE -->
<aside
    id="adminSidebar"
    class="fixed left-6 top-8 z-40 flex h-[calc(100vh-64px)] w-[250px] flex-col overflow-hidden rounded-xl bg-gradient-to-b from-blue-500 to-blue-700 px-4 py-5 text-white shadow-2xl transition-all duration-300 ease-in-out"
>
    <!-- CABECERA -->
    <div class="mb-9 flex items-center justify-between">
        <h1
            id="sidebarLogoTexto"
            class="text-lg font-black italic tracking-tight transition-all duration-300"
        >
            Intranet
        </h1>

        <button
            id="btnToggleSidebar"
            type="button"
            class="flex h-9 w-9 items-center justify-center rounded-lg bg-white/10 text-lg font-black text-white transition hover:bg-white/20"
            title="Abrir / cerrar menú"
        >
            ☰
        </button>
    </div>

    <!-- MENÚ -->
    <nav class="flex flex-col gap-3" aria-label="Menú administrador">
        <button
            class="js-nav-item group flex h-11 items-center gap-3 rounded-2xl px-4 text-left text-sm font-bold text-white transition hover:bg-white/20"
            type="button"
            data-section="usuarios"
            title="Usuarios"
        >
            <span class="flex w-5 justify-center text-white">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M16 11c1.66 0 3-1.57 3-3.5S17.66 4 16 4s-3 1.57-3 3.5S14.34 11 16 11Zm-8 0c1.66 0 3-1.57 3-3.5S9.66 4 8 4 5 5.57 5 7.5 6.34 11 8 11Zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4Zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.96 1.97 3.45V19h7v-2c0-2.66-5.33-4-8-4Z"/>
                </svg>
            </span>
            <span class="sidebar-label whitespace-nowrap transition-all duration-300">Usuarios</span>
        </button>

        <button
            class="js-nav-item group flex h-11 items-center gap-3 rounded-2xl px-4 text-left text-sm font-bold text-white transition hover:bg-white/15"
            type="button"
            data-section="modulos"
            title="Módulos"
        >
            <span class="flex w-5 justify-center text-white">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M21 7.5 12 2 3 7.5v9L12 22l9-5.5v-9ZM12 4.34l5.93 3.62L12 11.58 6.07 7.96 12 4.34ZM5 9.73l6 3.67v5.77l-6-3.67V9.73Zm8 9.44V13.4l6-3.67v5.77l-6 3.67Z"/>
                </svg>
            </span>
            <span class="sidebar-label whitespace-nowrap transition-all duration-300">Módulos</span>
        </button>

    </nav>

    <!-- DECORACIÓN INFERIOR -->
    <div class="pointer-events-none mt-auto h-32 bg-[radial-gradient(circle_at_30%_70%,rgba(255,255,255,0.30),transparent_35%),radial-gradient(circle_at_70%_40%,rgba(255,255,255,0.18),transparent_35%)]"></div>

    <!-- BOTONES INFERIORES -->
    <div class="flex flex-col gap-3">
        <button
            id="btnCerrarSesionAdmin"
            type="button"
            class="sidebar-footer-link flex h-11 items-center gap-3 rounded-full bg-white px-4 text-sm font-black text-red-600 shadow-lg transition hover:bg-red-50"
            title="Cerrar sesión"
        >
            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-red-100">↪</span>
            <span class="sidebar-label whitespace-nowrap transition-all duration-300">Salir</span>
        </button>
    </div>
</aside>

    <!-- CONTENIDO -->
    <main id="adminMain" class="ml-[300px] min-h-screen p-7 transition-all duration-300 ease-in-out">

        <!-- HEADER -->
        <header class="admin-header">
            <div>
                <p class="header-label">Hospital San José de Chincha</p>
                <h2>Administración del Intranet</h2>
                <p class="header-description">
                    Gestiona usuarios, roles, áreas y módulos asignados dentro del sistema.
                </p>
            </div>

            <div class="admin-user-card">
                <div class="user-avatar">
                    <?= e(strtoupper(substr((string) $adminCorreo, 0, 1))) ?>
                </div>

                <div>
                    <strong><?= e((string) $adminCorreo) ?></strong>
                    <span><?= e((string) $adminRol) ?></span>
                </div>
            </div>
        </header>
        <!-- RESUMEN TAILWIND RECTANGULAR -->
        <section
            id="adminResumen"
            class="mt-8 grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-4"
        >
            <!-- USUARIOS -->
            <article class="flex min-h-[120px] items-center justify-between rounded-2xl border border-slate-200 bg-white px-7 py-6 shadow-sm transition hover:-translate-y-1 hover:shadow-md">
                <div>
                    <span class="block text-[13px] font-black uppercase tracking-wider text-slate-500">
                        Usuarios
                    </span>

                    <strong
                        id="totalUsuarios"
                        class="mt-3 block text-4xl font-black leading-none text-slate-950"
                    >
                        0
                    </strong>
                </div>

                <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-blue-50 text-blue-600">
                    <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                </div>
            </article>

            <!-- ACTIVOS -->
            <article class="flex min-h-[120px] items-center justify-between rounded-2xl border border-slate-200 bg-white px-7 py-6 shadow-sm transition hover:-translate-y-1 hover:shadow-md">
                <div>
                    <span class="block text-[13px] font-black uppercase tracking-wider text-slate-500">
                        Activos
                    </span>

                    <strong
                        id="usuariosActivos"
                        class="mt-3 block text-4xl font-black leading-none text-slate-950"
                    >
                        0
                    </strong>
                </div>

                <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600">
                    <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 6 9 17l-5-5"/>
                    </svg>
                </div>
            </article>

            <!-- SOLICITUDES PENDIENTES -->
            <article class="flex min-h-[120px] items-center justify-between rounded-2xl border border-amber-200 bg-amber-50 px-7 py-6 shadow-sm transition hover:-translate-y-1 hover:shadow-md">
                <div>
                    <span class="block text-[13px] font-black uppercase tracking-wider text-amber-700">
                        Solicitudes pendientes
                    </span>

                    <strong
                        id="solicitudesPendientes"
                        class="mt-3 block text-4xl font-black leading-none text-amber-900"
                    >
                        0
                    </strong>
                </div>

                <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-white text-amber-600">
                    <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="9"/>
                        <path d="M12 7v5l3 2"/>
                    </svg>
                </div>
            </article>

            <!-- ÁREAS -->
            <article class="flex min-h-[120px] items-center justify-between rounded-2xl border border-slate-200 bg-white px-7 py-6 shadow-sm transition hover:-translate-y-1 hover:shadow-md">
                <div>
                    <span class="block text-[13px] font-black uppercase tracking-wider text-slate-500">
                        Áreas
                    </span>

                    <strong
                        id="totalAreas"
                        class="mt-3 block text-4xl font-black leading-none text-slate-950"
                    >
                        0
                    </strong>
                </div>

                <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-red-50 text-red-500">
                    <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 21h18"/>
                        <path d="M5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16"/>
                        <path d="M9 21v-5h6v5"/>
                        <path d="M9 7h.01"/>
                        <path d="M15 7h.01"/>
                        <path d="M9 11h.01"/>
                        <path d="M15 11h.01"/>
                    </svg>
                </div>
            </article>

            <!-- MÓDULOS -->
            <article class="flex min-h-[120px] items-center justify-between rounded-2xl border border-slate-200 bg-white px-7 py-6 shadow-sm transition hover:-translate-y-1 hover:shadow-md">
                <div>
                    <span class="block text-[13px] font-black uppercase tracking-wider text-slate-500">
                        Módulos
                    </span>

                    <strong
                        id="totalModulos"
                        class="mt-3 block text-4xl font-black leading-none text-slate-950"
                    >
                        0
                    </strong>
                </div>

                <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600">
                    <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m21 16-9 5-9-5"/>
                        <path d="m21 12-9 5-9-5"/>
                        <path d="M3 8l9-5 9 5-9 5-9-5Z"/>
                    </svg>
                </div>
            </article>
        </section>

        <!-- ALERTAS -->
        <div id="adminAlerta" class="admin-alert hidden"></div>

        <!-- SECCIÓN USUARIOS -->
        <section class="admin-section active" id="sectionUsuarios">

            <div class="section-header">
                <div>
                    <h3>Gestión de usuarios</h3>
                    <p>Crea, edita y asigna accesos a los trabajadores del intranet.</p>
                </div>

                <button class="btn-primary" type="button" id="btnNuevoUsuario">
                    + Nuevo usuario
                </button>
            </div>

            <div class="content-grid content-grid--solo-tabla">

    <!-- MODAL FORMULARIO USUARIO -->
    <div class="modal-backdrop hidden" id="modalUsuario">
        <div class="modal-card modal-card-usuario">
            <div class="modal-header">
                <div>
                    <h3 id="formTitulo">Crear usuario</h3>
                    <p id="formSubtitulo">Registra una nueva cuenta y asigna sus módulos.</p>
                </div>

                <button type="button" class="modal-close" id="btnCerrarModalUsuario">
                    ×
                </button>
            </div>

            <article class="panel-card form-card form-card-modal">
                    <div class="panel-title">
                        <h4 id="formTitulo">Crear usuario</h4>
                        <p id="formSubtitulo">Registra una nueva cuenta y asigna sus módulos.</p>
                    </div>

                    <form id="formUsuario" autocomplete="off">
                        <input type="hidden" id="usuarioId" value="">

                        <div class="form-group">
                            <label for="correo">Correo institucional</label>
                            <input
                                type="email"
                                id="correo"
                                name="correo"
                                placeholder="usuario@hospital.gob.pe"
                                required
                            >
                        </div>

                        <div class="form-group" id="grupoPassword">
                            <label for="password">Contraseña</label>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder="Mínimo 8 caracteres"
                                autocomplete="new-password"
                            >
                            <small>Para nuevos usuarios es obligatoria. Para editar, usa “Cambiar contraseña”.</small>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="rol">Rol</label>
                                <select id="rol" name="rol" required>
                                    <option value="trabajador">Trabajador</option>
                                    <option value="supervisor">Supervisor</option>
                                    <option value="director">Director</option>
                                    <option value="admin">Administrador</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="areaId">Perfil de acceso</label>
                                <select id="areaId" name="area_id" required>
                                    <option value="">Selecciona un perfil</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="modules-header">
                                <label>Módulos heredados del perfil</label>
                                <button type="button" class="btn-link" id="btnSeleccionarTodo" disabled>
                                    Asignación central
                                </button>
                            </div>

                            <div class="modules-list" id="modulosLista">
                                <div class="empty-box">Cargando módulos...</div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button class="btn-secondary" type="button" id="btnCancelarEdicion">
                                Limpiar
                            </button>

                            <button class="btn-primary" type="submit" id="btnGuardarUsuario">
                                Guardar usuario
                            </button>
                        </div>
                     </form>
            </article>
        </div>
    </div>

    <!-- TABLA -->
    <article class="panel-card table-card table-card-full">
                    <div class="table-toolbar">
                        <div>
                            <h4>Usuarios registrados</h4>
                        </div>

                        <div class="search-box">
                            <input
                                type="search"
                                id="buscarUsuario"
                                placeholder="Buscar por correo, rol o área..."
                            >
                        </div>
                    </div>

                    <div class="table-wrapper">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Usuario</th>
                                    <th>Rol</th>
                                    <th>Área</th>
                                    <th>Módulos</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>

                            <tbody id="tablaUsuarios">
                                <tr>
                                    <td colspan="6" class="table-empty">
                                        Cargando usuarios...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </article>
            </div>
        </section>

        <!-- SECCIÓN MÓDULOS -->
        <section class="admin-section" id="sectionModulos">
            <div class="section-header">
                <div>
                    <h3>Módulos disponibles</h3>
                    <p>Módulos activos registrados en la base de datos.</p>
                </div>
            </div>

            <div class="modules-cards" id="modulosCards">
                <div class="empty-box">Cargando módulos...</div>
            </div>
        </section>
    </main>
</div>
<!-- TOAST TAILWIND -->
<div
    id="toastAdmin"
    class="pointer-events-none fixed right-8 top-8 z-[99999] w-[480px] max-w-[calc(100vw-2rem)] translate-y-[-12px] opacity-0 transition-all duration-300 ease-out"
>
    <div class="rounded-lg border border-gray-300 bg-white p-3 shadow-lg">
        <div class="flex flex-row">
            <div class="px-2 pt-1">
                <svg
                    width="24"
                    height="24"
                    viewBox="0 0 1792 1792"
                    fill="#44C997"
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <path d="M1299 813l-422 422q-19 19-45 19t-45-19l-294-294q-19-19-19-45t19-45l102-102q19-19 45-19t45 19l147 147 275-275q19-19 45-19t45 19l102 102q19 19 19 45t-19 45zm141 83q0-148-73-273t-198-198-273-73-273 73-198 198-73 273 73 273 198 198 273 73 273-73 198-198 73-273zm224 0q0 209-103 385.5t-279.5 279.5-385.5 103-385.5-103-279.5-279.5-103-385.5 103-385.5 279.5-279.5 385.5-103 385.5 103 279.5 279.5 103 385.5z"/>
                </svg>
            </div>

            <div class="ml-2 mr-6">
                <span
                    id="toastAdminTitulo"
                    class="font-semibold text-black"
                >
                    !Guardado con éxito¡
                </span>

                <span
                    id="toastAdminMensaje"
                    class="block text-gray-500"
                >
                    Anyone with a link can now view this file
                </span>
            </div>
        </div>
    </div>
</div>
<!-- MODAL CAMBIAR CONTRASEÑA -->
<div class="modal-backdrop hidden" id="modalPassword">
    <div class="modal-card">
        <div class="modal-header">
            <h3>Cambiar contraseña</h3>
            <button type="button" class="modal-close" id="btnCerrarModalPassword">×</button>
        </div>

        <form id="formPassword">
            <input type="hidden" id="passwordUsuarioId">

            <div class="form-group">
                <label for="nuevaPassword">Nueva contraseña</label>
                <input
                    type="password"
                    id="nuevaPassword"
                    placeholder="Mínimo 8 caracteres"
                    required
                >
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-secondary" id="btnCancelarPassword">
                    Cancelar
                </button>

                <button type="submit" class="btn-primary">
                    Actualizar contraseña
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    window.APP_BASE = @json(request()->getBaseUrl());

    window.ADMIN_UEEI = {
        resumen: "{{ url('/api/admin-ueei/resumen') }}",
        catalogos: "{{ url('/api/admin-ueei/catalogos') }}",
        usuarios: "{{ url('/api/admin-ueei/usuarios') }}",
        logout: "{{ url('/logout-ueei') }}",
        areas: "{{ url('/areas') }}"
    };
</script>

<script src="{{ asset('assets/js/admin-ueei.js') }}?v=1.2.0"></script>

</body>
</html>
