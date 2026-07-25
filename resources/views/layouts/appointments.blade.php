<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Citas') | Intranet HSJ</title>
    <link rel="stylesheet" href="{{ asset('css/appointments.css') }}">
</head>
<body>
<header class="topbar">
    <a class="brand" href="{{ route('appointments.index') }}">
        <span class="brand-mark">HSJ</span>
        <span><strong>Hospital San José</strong><small>Gestión de citas</small></span>
    </a>
    <nav>
        <a @class(['active' => request()->routeIs('appointments.index', 'appointments.show')]) href="{{ route('appointments.index') }}">Agenda diaria</a>
        <a @class(['active' => request()->routeIs('appointments.reports')]) href="{{ route('appointments.reports') }}">Reportes</a>
    </nav>
    <div class="user-menu">
        <span>{{ auth()->user()->name }}</span>
        <form method="POST" action="{{ route('logout') }}">@csrf<button class="link-button">Cerrar sesión</button></form>
    </div>
</header>
<main class="container">
    @if (session('status'))<div class="alert success">{{ session('status') }}</div>@endif
    @if (isset($errors) && $errors->any())<div class="alert error">{{ $errors->first() }}</div>@endif
    @yield('content')
</main>
</body>
</html>
