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

<!-- MAPA DE UBICACIÓN DE LA TIENDA -->
<div class="row">
    <div class="col-12 d-flex">
        <div class="card rounded-4 w-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between mb-3 flex-wrap gap-2">
                    <div class="">
                        <h5 class="mb-0 fw-bold">
                            <i class="bi bi-geo-alt-fill text-danger"></i> 
                            {{ $empresa->nombre_comercial ?? $empresa->razon_social ?? 'Nuestra Ubicación' }}
                        </h5>
                        @if($empresa->link_ubicacion)
                            <small class="text-success d-block mt-1">
                                <i class="bi bi-check-circle"></i> Ubicación configurada en Google Maps
                            </small>
                        @else
                            <small class="text-warning d-block mt-1">
                                <i class="bi bi-exclamation-triangle"></i> No hay ubicación configurada
                            </small>
                        @endif
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <button class="btn btn-sm btn-outline-primary" onclick="openGoogleMaps()">
                            <i class="bi bi-map"></i> Ver en Google Maps
                        </button>
                    </div>
                </div>
                <div id="storeMap" style="height: 450px; width: 100%; border-radius: 10px;"></div>
                <div class="mt-3 text-center">
                    <small class="text-muted">
                        <i class="bi bi-info-circle"></i> La ubicación se carga desde el link configurado en la empresa
                    </small>
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

<!-- Script del Mapa de Google Maps - VERSION CORREGIDA SIN ERROR DEL & -->
<script>
    let map;
    let marker;
    let storeLink = null;
    let storeName = '{{ addslashes($empresa->nombre_comercial ?? $empresa->razon_social ?? "Mi Minimarket") }}';
    let storeLat = -12.046374;
    let storeLng = -77.042793;

    // Abrir en Google Maps
    window.openGoogleMaps = function() {
        if (storeLink) {
            window.open(storeLink, '_blank');
        } else {
            alert('No hay un link de ubicación configurado. Ve a Configuración de Empresa para agregarlo.');
        }
    };

    // Cargar ubicación desde el servidor
    async function loadStoreLocation() {
        try {
            const response = await fetch('{{ route("store.location") }}');
            const data = await response.json();
            
            if (data && !data.error && data.link_ubicacion) {
                storeLink = data.link_ubicacion;
                if (data.lat && data.lng) {
                    storeLat = parseFloat(data.lat);
                    storeLng = parseFloat(data.lng);
                }
                
                if (map && marker) {
                    map.setCenter({ lat: storeLat, lng: storeLng });
                    marker.setPosition({ lat: storeLat, lng: storeLng });
                }
            } else {
                console.warn('No hay link de ubicación configurado');
                const mapDiv = document.getElementById('storeMap');
                if (mapDiv && !storeLink) {
                    mapDiv.innerHTML = '<div class="alert alert-warning text-center p-5">⚠️ No hay una ubicación configurada. Ve a Configuración de Empresa para agregar el link de Google Maps.</div>';
                }
            }
        } catch (error) {
            console.error('Error cargando ubicación:', error);
        }
    }

    // Inicializar mapa
    function initMap() {
        const mapDiv = document.getElementById('storeMap');
        if (!mapDiv) return;
        
        const mapOptions = {
            center: { lat: storeLat, lng: storeLng },
            zoom: 17,
            zoomControl: true,
            streetViewControl: true,
            fullscreenControl: true,
            mapTypeControl: true
        };
        
        map = new google.maps.Map(mapDiv, mapOptions);
        
        marker = new google.maps.Marker({
            position: { lat: storeLat, lng: storeLng },
            map: map,
            title: storeName,
            icon: {
                url: 'https://maps.google.com/mapfiles/ms/icons/red-dot.png',
                scaledSize: new google.maps.Size(50, 50)
            }
        });
        
        const infoWindow = new google.maps.InfoWindow({
            content: '<div style="padding: 12px;">' +
                '<div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">' +
                '<div style="font-size: 24px;">🏪</div>' +
                '<div><h6 style="margin: 0; font-weight: bold;">' + storeName + '</h6></div>' +
                '</div>' +
                '<hr style="margin: 8px 0;">' +
                '<button onclick="openGoogleMaps()" style="width: 100%; padding: 5px; background: #0d6efd; color: white; border: none; border-radius: 5px; cursor: pointer;">📍 Ver en Google Maps</button>' +
                '</div>'
        });
        
        marker.addListener('click', function() {
            infoWindow.open(map, marker);
        });
    }
    
    // Cargar Google Maps SIN callback en la URL para evitar el error del &
    function loadGoogleMapsScript() {
        const existingScript = document.querySelector('script[src*="maps.googleapis.com/maps/api/js"]');
        if (existingScript) {
            existingScript.remove();
        }
        
        const script = document.createElement('script');
        script.src = 'https://maps.googleapis.com/maps/api/js?key=AIzaSyDQKbJK_7JMR45InjGsGuHQcsQ7toEVIf4';
        script.async = true;
        script.defer = true;
        
        script.onload = function() {
            console.log('Google Maps cargado correctamente');
            loadStoreLocation().then(function() {
                initMap();
            }).catch(function() {
                initMap();
            });
        };
        
        script.onerror = function() {
            console.error('Error al cargar Google Maps API');
            const mapDiv = document.getElementById('storeMap');
            if (mapDiv) {
                mapDiv.innerHTML = '<div class="alert alert-danger text-center p-5">Error al cargar el mapa. Verifica tu conexión a Internet.</div>';
            }
        };
        
        document.head.appendChild(script);
    }
    
    // Iniciar
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', loadGoogleMapsScript);
    } else {
        loadGoogleMapsScript();
    }
</script>
@endsection