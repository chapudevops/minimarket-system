<div class="row">
    <div class="col-12 col-xl-8 d-flex">
        <div class="card w-100 rounded-4 border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <h5 class="mb-0 fw-bold">Ventas Mensuales</h5>
                </div>
                <div id="chartVentas" style="height: 350px;"></div>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-4 d-flex">
        <div class="card w-100 rounded-4 border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <h5 class="mb-0 fw-bold">Productos Más Vendidos</h5>
                </div>
                <div class="d-flex flex-column gap-3" id="topProductos">
                    @forelse($productosMasVendidos as $producto)
                        <div class="d-flex align-items-center gap-3">
                            <div class="wh-48 d-flex align-items-center justify-content-center rounded-3 bg-primary bg-opacity-10">
                                @if($producto->foto)
                                    <img src="{{ asset('storage/productos/' . $producto->foto) }}" width="48" height="48" class="rounded-3" style="object-fit: cover;" alt="">
                                @else
                                    <i class="material-icons-outlined text-primary">shopping_bag</i>
                                @endif
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-0 fw-bold text-truncate" style="max-width: 150px;">{{ $producto->descripcion }}</h6>
                                <p class="mb-0 small">Código: {{ $producto->codigo_interno }}</p>
                            </div>
                            <div class="text-end">
                                <h6 class="mb-0">{{ number_format($producto->total_vendido) }} und</h6>
                                <small class="text-muted">S/ {{ number_format($producto->total_monto, 2) }}</small>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4">
                            <i class="material-icons-outlined fs-1 text-muted">shopping_bag</i>
                            <p class="text-muted mt-2">No hay datos de ventas</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-12 col-xl-6 d-flex">
        <div class="card w-100 rounded-4 border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <h5 class="mb-0 fw-bold">Ventas vs Compras (12 meses)</h5>
                </div>
                <div id="chartComparativo" style="height: 300px;"></div>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-6 d-flex">
        <div class="card w-100 rounded-4 border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <h5 class="mb-0 fw-bold">Ventas por Tipo de Comprobante</h5>
                </div>
                <div id="chartTipoVenta" style="height: 300px;"></div>
            </div>
        </div>
    </div>
</div>

@section('scripts')
<script src="{{ URL::asset('build/plugins/apexchart/apexcharts.min.js') }}"></script>
<script>
    var ventasMeses = @json($ventasMensuales['meses']);
    var ventasMontos = @json($ventasMensuales['montosVentas']);
    var comprasMontos = @json($comprasMensuales['montosCompras']);

    // Gráfico de Ventas Mensuales
    var optionsVentas = {
        series: [{ name: 'Ventas', data: ventasMontos }],
        chart: { type: 'area', height: 350, toolbar: { show: true } },
        colors: ['#0d6efd'],
        dataLabels: { enabled: false },
        stroke: { curve: 'smooth', width: 2 },
        fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.7, opacityTo: 0.3 } },
        xaxis: { categories: ventasMeses, title: { text: 'Mes' } },
        yaxis: { title: { text: 'Monto (S/)' }, labels: { formatter: function(v) { return 'S/ ' + v.toFixed(2); } } },
        tooltip: { y: { formatter: function(v) { return 'S/ ' + v.toFixed(2); } } }
    };
    new ApexCharts(document.querySelector("#chartVentas"), optionsVentas).render();

    // Gráfico Comparativo Ventas vs Compras
    var optionsComparativo = {
        series: [
            { name: 'Ventas', data: ventasMontos },
            { name: 'Compras', data: comprasMontos }
        ],
        chart: { type: 'bar', height: 300, toolbar: { show: false } },
        colors: ['#0d6efd', '#dc3545'],
        plotOptions: { bar: { horizontal: false, columnWidth: '55%' } },
        dataLabels: { enabled: false },
        stroke: { show: true, width: 2, colors: ['transparent'] },
        xaxis: { categories: ventasMeses, title: { text: 'Mes' } },
        yaxis: { title: { text: 'Monto (S/)' } },
        tooltip: { y: { formatter: function(v) { return 'S/ ' + v.toFixed(2); } } }
    };
    new ApexCharts(document.querySelector("#chartComparativo"), optionsComparativo).render();

    // Gráfico de Ventas por Tipo de Comprobante
    var tipos = @json($ventasPorTipo->pluck('tipo_comprobante'));
    var totales = @json($ventasPorTipo->pluck('total'));

    var optionsTipo = {
        series: totales.map(function(t) { return parseFloat(t); }),
        chart: { type: 'donut', height: 300 },
        labels: tipos,
        colors: ['#0d6efd', '#198754', '#ffc107', '#dc3545'],
        legend: { position: 'bottom' },
        tooltip: { y: { formatter: function(v) { return 'S/ ' + v.toFixed(2); } } }
    };
    new ApexCharts(document.querySelector("#chartTipoVenta"), optionsTipo).render();
</script>
@endsection
