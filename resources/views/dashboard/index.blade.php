@extends('layouts.master')

@section('title', 'Dashboard')

@section('css')
<style>
    /*
       El panel de alertas se desplaza por dentro en vez de estirar el
       dashboard. La altura es un tope, no una altura fija: con dos alertas la
       tarjeta mide dos alertas y no aparece scroll.
    */
    .alertas-scroll {
        max-height: 420px;
        overflow-y: auto;
        overflow-x: hidden;
        /* Aire para que la barra no se monte sobre el texto de las alertas. */
        padding-right: .25rem;
        /* Evita que el gesto de scroll siga arrastrando la pagina al llegar
           al final de la lista, que en movil se siente como un salto. */
        overscroll-behavior: contain;
        -webkit-overflow-scrolling: touch;
    }

    /* Misma barra fina que ya usa el terminal POS, para no inventar un estilo
       de scrollbar distinto en cada pantalla. */
    .alertas-scroll::-webkit-scrollbar { width: 6px; }
    .alertas-scroll::-webkit-scrollbar-track { background: rgba(0, 0, 0, .05); border-radius: 10px; }
    .alertas-scroll::-webkit-scrollbar-thumb { background: rgba(0, 0, 0, .2); border-radius: 10px; }
    .alertas-scroll::-webkit-scrollbar-thumb:hover { background: rgba(0, 0, 0, .35); }
    .alertas-scroll { scrollbar-width: thin; }

    /* Sin esto un mensaje largo estira el flex y saca scroll horizontal en
       lugar de partirse en varias lineas. */
    .alertas-scroll .min-w-0 { min-width: 0; }

    /* En movil la pantalla es mas corta: 420px de alertas se comerian casi
       todo el viewport antes de llegar a las metricas. */
    @media (max-width: 575.98px) {
        .alertas-scroll { max-height: 260px; }
    }
</style>
@endsection

@section('content')
<x-page-title title="Dashboard" :pagetitle="\App\Marca::nombre()" />

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
