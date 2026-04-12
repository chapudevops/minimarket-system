@extends('layouts.master')

@section('title', 'Nueva Nota de Crédito')
@section('css')
    <style>
        .product-row {
            background: #f8f9fa;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }
        .remove-product {
            cursor: pointer;
            color: #dc3545;
        }
    </style>
@endsection

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h4 class="mb-0">
                            <i class="bi bi-plus-circle"></i> Nueva Nota de Crédito
                        </h4>
                        <p class="mb-0 text-muted small">Registra una nueva nota de crédito</p>
                    </div>
                    <div class="col text-end">
                        <a href="{{ route('notas-credito.index') }}" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Seleccionar Venta Original</label>
                        <select id="venta_id" class="form-control">
                            <option value="">Seleccionar venta</option>
                            @foreach($ventas as $venta)
                                <option value="{{ $venta->id }}">{{ $venta->documento }} - {{ $venta->cliente->nombre_razon_social ?? 'CLIENTES VARIOS' }} - S/ {{ number_format($venta->total, 2) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Serie</label>
                        <input type="text" id="serie_documento" class="form-control" readonly>
                        <input type="hidden" id="serie" name="serie">
                        <input type="hidden" id="numero" name="numero">
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="form-label fw-bold">Motivo <span class="text-danger">*</span></label>
                        <textarea id="motivo" class="form-control" rows="2" placeholder="Motivo de la nota de crédito..."></textarea>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Tipo de Nota</label>
                        <select id="tipo_nota" class="form-control">
                            <option value="ANULACION">Anulación</option>
                            <option value="DESCUENTO">Descuento</option>
                            <option value="DEVOLUCION">Devolución</option>
                            <option value="OTRO">Otro</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="form-check mt-4">
                            <input type="checkbox" id="detraccion" class="form-check-input" value="1">
                            <label class="form-check-label fw-bold" for="detraccion">Detracción</label>
                        </div>
                    </div>
                </div>

                <hr>

                <h6 class="fw-bold mb-3"><i class="bi bi-box-seam"></i> Productos a Acreditar</h6>
                <div id="productosList" class="mb-3"></div>

                <div class="row">
                    <div class="col-md-8"></div>
                    <div class="col-md-4">
                        <table class="table table-bordered">
                            <tr><td class="fw-bold">OP. Gravadas:</td><td class="text-end" id="subtotal">S/ 0.00</td></tr>
                            <tr><td class="fw-bold">IGV (18%):</td><td class="text-end" id="igv">S/ 0.00</td></tr>
                            <tr class="table-primary"><td class="fw-bold">Total:</td><td class="text-end fw-bold" id="total">S/ 0.00</td></tr>
                        </table>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Observaciones</label>
                    <textarea id="observaciones" class="form-control" rows="2"></textarea>
                </div>

                <div class="text-end">
                    <a href="{{ route('notas-credito.index') }}" class="btn btn-secondary">Cancelar</a>
                    <button type="button" class="btn btn-primary" id="btnGuardar">
                        <i class="bi bi-save"></i> Guardar Nota de Crédito
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
$(document).ready(function() {
    let ventaData = null;
    let productosSeleccionados = [];

    function cargarSerie() {
        $.ajax({
            url: '/notas-credito/serie',
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    $('#serie_documento').val(response.documento);
                    $('#serie').val(response.serie);
                    $('#numero').val(response.numero);
                } else {
                    $('#serie_documento').val(response.message);
                }
            }
        });
    }

    cargarSerie();

    $('#venta_id').change(function() {
        var ventaId = $(this).val();
        if (!ventaId) return;
        
        $.ajax({
            url: '/notas-credito/venta/' + ventaId,
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    ventaData = response.data;
                    productosSeleccionados = ventaData.detalles.map(d => ({
                        ...d,
                        cantidad_original: d.cantidad,
                        cantidad: d.cantidad,
                        selected: true
                    }));
                    renderizarProductos();
                    actualizarTotales();
                }
            }
        });
    });

    function renderizarProductos() {
        let html = '';
        productosSeleccionados.forEach((item, idx) => {
            html += `
                <div class="product-row" data-index="${idx}">
                    <div class="row">
                        <div class="col-md-5">
                            <p class="mb-0"><strong>${item.codigo}</strong> - ${item.producto}</p>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Cantidad Original</label>
                            <p class="mb-0">${item.cantidad_original}</p>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Cantidad a Acreditar</label>
                            <input type="number" class="form-control cantidad-input" data-index="${idx}" value="${item.cantidad}" min="0" max="${item.cantidad_original}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Precio Unitario</label>
                            <input type="number" step="0.01" class="form-control precio-input" data-index="${idx}" value="${item.precio_unitario}">
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">&nbsp;</label>
                            <div>
                                <input type="checkbox" class="form-check-input select-product" data-index="${idx}" ${item.selected ? 'checked' : ''}>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        $('#productosList').html(html);
        
        $('.cantidad-input').on('change', function() {
            const idx = $(this).data('index');
            productosSeleccionados[idx].cantidad = parseInt($(this).val()) || 0;
            actualizarTotales();
        });
        
        $('.precio-input').on('change', function() {
            const idx = $(this).data('index');
            productosSeleccionados[idx].precio_unitario = parseFloat($(this).val()) || 0;
            actualizarTotales();
        });
        
        $('.select-product').on('change', function() {
            const idx = $(this).data('index');
            productosSeleccionados[idx].selected = $(this).is(':checked');
            actualizarTotales();
        });
    }

    function actualizarTotales() {
        let subtotal = 0;
        productosSeleccionados.forEach(item => {
            if (item.selected && item.cantidad > 0) {
                subtotal += item.cantidad * item.precio_unitario;
            }
        });
        const igv = subtotal * 0.18;
        const total = subtotal + igv;
        
        $('#subtotal').text(`S/ ${subtotal.toFixed(2)}`);
        $('#igv').text(`S/ ${igv.toFixed(2)}`);
        $('#total').text(`S/ ${total.toFixed(2)}`);
    }

    $('#btnGuardar').click(function() {
        const detalles = [];
        productosSeleccionados.forEach(item => {
            if (item.selected && item.cantidad > 0) {
                detalles.push({
                    producto_id: item.producto_id,
                    cantidad: item.cantidad,
                    precio_unitario: item.precio_unitario,
                    almacen_id: item.almacen_id
                });
            }
        });
        
        if (detalles.length === 0) {
            alert('Debe seleccionar al menos un producto para acreditar');
            return;
        }
        
        const data = {
            venta_id: $('#venta_id').val(),
            cliente_id: ventaData?.venta.cliente_id,
            motivo: $('#motivo').val(),
            tipo_nota: $('#tipo_nota').val(),
            detraccion: $('#detraccion').is(':checked'),
            observaciones: $('#observaciones').val(),
            detalles: detalles
        };
        
        $.ajax({
            url: '/notas-credito',
            type: 'POST',
            data: JSON.stringify(data),
            contentType: 'application/json',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    window.location.href = '/notas-credito';
                } else {
                    alert(response.message);
                }
            },
            error: function(xhr) {
                alert(xhr.responseJSON?.message || 'Error al guardar la nota de crédito');
            }
        });
    });
});
</script>
@endsection