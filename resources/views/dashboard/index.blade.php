@extends('layouts.master')

@section('title', 'Dashboard - Minimarket')

@section('content')
<x-page-title title="Dashboard" pagetitle="Minimarket" />

<!-- Filtros de fecha -->
<div class="card mb-3 rounded-4 border-0 shadow-sm">
    <div class="card-body">
        <form method="GET" action="{{ route('home') }}" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label fw-semibold"><i class="bi bi-calendar me-1"></i>Fecha Inicio</label>
                <input type="date" name="fecha_inicio" class="form-control" value="{{ $fechaInicio }}">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold"><i class="bi bi-calendar me-1"></i>Fecha Fin</label>
                <input type="date" name="fecha_fin" class="form-control" value="{{ $fechaFin }}">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100 rounded-pill">
                    <i class="bi bi-funnel"></i> Filtrar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Alertas del sistema -->
@include('dashboard.partials.alerts')

<!-- Tarjetas de métricas principales -->
@include('dashboard.partials.metric-cards')

<!-- Tarjetas de estadísticas + Caja -->
@include('dashboard.partials.stats-cards')

<!-- Gráficos -->
@include('dashboard.partials.charts')

<!-- Tablas -->
@include('dashboard.partials.tables')

<!-- Mapa -->
@include('dashboard.partials.map')

@endsection
