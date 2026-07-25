@extends('layouts.appointments')

@section('title', 'Programación '.$program->id_programacion)

@section('content')
<a class="back" href="{{ route('appointments.index', ['fecha' => $program->fecha]) }}">← Volver a la agenda</a>
<section class="page-heading compact">
    <div><p class="eyebrow">Programación N.° {{ $program->id_programacion }}</p><h1>{{ $program->especialidad }}</h1><p>{{ $program->servicio }} · {{ $program->medico }} · {{ substr($program->hora_inicio,0,5) }}–{{ substr($program->hora_fin,0,5) }}</p></div>
</section>

@if(auth()->user()->hasRole('administrador') || auth()->user()->hasPermission('citas.manage') || auth()->user()->hasPermission('citas.fua.validate'))
<form class="card state-form" method="POST" action="{{ route('appointments.program-state', $program->id_programacion) }}">
    @csrf @method('PATCH')
    <label>Estado operativo<select name="estado">@foreach(['PROGRAMADO','EN_PROCESO','CERRADO','CANCELADO'] as $option)<option @selected(($state->estado ?? 'PROGRAMADO') === $option)>{{ $option }}</option>@endforeach</select></label>
    <label class="grow">Observación<input name="observacion" maxlength="500" value="{{ $state->observacion ?? '' }}" placeholder="Novedad del consultorio"></label>
    <button class="button">Guardar estado</button>
</form>
@endif

<section class="card table-card">
    <div class="table-title"><h2>Pacientes citados</h2><span>{{ $patients->count() }} pacientes</span></div>
    <div class="table-scroll"><table>
        <thead><tr><th>Hora</th><th>HC / documento</th><th>Paciente</th><th>Teléfono</th><th>Tipo</th><th>Estado SIGH</th></tr></thead>
        <tbody>@forelse($patients as $patient)<tr>
            <td>{{ substr($patient->hora_inicio,0,5) }}</td>
            <td><strong>{{ $patient->historia_clinica }}</strong><small>{{ $patient->documento }}</small></td>
            <td>{{ $patient->paciente }}</td><td>{{ $patient->telefono ?: '—' }}</td>
            <td><span class="badge {{ $patient->es_adicional ? 'adicional' : '' }}">{{ $patient->es_adicional ? 'Adicional' : 'Regular' }}</span></td>
            <td>
            @if(config('database.connections.sigh.allow_writes') && (auth()->user()->hasRole('administrador') || auth()->user()->hasPermission('citas.manage') || auth()->user()->hasPermission('citas.fua.validate')))
                <form class="inline-form" method="POST" action="{{ route('appointments.appointment-state', $patient->id_cita) }}">@csrf @method('PATCH')
                    <select name="estado_id">@foreach($appointmentStates as $option)<option value="{{ $option->id }}" @selected((int)$patient->estado_id === (int)$option->id)>{{ $option->nombre }}</option>@endforeach</select><button title="Guardar">✓</button>
                </form>
            @else<span>{{ $patient->estado }}</span>@endif
            </td>
        </tr>@empty<tr><td class="empty" colspan="6">Esta programación no tiene pacientes citados.</td></tr>@endforelse</tbody>
    </table></div>
</section>
@endsection
