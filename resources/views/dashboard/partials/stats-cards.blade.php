<div class="row">
    <div class="col-12 col-xl-8 d-flex">
        <div class="card rounded-4 w-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-around flex-wrap gap-4 p-4">
                    <div class="d-flex flex-column align-items-center justify-content-center gap-2">
                        <a href="{{ route('ventas.index') }}" class="mb-2 wh-48 bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center text-decoration-none">
                            <i class="material-icons-outlined">shopping_cart</i>
                        </a>
                        <h3 class="mb-0">{{ number_format($cantidadVentas) }}</h3>
                        <p class="mb-0 fw-semibold">Ventas</p>
                        <small class="text-muted">Hoy: {{ number_format($cantidadVentasHoy) }}</small>
                    </div>
                    <div class="vr"></div>
                    <div class="d-flex flex-column align-items-center justify-content-center gap-2">
                        <a href="{{ route('productos.index') }}" class="mb-2 wh-48 bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center text-decoration-none">
                            <i class="material-icons-outlined">inventory_2</i>
                        </a>
                        <h3 class="mb-0">{{ number_format($totalProductos) }}</h3>
                        <p class="mb-0 fw-semibold">Productos</p>
                        <small class="text-muted text-warning">Bajo stock: {{ count($productosBajoStock) }}</small>
                    </div>
                    <div class="vr"></div>
                    <div class="d-flex flex-column align-items-center justify-content-center gap-2">
                        <a href="{{ route('clientes.index') }}" class="mb-2 wh-48 bg-info bg-opacity-10 text-info rounded-circle d-flex align-items-center justify-content-center text-decoration-none">
                            <i class="material-icons-outlined">people</i>
                        </a>
                        <h3 class="mb-0">{{ number_format($totalClientes) }}</h3>
                        <p class="mb-0 fw-semibold">Clientes</p>
                    </div>
                    <div class="vr"></div>
                    <div class="d-flex flex-column align-items-center justify-content-center gap-2">
                        <a href="{{ route('proveedores.index') }}" class="mb-2 wh-48 bg-warning bg-opacity-10 text-warning rounded-circle d-flex align-items-center justify-content-center text-decoration-none">
                            <i class="material-icons-outlined">local_shipping</i>
                        </a>
                        <h3 class="mb-0">{{ number_format($totalProveedores) }}</h3>
                        <p class="mb-0 fw-semibold">Proveedores</p>
                    </div>
                    <div class="vr"></div>
                    <div class="d-flex flex-column align-items-center justify-content-center gap-2">
                        <a href="{{ route('usuarios.index') }}" class="mb-2 wh-48 bg-danger bg-opacity-10 text-danger rounded-circle d-flex align-items-center justify-content-center text-decoration-none">
                            <i class="material-icons-outlined">admin_panel_settings</i>
                        </a>
                        <h3 class="mb-0">{{ number_format($totalUsuarios) }}</h3>
                        <p class="mb-0 fw-semibold">Usuarios</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-4 d-flex">
        <div class="card rounded-4 w-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <h5 class="mb-0 fw-bold">Estado de Caja</h5>
                </div>
                <div class="text-center">
                    @if($cajaAbierta)
                        <div class="mb-3">
                            <div class="badge bg-success p-3 fs-6 rounded-pill">
                                <i class="bi bi-check-circle"></i> CAJA ABIERTA
                            </div>
                        </div>
                        <div class="text-start">
                            <p class="mb-1"><strong>Monto Inicial:</strong> S/ {{ number_format($cajaAbierta->monto_inicial, 2) }}</p>
                            <p class="mb-1"><strong>Apertura:</strong> {{ \Carbon\Carbon::parse($cajaAbierta->fecha_apertura)->format('d/m/Y H:i') }}</p>
                            <p class="mb-0"><strong>Responsable:</strong> {{ $cajaAbierta->responsable->name ?? auth()->user()->name }}</p>
                        </div>
                        <a href="{{ route('apertura-caja.index') }}" class="btn btn-outline-warning btn-sm mt-3 rounded-pill">
                            <i class="bi bi-cash"></i> Cerrar Caja
                        </a>
                    @else
                        <div class="mb-3">
                            <div class="badge bg-danger p-3 fs-6 rounded-pill">
                                <i class="bi bi-x-circle"></i> CAJA CERRADA
                            </div>
                        </div>
                        <p class="text-muted">No hay una caja abierta actualmente.</p>
                        <a href="{{ route('apertura-caja.index') }}" class="btn btn-primary mt-2 rounded-pill">
                            <i class="bi bi-cash"></i> Abrir Caja
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
