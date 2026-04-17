@extends('layouts.master')

@section('title', 'Nueva Nota de Crédito')
@section('css')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
    <style>
        .product-row {
            background: #f8f9fa;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 8px;
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
        }
        .product-row:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            background: #fff;
        }
        .product-row.producto-seleccionado {
            background: #e8f5e9;
            border-left: 4px solid #4caf50;
        }
        .select2-container--bootstrap-5 .select2-selection {
            border-radius: 8px !important;
            border: 1px solid #ced4da !important;
        }
        .select2-container--bootstrap-5.select2-container--focus .select2-selection,
        .select2-container--bootstrap-5.select2-container--open .select2-selection {
            border-color: #86b7fe !important;
            box-shadow: 0 0 0 0.25rem rgba(13,110,253,0.25) !important;
        }
        .info-venta-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .info-venta-card small {
            color: rgba(255,255,255,0.8);
        }
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
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
                            <i class="bi bi-receipt-cutoff"></i> Nueva Nota de Crédito
                        </h4>
                        <p class="mb-0 text-muted small">Registra una nueva nota de crédito electrónica</p>
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
                        <label class="form-label fw-bold">
                            <i class="bi bi-receipt"></i> Seleccionar Venta Original
                        </label>
                        <select id="venta_id" class="form-control" style="width: 100%;">
                            <option value="">🔍 Buscar venta por documento o cliente...</option>
                            @foreach($ventas as $venta)
                                <option value="{{ $venta->id }}" 
                                    data-cliente="{{ $venta->cliente->nombre_razon_social ?? 'CLIENTES VARIOS' }}"
                                    data-documento="{{ $venta->documento }}"
                                    data-total="{{ $venta->total }}"
                                    data-fecha="{{ $venta->fecha_emision->format('d/m/Y') }}">
                                    {{ $venta->documento }} - {{ $venta->cliente->nombre_razon_social ?? 'CLIENTES VARIOS' }} - S/ {{ number_format($venta->total, 2) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label fw-bold">Serie del Documento</label>
                        <input type="text" id="serie_documento" class="form-control" readonly>
                        <input type="hidden" id="serie" name="serie">
                        <input type="hidden" id="numero" name="numero">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label fw-bold">Fecha de Emisión</label>
                        <input type="date" id="fecha_emision" class="form-control" value="{{ date('Y-m-d') }}" readonly>
                    </div>
                </div>

                <!-- Info de la Venta Seleccionada -->
                <div id="infoVentaContainer" style="display: none;">
                    <div class="info-venta-card">
                        <div class="row">
                            <div class="col-md-4">
                                <small>DOCUMENTO</small>
                                <p class="mb-0 fw-bold" id="infoDocumento">-</p>
                            </div>
                            <div class="col-md-4">
                                <small>CLIENTE</small>
                                <p class="mb-0 fw-bold" id="infoCliente">-</p>
                            </div>
                            <div class="col-md-4">
                                <small>FECHA VENTA</small>
                                <p class="mb-0 fw-bold" id="infoFechaVenta">-</p>
                            </div>
                        </div>
                        <hr class="my-2" style="border-color: rgba(255,255,255,0.2);">
                        <div class="row">
                            <div class="col-md-4">
                                <small>TOTAL VENTA</small>
                                <p class="mb-0 fw-bold" id="infoTotalVenta">-</p>
                            </div>
                            <div class="col-md-4">
                                <small>MONTO A ACREDITAR</small>
                                <p class="mb-0 fw-bold" id="infoMontoAcreditar">S/ 0.00</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label class="form-label fw-bold">Motivo <span class="text-danger">*</span></label>
                        <textarea id="motivo" class="form-control" rows="2" placeholder="Describa el motivo de la nota de crédito..."></textarea>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Tipo de Nota</label>
                        <select id="tipo_nota" class="form-control">
                            <option value="ANULACION">🚫 Anulación de operación</option>
                            <option value="DESCUENTO">🏷️ Descuento por volumen</option>
                            <option value="DEVOLUCION">📦 Devolución de mercadería</option>
                            <option value="OTRO">📝 Otros conceptos</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="form-check mt-4">
                            <input type="checkbox" id="detraccion" class="form-check-input" value="1">
                            <label class="form-check-label fw-bold" for="detraccion">
                                <i class="bi bi-calculator"></i> Aplicar detracción
                            </label>
                        </div>
                    </div>
                </div>

                <hr>

                <h6 class="fw-bold mb-3">
                    <i class="bi bi-box-seam"></i> Productos a Acreditar
                </h6>
                <div id="productosList" class="mb-3">
                    <div class="alert alert-info text-center">
                        <i class="bi bi-info-circle"></i> Seleccione una venta para ver los productos
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-8"></div>
                    <div class="col-md-4">
                        <div class="card bg-light">
                            <div class="card-body">
                                <table class="table table-borderless mb-0">
                                    <tr>
                                        <td class="fw-bold">OP. Gravadas:</td>
                                        <td class="text-end" id="subtotal">S/ 0.00</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">IGV (18%):</td>
                                        <td class="text-end" id="igv">S/ 0.00</td>
                                    </tr>
                                    <tr class="table-primary">
                                        <td class="fw-bold fs-5">Total a Acreditar:</td>
                                        <td class="text-end fw-bold fs-5" id="total">S/ 0.00</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-3 mt-3">
                    <label class="form-label fw-bold">Observaciones</label>
                    <textarea id="observaciones" class="form-control" rows="2" placeholder="Observaciones adicionales..."></textarea>
                </div>

                <div class="text-end">
                    <a href="{{ route('notas-credito.index') }}" class="btn btn-secondary">
                        <i class="bi bi-x-circle"></i> Cancelar
                    </a>
                    <button type="button" class="btn btn-primary" id="btnGuardar">
                        <i class="bi bi-save"></i> Guardar Nota de Crédito
                    </button>
                    <button type="button" class="btn btn-primary" id="btnLoading" style="display: none;" disabled>
                        <span class="spinner-border spinner-border-sm me-2"></span> Procesando...
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    let ventaData = null;
    let productosSeleccionados = [];

    // Select2 para venta
    $('#venta_id').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: '🔍 Buscar venta por documento o cliente...',
        allowClear: true,
        language: {
            noResults: function() {
                return "No se encontraron ventas";
            }
        }
    });

    // Cargar serie del documento
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
                    $('#serie_documento').val(response.message || 'Error al cargar serie');
                }
            },
            error: function() {
                $('#serie_documento').val('Error al cargar serie');
            }
        });
    }
    cargarSerie();

    // Cuando se selecciona una venta
    $('#venta_id').on('change', function() {
        const ventaId = $(this).val();
        
        if (!ventaId) {
            $('#infoVentaContainer').hide();
            $('#productosList').html('<div class="alert alert-info text-center"><i class="bi bi-info-circle"></i> Seleccione una venta para ver los productos</div>');
            productosSeleccionados = [];
            actualizarTotales();
            return;
        }
        
        // Mostrar información básica de la venta seleccionada
        const selectedOption = $('#venta_id option:selected');
        const cliente = selectedOption.data('cliente') || 'CLIENTES VARIOS';
        const documento = selectedOption.data('documento') || '-';
        const totalVenta = selectedOption.data('total') || 0;
        const fechaVenta = selectedOption.data('fecha') || '-';
        
        $('#infoDocumento').text(documento);
        $('#infoCliente').text(cliente);
        $('#infoTotalVenta').text('S/ ' + parseFloat(totalVenta).toFixed(2));
        $('#infoFechaVenta').text(fechaVenta);
        $('#infoVentaContainer').show();
        
        // Cargar detalles de la venta
        $('#productosList').html(`
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <p class="mt-2 text-muted">Cargando productos de la venta...</p>
            </div>
        `);
        
        $.ajax({
            url: '/notas-credito/venta/' + ventaId,
            type: 'GET',
            dataType: 'json',
            timeout: 30000,
            success: function(response) {
                console.log('Respuesta del servidor:', response); // Para debugging
                
                if (response.success && response.data) {
                    ventaData = response.data;
                    
                    // Verificar si tiene detalles
                    if (response.data.detalles && response.data.detalles.length > 0) {
                        productosSeleccionados = response.data.detalles.map(d => ({
                            producto_id: d.producto_id,
                            producto: d.producto_descripcion || d.producto?.descripcion || 'Producto',
                            codigo: d.codigo_interno || d.producto?.codigo_interno || '-',
                            cantidad_original: d.cantidad,
                            cantidad: d.cantidad,
                            precio_unitario: parseFloat(d.precio_unitario) || 0,
                            almacen_id: d.almacen_id || 1,
                            selected: true
                        }));
                        renderizarProductos();
                        actualizarTotales();
                    } else {
                        $('#productosList').html('<div class="alert alert-warning text-center"><i class="bi bi-exclamation-triangle"></i> Esta venta no tiene productos asociados</div>');
                        productosSeleccionados = [];
                        actualizarTotales();
                    }
                } else {
                    $('#productosList').html(`<div class="alert alert-danger text-center"><i class="bi bi-exclamation-octagon"></i> ${response.message || 'Error al cargar los productos'}</div>`);
                    productosSeleccionados = [];
                    actualizarTotales();
                }
            },
            error: function(xhr, status, error) {
                console.error('Error AJAX:', status, error);
                console.error('Respuesta:', xhr.responseText);
                
                let errorMsg = 'Error al cargar los datos de la venta';
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.message) errorMsg = response.message;
                } catch(e) {}
                
                $('#productosList').html(`<div class="alert alert-danger text-center"><i class="bi bi-exclamation-octagon"></i> ${errorMsg}<br><small class="text-muted">Status: ${status}</small></div>`);
                productosSeleccionados = [];
                actualizarTotales();
            }
        });
    });

    function renderizarProductos() {
        let html = '';
        
        if (!productosSeleccionados || productosSeleccionados.length === 0) {
            html = '<div class="alert alert-warning text-center">No se encontraron productos en esta venta</div>';
        } else {
            productosSeleccionados.forEach((item, idx) => {
                const isSelected = item.selected !== false;
                const totalItem = (item.cantidad || 0) * (item.precio_unitario || 0);
                
                html += `
                    <div class="product-row ${isSelected ? 'producto-seleccionado' : ''}" data-index="${idx}">
                        <div class="row align-items-center">
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-muted">Producto</label>
                                <p class="mb-0"><strong>${escapeHtml(item.codigo || '-')}</strong><br><small>${escapeHtml(item.producto || 'Producto')}</small></p>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-bold small text-muted">Cantidad Original</label>
                                <p class="mb-0">${item.cantidad_original || 0} und.</p>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-bold small text-muted">Cantidad a Acreditar</label>
                                <input type="number" class="form-control cantidad-input" data-index="${idx}" value="${item.cantidad || 0}" min="0" max="${item.cantidad_original || 0}" step="1">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-bold small text-muted">Precio Unitario</label>
                                <div class="input-group">
                                    <span class="input-group-text">S/</span>
                                    <input type="number" step="0.01" class="form-control precio-input" data-index="${idx}" value="${(item.precio_unitario || 0).toFixed(2)}">
                                </div>
                            </div>
                            <div class="col-md-2 text-end">
                                <div class="form-check form-switch d-inline-block me-3">
                                    <input type="checkbox" class="form-check-input select-product" data-index="${idx}" ${isSelected ? 'checked' : ''} style="transform: scale(1.2);">
                                    <label class="form-check-label small">Incluir</label>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
        }
        
        $('#productosList').html(html);
        
        // Eventos
        $('.cantidad-input').off('change').on('change', function() {
            const idx = $(this).data('index');
            let nuevaCantidad = parseInt($(this).val()) || 0;
            const maxCantidad = productosSeleccionados[idx].cantidad_original || 0;
            
            if (nuevaCantidad > maxCantidad) {
                nuevaCantidad = maxCantidad;
                $(this).val(nuevaCantidad);
                Swal.fire({
                    icon: 'warning',
                    title: 'Cantidad excedida',
                    text: `No puede acreditar más de ${maxCantidad} unidades`,
                    timer: 1500,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                });
            }
            productosSeleccionados[idx].cantidad = nuevaCantidad;
            actualizarTotales();
        });
        
        $('.precio-input').off('change').on('change', function() {
            const idx = $(this).data('index');
            productosSeleccionados[idx].precio_unitario = parseFloat($(this).val()) || 0;
            actualizarTotales();
        });
        
        $('.select-product').off('change').on('change', function() {
            const idx = $(this).data('index');
            productosSeleccionados[idx].selected = $(this).is(':checked');
            $(this).closest('.product-row').toggleClass('producto-seleccionado', $(this).is(':checked'));
            actualizarTotales();
        });
    }

    function actualizarTotales() {
        let subtotal = 0;
        
        if (productosSeleccionados && productosSeleccionados.length > 0) {
            productosSeleccionados.forEach(item => {
                if (item.selected && item.cantidad > 0) {
                    subtotal += (item.cantidad || 0) * (item.precio_unitario || 0);
                }
            });
        }
        
        const igv = subtotal * 0.18;
        const total = subtotal + igv;
        
        $('#subtotal').text(`S/ ${subtotal.toFixed(2)}`);
        $('#igv').text(`S/ ${igv.toFixed(2)}`);
        $('#total').text(`S/ ${total.toFixed(2)}`);
        $('#infoMontoAcreditar').text(`S/ ${total.toFixed(2)}`);
    }

    // Función para escapar HTML
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Guardar Nota de Crédito
    $('#btnGuardar').click(function() {
        const ventaId = $('#venta_id').val();
        const motivo = $('#motivo').val().trim();
        
        if (!ventaId) {
            Swal.fire({
                icon: 'warning',
                title: 'Venta no seleccionada',
                text: 'Debe seleccionar una venta original',
                confirmButtonColor: '#3085d6'
            });
            return;
        }
        
        if (!motivo) {
            Swal.fire({
                icon: 'warning',
                title: 'Motivo requerido',
                text: 'Debe ingresar el motivo de la nota de crédito',
                confirmButtonColor: '#3085d6'
            });
            return;
        }
        
        const detalles = [];
        if (productosSeleccionados && productosSeleccionados.length > 0) {
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
        }
        
        if (detalles.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Sin productos',
                text: 'Debe seleccionar al menos un producto para acreditar',
                confirmButtonColor: '#3085d6'
            });
            return;
        }
        
        const data = {
            venta_id: parseInt(ventaId),
            cliente_id: ventaData?.venta?.cliente_id,
            motivo: motivo,
            tipo_nota: $('#tipo_nota').val(),
            detraccion: $('#detraccion').is(':checked'),
            observaciones: $('#observaciones').val(),
            detalles: detalles
        };
        
        $('#btnGuardar').hide();
        $('#btnLoading').show();
        
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
                    Swal.fire({
                        icon: 'success',
                        title: '¡Nota de Crédito Registrada!',
                        text: response.message || 'La nota de crédito ha sido creada exitosamente',
                        confirmButtonColor: '#3085d6'
                    }).then(() => {
                        window.location.href = '/notas-credito';
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message || 'Error al guardar la nota de crédito'
                    });
                    $('#btnGuardar').show();
                    $('#btnLoading').hide();
                }
            },
            error: function(xhr) {
                let errorMsg = 'Error al guardar la nota de crédito';
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.message) errorMsg = response.message;
                } catch(e) {}
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: errorMsg
                });
                $('#btnGuardar').show();
                $('#btnLoading').hide();
            }
        });
    });
});
</script>
@endsection