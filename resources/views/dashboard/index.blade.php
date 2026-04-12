@extends('layouts.master')

@section('title', 'Dashboard - Minimarket')

@section('content')
<x-page-title title="Dashboard" pagetitle="Minimarket" />

<!-- Filtros de fecha -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('home') }}" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label fw-bold">Fecha Inicio</label>
                <input type="date" name="fecha_inicio" class="form-control" value="{{ $fechaInicio }}">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold">Fecha Fin</label>
                <input type="date" name="fecha_fin" class="form-control" value="{{ $fechaFin }}">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-funnel"></i> Filtrar
                </button>
            </div>
        </form>
    </div>
</div>

<div class="row">
    <!-- Tarjeta de Ventas -->
    <div class="col-12 col-xl-3 d-flex">
        <div class="card rounded-4 w-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-2">
                    <div class="">
                        <h2 class="mb-0">S/ {{ number_format($totalVentas, 2) }}</h2>
                    </div>
                    <div class="">
                        <p class="dash-lable d-flex align-items-center gap-1 rounded mb-0 {{ $porcentajeCambio >= 0 ? 'bg-success text-success' : 'bg-danger text-danger' }} bg-opacity-10">
                            <span class="material-icons-outlined fs-6">{{ $porcentajeCambio >= 0 ? 'arrow_upward' : 'arrow_downward' }}</span>
                            {{ number_format(abs($porcentajeCambio), 1) }}%
                        </p>
                    </div>
                </div>
                <p class="mb-0">Total Ventas</p>
                <small class="text-muted">Hoy: S/ {{ number_format($totalVentasHoy, 2) }}</small>
            </div>
        </div>
    </div>
    
    <!-- Tarjeta de Compras -->
    <div class="col-12 col-xl-3 d-flex">
        <div class="card rounded-4 w-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-2">
                    <div class="">
                        <h2 class="mb-0">S/ {{ number_format($totalCompras, 2) }}</h2>
                    </div>
                </div>
                <p class="mb-0">Total Compras</p>
                <small class="text-muted">Hoy: S/ {{ number_format($totalComprasHoy, 2) }}</small>
            </div>
        </div>
    </div>
    
    <!-- Tarjeta de Gastos -->
    <div class="col-12 col-xl-3 d-flex">
        <div class="card rounded-4 w-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-2">
                    <div class="">
                        <h2 class="mb-0">S/ {{ number_format($totalGastos, 2) }}</h2>
                    </div>
                </div>
                <p class="mb-0">Total Gastos</p>
                <small class="text-muted">Hoy: S/ {{ number_format($totalGastosHoy, 2) }}</small>
            </div>
        </div>
    </div>
    
    <!-- Tarjeta de Beneficio Neto -->
    <div class="col-12 col-xl-3 d-flex">
        <div class="card rounded-4 w-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-2">
                    <div class="">
                        <h2 class="mb-0 text-success">S/ {{ number_format($beneficioNeto, 2) }}</h2>
                    </div>
                </div>
                <p class="mb-0">Beneficio Neto</p>
                <small class="text-muted">Hoy: S/ {{ number_format($beneficioNetoHoy, 2) }}</small>
            </div>
        </div>
    </div>
</div><!--end row-->

