@if(count($alertas) > 0)
<div class="row mb-3">
    <div class="col-12">
        <div class="card rounded-4 border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="bi bi-bell-fill text-warning"></i>
                    <h6 class="mb-0 fw-bold">Alertas del Sistema</h6>
                </div>
                <div class="d-flex flex-column gap-2">
                    @foreach($alertas as $alerta)
                    <a href="{{ $alerta['ruta'] }}" class="text-decoration-none">
                        <div class="alert alert-{{ $alerta['color'] }} alert-dismissible fade show py-2 mb-0 d-flex align-items-center gap-2" role="alert">
                            <span class="material-icons-outlined fs-5">{{ $alerta['icono'] }}</span>
                            <span>{{ $alerta['mensaje'] }}</span>
                        </div>
                    </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endif
