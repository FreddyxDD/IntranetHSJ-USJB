@extends('layouts.appointments')

@section('title', 'Agenda diaria')

@section('content')
<section class="page-heading">
    <div><p class="eyebrow">Consulta SIGH en tiempo real</p><h1>Agenda diaria de citas</h1><p>Programaciones, cupos y atención por consultorio.</p></div>
    <a class="button secondary" href="{{ route('appointments.reports') }}">Ver reporte mensual</a>
</section>

<section class="metrics">
    <article><span>Programaciones</span><strong>{{ number_format($summary['programaciones']) }}</strong></article>
    <article><span>Cupos</span><strong>{{ number_format($summary['cupos']) }}</strong></article>
    <article><span>Citas otorgadas</span><strong>{{ number_format($summary['otorgadas']) }}</strong></article>
    <article><span>Atendidas</span><strong>{{ number_format($summary['atendidas']) }}</strong></article>
    <article><span>Adicionales</span><strong>{{ number_format($summary['adicionales']) }}</strong></article>
</section>

<form class="filters card" method="GET">
    <label>Fecha<input type="date" name="fecha" value="{{ $date }}"></label>
    <label>Especialidad<select name="especialidad"><option value="">Todas</option>@foreach($specialties as $item)<option value="{{ $item->id }}" @selected($specialty === (int) $item->id)>{{ $item->nombre }}</option>@endforeach</select></label>
    <label class="grow">Buscar<input name="buscar" value="{{ $search }}" placeholder="Especialidad, servicio o médico"></label>
    <button class="button" type="submit">Filtrar</button>
    <a class="button ghost" href="{{ route('appointments.index') }}">Limpiar</a>
</form>

<section class="card table-card">
    <div class="table-title"><h2>Programación del {{ \Illuminate\Support\Carbon::parse($date)->translatedFormat('d \d\e F \d\e Y') }}</h2><span>{{ $programs->count() }} resultados</span></div>
    <div class="table-scroll"><table>
        <thead><tr><th>Horario</th><th>Especialidad / servicio</th><th>Médico</th><th>Cupos</th><th>Otorgadas</th><th>Atendidas</th><th>Estado</th><th></th></tr></thead>
        <tbody>
        @forelse($programs as $program)
            <tr>
                <td><strong>{{ substr($program->hora_inicio, 0, 5) }}</strong><small>{{ substr($program->hora_fin, 0, 5) }}</small></td>
                <td><strong>{{ $program->especialidad }}</strong><small>{{ $program->servicio }}</small></td>
                <td>{{ $program->medico ?: 'No asignado' }}</td>
                <td>{{ $program->cupos_programados }}</td><td>{{ $program->citas_otorgadas }}</td><td>{{ $program->citas_atendidas }}</td>
                <td><span class="badge {{ strtolower($program->estado_local) }}">{{ str_replace('_', ' ', $program->estado_local) }}</span></td>
                <td><a class="table-link" href="{{ route('appointments.show', $program->id_programacion) }}">Abrir →</a></td>
            </tr>
        @empty
            <tr><td class="empty" colspan="8">No se encontraron programaciones para los filtros seleccionados.</td></tr>
        @endforelse
        </tbody>
    </table></div>
</section>
@endsection
