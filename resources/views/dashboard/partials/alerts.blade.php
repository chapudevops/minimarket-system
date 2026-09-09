{{--
    Panel de alertas del dashboard.

    El scroll vive DENTRO del cuerpo, no en la tarjeta: el encabezado y el
    contador quedan fijos mientras la lista se desplaza, y el dashboard deja de
    estirarse cuando hay muchas alertas.

    La lista que llega ya viene acotada y ordenada por prioridad desde
    DashboardService::getAlertas(); aca no se filtra ni se reordena nada.
--}}
@php
    $items = $alertas['items'] ?? [];
    $totalAlertas = $alertas['total'] ?? count($items);
@endphp

<div class="row mb-3">
    <div class="col-12">
        <div class="card rounded-4 border-0 shadow-sm">
            {{-- Encabezado fijo: queda fuera del contenedor con scroll. --}}
            <div class="card-body pb-2 pt-3">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <i class="bi bi-bell-fill text-warning"></i>
                    <h6 class="mb-0 fw-bold">Alertas del Sistema</h6>

                    @if($totalAlertas > 0)
                        <span class="badge bg-danger rounded-pill">{{ $totalAlertas }}</span>
                    @endif

                    @if($totalAlertas > count($items))
                        <small class="text-muted ms-auto">
                            Mostrando {{ count($items) }} de {{ $totalAlertas }}
                        </small>
                    @endif
                </div>
            </div>

            <div class="card-body pt-2">
                @forelse($items as $alerta)
                    @if($loop->first)
                        {{-- max-height + overflow interno: el panel nunca pasa de
                             esta altura, sin importar cuantas alertas lleguen. --}}
                        <div class="d-flex flex-column gap-2 alertas-scroll">
                    @endif

                    <a href="{{ $alerta['ruta'] }}" class="text-decoration-none">
                        <div class="alert alert-{{ $alerta['color'] }} py-2 mb-0 d-flex align-items-center gap-2 flex-nowrap" role="alert">
                            <span class="material-icons-outlined fs-5 flex-shrink-0">{{ $alerta['icono'] }}</span>

                            {{-- min-width:0 deja que el texto se parta en movil en
                                 vez de estirar la fila y sacar scroll horizontal. --}}
                            <span class="flex-grow-1 min-w-0 text-break">{{ $alerta['mensaje'] }}</span>

                            @if(!empty($alerta['accion']))
                                <small class="d-none d-sm-inline flex-shrink-0 text-decoration-underline">
                                    {{ $alerta['accion'] }}
                                </small>
                            @endif
                        </div>
                    </a>

                    @if($loop->last)
                        </div>
                    @endif
                @empty
                    <p class="mb-0 text-muted d-flex align-items-center gap-2">
                        <i class="bi bi-check-circle-fill text-success"></i>
                        No hay alertas pendientes.
                    </p>
                @endforelse
            </div>
        </div>
    </div>
</div>
