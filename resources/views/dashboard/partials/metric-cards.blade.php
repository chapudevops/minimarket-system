<style>
.metric-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    cursor: default;
}
.metric-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 24px rgba(0,0,0,0.1) !important;
}
.metric-icon {
    transition: transform 0.2s ease;
}
.metric-card:hover .metric-icon {
    transform: scale(1.1);
}
</style>

<div class="row g-3">
    <div class="col-12 col-xl-3 d-flex">
        <div class="card metric-card rounded-4 w-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-2">
                    <div class="metric-icon wh-48 rounded-3 bg-primary bg-opacity-10 d-flex align-items-center justify-content-center">
                        <span class="material-icons-outlined text-primary">shopping_cart</span>
                    </div>
                    <div class="">
                        <h2 class="mb-0">S/ {{ number_format($totalVentas, 2) }}</h2>
                    </div>
                    <div class="">
                        <p class="dash-lable d-flex align-items-center gap-1 rounded mb-0 {{ $porcentajeCambio >= 0 ? 'bg-success text-success' : 'bg-danger text-danger' }} bg-opacity-10 px-2 py-1">
                            <span class="material-icons-outlined fs-6">{{ $porcentajeCambio >= 0 ? 'arrow_upward' : 'arrow_downward' }}</span>
                            {{ number_format(abs($porcentajeCambio), 1) }}%
                        </p>
                    </div>
                </div>
                <p class="mb-0 fw-semibold">Total Ventas</p>
                <small class="text-muted">Hoy: S/ {{ number_format($totalVentasHoy, 2) }}</small>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-3 d-flex">
        <div class="card metric-card rounded-4 w-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-2">
                    <div class="metric-icon wh-48 rounded-3 bg-danger bg-opacity-10 d-flex align-items-center justify-content-center">
                        <span class="material-icons-outlined text-danger">shopping_bag</span>
                    </div>
                    <div class="">
                        <h2 class="mb-0">S/ {{ number_format($totalCompras, 2) }}</h2>
                    </div>
                </div>
                <p class="mb-0 fw-semibold">Total Compras</p>
                <small class="text-muted">Hoy: S/ {{ number_format($totalComprasHoy, 2) }}</small>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-3 d-flex">
        <div class="card metric-card rounded-4 w-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-2">
                    <div class="metric-icon wh-48 rounded-3 bg-warning bg-opacity-10 d-flex align-items-center justify-content-center">
                        <span class="material-icons-outlined text-warning">money_off</span>
                    </div>
                    <div class="">
                        <h2 class="mb-0">S/ {{ number_format($totalGastos, 2) }}</h2>
                    </div>
                </div>
                <p class="mb-0 fw-semibold">Total Gastos</p>
                <small class="text-muted">Hoy: S/ {{ number_format($totalGastosHoy, 2) }}</small>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-3 d-flex">
        <div class="card metric-card rounded-4 w-100 border-0 shadow-sm border-start border-success border-3">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-2">
                    <div class="metric-icon wh-48 rounded-3 bg-success bg-opacity-10 d-flex align-items-center justify-content-center">
                        <span class="material-icons-outlined text-success">trending_up</span>
                    </div>
                    <div class="">
                        <h2 class="mb-0 text-success fw-bold">S/ {{ number_format($beneficioNeto, 2) }}</h2>
                    </div>
                </div>
                {{-- Se llamaba "Beneficio Neto", pero lo que se calcula es
                     utilidad bruta menos gastos operativos: eso es el
                     RESULTADO OPERATIVO. El beneficio neto llevaria ademas
                     impuestos y financieros, que el sistema no maneja. --}}
                <p class="mb-0 fw-semibold">Resultado Operativo</p>
                <small class="text-muted">Hoy: S/ {{ number_format($beneficioNetoHoy, 2) }}</small>
            </div>
        </div>
    </div>
</div>