<div class="row">
    <!-- Tarjetas de métricas -->
    <div class="col-12 col-xl-8 d-flex">
        <div class="card rounded-4 w-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-around flex-wrap gap-4 p-4">
                    <div class="d-flex flex-column align-items-center justify-content-center gap-2">
                        <a href="javascript:;" class="mb-2 wh-48 bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center">
                            <i class="material-icons-outlined">shopping_cart</i>
                        </a>
                        <h3 class="mb-0">{{ number_format($cantidadVentas) }}</h3>
                        <p class="mb-0">Ventas</p>
                        <small class="text-muted">Hoy: {{ number_format($cantidadVentasHoy) }}</small>
                    </div>
                    <div class="vr"></div>
                    <div class="d-flex flex-column align-items-center justify-content-center gap-2">
                        <a href="javascript:;" class="mb-2 wh-48 bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center">
                            <i class="material-icons-outlined">inventory_2</i>
                        </a>
                        <h3 class="mb-0">{{ number_format($totalProductos) }}</h3>
                        <p class="mb-0">Productos</p>
                        <small class="text-muted text-warning">Bajo stock: {{ $productosBajoStock }}</small>
                    </div>
                    <div class="vr"></div>
                    <div class="d-flex flex-column align-items-center justify-content-center gap-2">
                        <a href="javascript:;" class="mb-2 wh-48 bg-info bg-opacity-10 text-info rounded-circle d-flex align-items-center justify-content-center">
                            <i class="material-icons-outlined">people</i>
                        </a>
                        <h3 class="mb-0">{{ number_format($totalClientes) }}</h3>
                        <p class="mb-0">Clientes</p>
                    </div>
                    <div class="vr"></div>
                    <div class="d-flex flex-column align-items-center justify-content-center gap-2">
                        <a href="javascript:;" class="mb-2 wh-48 bg-warning bg-opacity-10 text-warning rounded-circle d-flex align-items-center justify-content-center">
                            <i class="material-icons-outlined">local_shipping</i>
                        </a>
                        <h3 class="mb-0">{{ number_format($totalProveedores) }}</h3>
                        <p class="mb-0">Proveedores</p>
                    </div>
                    <div class="vr"></div>
                    <div class="d-flex flex-column align-items-center justify-content-center gap-2">
                        <a href="javascript:;" class="mb-2 wh-48 bg-danger bg-opacity-10 text-danger rounded-circle d-flex align-items-center justify-content-center">
                            <i class="material-icons-outlined">admin_panel_settings</i>
                        </a>
                        <h3 class="mb-0">{{ number_format($totalUsuarios) }}</h3>
                        <p class="mb-0">Usuarios</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Estado de Caja -->
    <div class="col-12 col-xl-4 d-flex">
        <div class="card rounded-4 w-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <div class="">
                        <h5 class="mb-0 fw-bold">Estado de Caja</h5>
                    </div>
                </div>
                <div class="text-center">
                    @if($cajaAbierta)
                        <div class="mb-3">
                            <div class="badge bg-success p-3 fs-6">
                                <i class="bi bi-check-circle"></i> CAJA ABIERTA
                            </div>
                        </div>
                        <p><strong>Monto Inicial:</strong> S/ {{ number_format($cajaAbierta->monto_inicial, 2) }}</p>
                        <p><strong>Fecha Apertura:</strong> {{ $cajaAbierta->fecha_apertura->format('d/m/Y H:i') }}</p>
                        <p><strong>Responsable:</strong> {{ $cajaAbierta->responsable->name ?? auth()->user()->name }}</p>
                    @else
                        <div class="mb-3">
                            <div class="badge bg-danger p-3 fs-6">
                                <i class="bi bi-x-circle"></i> CAJA CERRADA
                            </div>
                        </div>
                        <p>No hay una caja abierta actualmente.</p>
                        <a href="{{ route('apertura-caja.index') }}" class="btn btn-primary mt-2">
                            <i class="bi bi-cash"></i> Abrir Caja
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Gráfico de Ventas Mensuales -->
    <div class="col-12 col-xl-8 d-flex">
        <div class="card w-100 rounded-4">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <div class="">
                        <h5 class="mb-0 fw-bold">Ventas Mensuales</h5>
                    </div>
                </div>
                <div id="chartVentas" style="height: 350px;"></div>
            </div>
        </div>
    </div>
    
    <!-- Productos Más Vendidos -->
    <div class="col-12 col-xl-4 d-flex">
        <div class="card w-100 rounded-4">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <div class="">
                        <h5 class="mb-0 fw-bold">Productos Más Vendidos</h5>
                    </div>
                </div>
                <div class="d-flex flex-column gap-3">
                    @forelse($productosMasVendidos as $producto)
                        <div class="d-flex align-items-center gap-3">
                            <div class="wh-48 d-flex align-items-center justify-content-center rounded-3 bg-primary bg-opacity-10">
                                <i class="material-icons-outlined text-primary">shopping_bag</i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-0 fw-bold">{{ $producto->descripcion }}</h6>
                                <p class="mb-0 small">Código: {{ $producto->codigo_interno }}</p>
                            </div>
                            <div class="">
                                <h6 class="mb-0">{{ number_format($producto->total_vendido) }} und</h6>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4">
                            <p class="text-muted">No hay datos de ventas</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Últimas Ventas -->
    <div class="col-12 col-xl-6 d-flex">
        <div class="card rounded-4 w-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <div class="">
                        <h5 class="mb-0 fw-bold">Últimas Ventas</h5>
                    </div>
                    <a href="{{ route('ventas.index') }}" class="btn btn-sm btn-primary">Ver todas</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Documento</th>
                                <th>Cliente</th>
                                <th>Total</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($ultimasVentas as $venta)
                                <tr>
                                    <td>{{ $venta->documento }}</td>
                                    <td>{{ $venta->cliente->nombre_razon_social ?? 'CLIENTES VARIOS' }}</td>
                                    <td class="fw-bold">S/ {{ number_format($venta->total, 2) }}</td>
                                    <td>{{ $venta->fecha_emision->format('d/m/Y H:i') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center">No hay ventas registradas</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Últimos Gastos -->
    <div class="col-12 col-xl-6 d-flex">
        <div class="card rounded-4 w-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <div class="">
                        <h5 class="mb-0 fw-bold">Últimos Gastos</h5>
                    </div>
                    <a href="{{ route('gastos.index') }}" class="btn btn-sm btn-primary">Ver todos</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Motivo</th>
                                <th>Cuenta</th>
                                <th>Monto</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($ultimosGastos as $gasto)
                                <tr>
                                    <td>{{ $gasto->motivo }}</td>
                                    <td>{{ $gasto->cuenta }}</td>
                                    <td class="fw-bold text-danger">S/ {{ number_format($gasto->monto, 2) }}</td>
                                    <td>{{ $gasto->fecha_emision->format('d/m/Y') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center">No hay gastos registrados</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Resumen de Ventas vs Compras vs Gastos -->
    <div class="col-12 col-xl-12 d-flex">
        <div class="card rounded-4 w-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <div class="">
                        <h5 class="mb-0 fw-bold">Resumen del Período</h5>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="card bg-success bg-opacity-10 border-0">
                            <div class="card-body text-center">
                                <h6 class="text-success">Total Ventas</h6>
                                <h3 class="text-success">S/ {{ number_format($totalVentas, 2) }}</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-danger bg-opacity-10 border-0">
                            <div class="card-body text-center">
                                <h6 class="text-danger">Total Compras</h6>
                                <h3 class="text-danger">S/ {{ number_format($totalCompras, 2) }}</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-warning bg-opacity-10 border-0">
                            <div class="card-body text-center">
                                <h6 class="text-warning">Total Gastos</h6>
                                <h3 class="text-warning">S/ {{ number_format($totalGastos, 2) }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="text-center mt-3">
                    <div class="card bg-primary bg-opacity-10 border-0">
                        <div class="card-body">
                            <h6 class="text-primary">Beneficio Neto</h6>
                            <h2 class="text-primary">S/ {{ number_format($beneficioNeto, 2) }}</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection 

@section('scripts')
<script src="{{ URL::asset('build/plugins/apexchart/apexcharts.min.js') }}"></script>
<script>
    // Gráfico de Ventas Mensuales
    var options = {
        series: [{
            name: 'Ventas',
            data: {{ json_encode($montosVentas) }}
        }],
        chart: {
            type: 'area',
            height: 350,
            toolbar: {
                show: true
            }
        },
        title: {
            text: 'Ventas por Mes',
            align: 'left'
        },
        colors: ['#0d6efd'],
        dataLabels: {
            enabled: false
        },
        stroke: {
            curve: 'smooth',
            width: 2
        },
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.7,
                opacityTo: 0.3,
                stops: [0, 90, 100]
            }
        },
        xaxis: {
            categories: {{ json_encode($meses) }},
            title: {
                text: 'Mes'
            }
        },
        yaxis: {
            title: {
                text: 'Monto (S/)'
            },
            labels: {
                formatter: function(value) {
                    return 'S/ ' + value.toFixed(2);
                }
            }
        },
        tooltip: {
            y: {
                formatter: function(value) {
                    return 'S/ ' + value.toFixed(2);
                }
            }
        }
    };

    var chart = new ApexCharts(document.querySelector("#chartVentas"), options);
    chart.render();
</script>
@endsection