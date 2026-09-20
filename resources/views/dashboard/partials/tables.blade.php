<div class="row">
    <div class="col-12 col-xl-6 d-flex">
        <div class="card rounded-4 w-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <h5 class="mb-0 fw-bold">Últimas Ventas</h5>
                    <a href="{{ route('ventas.index') }}" class="btn btn-sm btn-primary rounded-pill">Ver todas</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
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
                                    <td><span class="badge bg-dark">{{ $venta->tipo_comprobante }} {{ $venta->serie }}-{{ str_pad($venta->numero, 8, '0', STR_PAD_LEFT) }}</span></td>
                                    <td>{{ $venta->cliente->nombre_razon_social ?? 'CLIENTES VARIOS' }}</td>
                                    <td class="fw-bold text-success">S/ {{ number_format($venta->total, 2) }}</td>
                                    <td><small>{{ $venta->fecha_emision->format('d/m/Y H:i') }}</small></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4">
                                        <i class="material-icons-outlined text-muted">receipt_long</i>
                                        <p class="text-muted mt-2 mb-0">No hay ventas registradas</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-6 d-flex">
        <div class="card rounded-4 w-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <h5 class="mb-0 fw-bold">Últimos Gastos</h5>
                    <a href="{{ route('gastos.index') }}" class="btn btn-sm btn-primary rounded-pill">Ver todos</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
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
                                    <td><small>{{ \Carbon\Carbon::parse($gasto->fecha_emision)->format('d/m/Y') }}</small></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4">
                                        <i class="material-icons-outlined text-muted">receipt</i>
                                        <p class="text-muted mt-2 mb-0">No hay gastos registrados</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-12 d-flex">
        <div class="card rounded-4 w-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <h5 class="mb-0 fw-bold">Resumen del Período</h5>
                </div>
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card bg-primary bg-opacity-10 border-0">
                            <div class="card-body text-center">
                                <h6 class="text-primary">Total Ventas</h6>
                                <h3 class="text-primary fw-bold">S/ {{ number_format($totalVentas, 2) }}</h3>
                                <small>{{ $cantidadVentas }} transacciones</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-danger bg-opacity-10 border-0">
                            <div class="card-body text-center">
                                <h6 class="text-danger">Total Compras</h6>
                                <h3 class="text-danger fw-bold">S/ {{ number_format($totalCompras, 2) }}</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning bg-opacity-10 border-0">
                            <div class="card-body text-center">
                                <h6 class="text-warning">Total Gastos</h6>
                                <h3 class="text-warning fw-bold">S/ {{ number_format($totalGastos, 2) }}</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success bg-opacity-10 border-0">
                            <div class="card-body text-center">
                                <h6 class="text-success">Resultado Operativo</h6>
                                <h3 class="text-success fw-bold">S/ {{ number_format($beneficioNeto, 2) }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
