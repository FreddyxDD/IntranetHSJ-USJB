@extends('layouts.appointments')

@section('title', 'Reportes de citas')

@section('content')
<section class="page-heading"><div><p class="eyebrow">Indicadores consolidados</p><h1>Reporte mensual de citas</h1><p>Distribución por especialidad y estado registrado en SIGH.</p></div></section>
<form class="filters card" method="GET"><label>Mes<input type="month" name="mes" value="{{ $month }}"></label><button class="button">Actualizar</button></form>
<section class="metrics">
    <article><span>Programaciones</span><strong>{{ number_format($totals['programaciones']) }}</strong></article><article><span>Total citas</span><strong>{{ number_format($totals['citas']) }}</strong></article><article><span>Atendidas</span><strong>{{ number_format($totals['atendidas']) }}</strong></article><article><span>Anuladas</span><strong>{{ number_format($totals['anuladas']) }}</strong></article><article><span>Adicionales</span><strong>{{ number_format($totals['adicionales']) }}</strong></article>
</section>
<section class="card table-card"><div class="table-title"><h2>Especialidades</h2><span>{{ $rows->count() }} resultados</span></div><div class="table-scroll"><table>
    <thead><tr><th>Especialidad</th><th>Programaciones</th><th>Citas</th><th>Atendidas</th><th>Anuladas</th><th>Adicionales</th><th>% atención</th></tr></thead>
    <tbody>@forelse($rows as $row)<tr><td><strong>{{ $row->especialidad }}</strong></td><td>{{ $row->programaciones }}</td><td>{{ $row->total_citas }}</td><td>{{ $row->atendidas }}</td><td>{{ $row->anuladas }}</td><td>{{ $row->adicionales }}</td><td>{{ $row->total_citas ? number_format(($row->atendidas / $row->total_citas) * 100, 1) : '0.0' }}%</td></tr>@empty<tr><td class="empty" colspan="7">No hay información para el mes seleccionado.</td></tr>@endforelse</tbody>
</table></div></section>
@endsection
