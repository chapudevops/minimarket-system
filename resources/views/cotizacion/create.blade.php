@extends('layouts.master')

@section('title', 'Nueva Cotización')
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
        .remove-product {
            cursor: pointer;
            color: #dc3545;
            transition: all 0.2s;
        }
        .remove-product:hover {
            color: #a71d2a;
            transform: scale(1.05);
        }
        .search-product-result {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            display: none;
            position: absolute;
            background: white;
            z-index: 1000;
            width: 100%;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .product-result-item {
            padding: 10px;
            border-bottom: 1px solid #dee2e6;
            cursor: pointer;
            transition: background 0.2s;
        }
        .product-result-item:hover {
            background: #e8f5e9;
        }
        .product-result-item:last-child {
            border-bottom: none;
        }
        .position-relative {
            position: relative;
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
        .info-cliente-card {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .info-cliente-card small {
            color: rgba(255,255,255,0.8);
        }
        .btn-agregar-producto {
            background: linear-gradient(95deg, #059669, #10b981);
            border: none;
            border-radius: 40px;
            padding: 8px 20px;
            font-weight: 600;
        }
        .btn-agregar-producto:hover {
            transform: scale(0.98);
            filter: brightness(1.02);
        }
        .producto-total {
            font-weight: bold;
            color: #059669;
        }
        .badge-moneda {
            font-size: 10px;
            padding: 3px 8px;
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
                            <i class="bi bi-file-text-fill"></i> Nueva Cotización
                        </h4>
                        <p class="mb-0 text-muted small">Registra una nueva cotización para tu cliente</p>
                    </div>
                    <div class="col text-end">
                        <a href="{{ route('cotizaciones.index') }}" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">
                            <i class="bi bi-person"></i> Cliente <span class="text-danger">*</span>
                        </label>
                        <select id="cliente_id" class="form-control" style="width: 100%;">
                            <option value="">🔍 Buscar cliente por nombre o documento...</option>
                            @foreach($clientes as $cliente)
                                <option value="{{ $cliente->id }}" 
                                    data-documento="{{ $cliente->numero_documento }}"
                                    data-nombre="{{ $cliente->nombre_razon_social }}"
                                    data-direccion="{{ $cliente->direccion ?? '-' }}"
                                    data-telefono="{{ $cliente->telefono ?? '-' }}">
                                    {{ $cliente->numero_documento }} - {{ $cliente->nombre_razon_social }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Serie del Documento</label>
                        <input type="text" id="serie_documento" class="form-control bg-light" readonly>
                        <input type="hidden" id="serie" name="serie">
                        <input type="hidden" id="numero" name="numero">
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label fw-bold">Fecha de Emisión</label>
                        <input type="date" id="fecha_emision" class="form-control" value="{{ date('Y-m-d') }}" readonly>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">
                            <i class="bi bi-calendar-check"></i> Fecha Validez <span class="text-danger">*</span>
                        </label>
                        <input type="date" id="fecha_validez" class="form-control" value="{{ date('Y-m-d', strtotime('+7 days')) }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label fw-bold">
                            <i class="bi bi-currency-dollar"></i> Tipo Moneda
                        </label>
                        <select id="tipo_moneda" class="form-control">
                            <option value="PEN">🇵🇪 Soles (S/)</option>
                            <option value="USD">🇺🇸 Dólares ($)</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label fw-bold">
                            <i class="bi bi-arrow-left-right"></i> Tipo Cambio
                        </label>
                        <input type="number" step="0.0001" id="tipo_cambio" class="form-control" value="3.70" readonly>
                    </div>
                </div>

                <!-- Info del Cliente Seleccionado -->
                <div id="infoClienteContainer" style="display: none;">
                    <div class="info-cliente-card">
                        <div class="row">
                            <div class="col-md-4">
                                <small>DOCUMENTO</small>
                                <p class="mb-0 fw-bold" id="infoDocumento">-</p>
                            </div>
                            <div class="col-md-4">
                                <small>CLIENTE</small>
                                <p class="mb-0 fw-bold" id="infoNombreCliente">-</p>
                            </div>
                            <div class="col-md-4">
                                <small>TELÉFONO</small>
                                <p class="mb-0 fw-bold" id="infoTelefono">-</p>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-md-12">
                                <small>DIRECCIÓN</small>
                                <p class="mb-0 fw-bold" id="infoDireccion">-</p>
                            </div>
                        </div>
                    </div>
                </div>

                <hr>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0">
                        <i class="bi bi-box-seam"></i> Productos
                    </h6>
                </div>

                <!-- Agregar Producto -->
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">Agregar Producto</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12 mb-3 position-relative">
                                <label class="form-label fw-bold">Buscar Producto</label>
                                <div class="input-group">
                                    <input type="text" id="searchProducto" class="form-control" placeholder="Buscar por nombre o código...">
                                    <button type="button" class="btn btn-agregar-producto" id="btnBuscarProducto" style="display: none;">
                                        <i class="bi bi-search"></i> Buscar
                                    </button>
                                </div>
                                <div id="searchResults" class="search-product-result"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Lista de productos agregados -->
                <div id="productosList" class="mb-3">
                    <div class="alert alert-info text-center">
                        <i class="bi bi-info-circle"></i> Busque y seleccione productos para agregar
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body">
                                <label class="form-label fw-bold">
                                    <i class="bi bi-tag"></i> Descuento General
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text" id="monedaSimbolo">S/</span>
                                    <input type="number" step="0.01" id="descuento_general" class="form-control" value="0">
                                    <button class="btn btn-outline-secondary" type="button" id="btnAplicarDescuento">
                                        <i class="bi bi-check-lg"></i> Aplicar
                                    </button>
                                </div>
                                <small class="text-muted">Descuento aplicado al subtotal antes de IGV</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6"></div>
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
                                        <td class="fw-bold fs-5">Total:</td>
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
                    <a href="{{ route('cotizaciones.index') }}" class="btn btn-secondary">
                        <i class="bi bi-x-circle"></i> Cancelar
                    </a>
                    <button type="button" class="btn btn-primary" id="btnGuardar">
                        <i class="bi bi-save"></i> Guardar Cotización
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
    let productosAgregados = [];
    let searchTimeout;

    // ========== SELECT2 PARA CLIENTE ==========
    $('#cliente_id').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: '🔍 Buscar cliente por nombre o documento...',
        allowClear: true,
        language: {
            noResults: function() {
                return "No se encontraron clientes";
            }
        }
    });

    // Mostrar info del cliente cuando se selecciona
    $('#cliente_id').on('change', function() {
        const selectedOption = $(this).find('option:selected');
        const clienteId = $(this).val();
        
        if (!clienteId) {
            $('#infoClienteContainer').hide();
            return;
        }
        
        const documento = selectedOption.data('documento') || '-';
        const nombre = selectedOption.data('nombre') || '-';
        const direccion = selectedOption.data('direccion') || '-';
        const telefono = selectedOption.data('telefono') || '-';
        
        $('#infoDocumento').text(documento);
        $('#infoNombreCliente').text(nombre);
        $('#infoDireccion').text(direccion);
        $('#infoTelefono').text(telefono);
        $('#infoClienteContainer').show();
    });

    // Cargar serie del documento
    function cargarSerie() {
        $.ajax({
            url: '/cotizaciones/serie',
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    $('#serie_documento').val(response.documento);
                    $('#serie').val(response.serie);
                    $('#numero').val(response.numero);
                } else {
                    $('#serie_documento').val(response.message || 'Error al cargar serie');
                    Swal.fire({
                        icon: 'warning',
                        title: 'Atención',
                        text: response.message,
                        confirmButtonColor: '#3085d6'
                    });
                }
            },
            error: function() {
                $('#serie_documento').val('Error al cargar serie');
            }
        });
    }
    cargarSerie();

    // Cambiar símbolo de moneda
    function actualizarSimboloMoneda() {
        const moneda = $('#tipo_moneda').val();
        const simbolo = moneda === 'PEN' ? 'S/' : '$';
        $('#monedaSimbolo').text(simbolo);
        actualizarTotales();
    }

    // Tipo de cambio
    $('#tipo_moneda').on('change', function() {
        const moneda = $(this).val();
        if (moneda === 'USD') {
            $('#tipo_cambio').prop('readonly', false);
            $('#tipo_cambio').val(3.70);
        } else {
            $('#tipo_cambio').val(1).prop('readonly', true);
        }
        actualizarSimboloMoneda();
    });

    function actualizarTotales() {
        let subtotal = 0;
        productosAgregados.forEach(item => {
            subtotal += (item.cantidad || 0) * (item.precio || 0);
        });
        
        const descuentoGeneral = parseFloat($('#descuento_general').val()) || 0;
        const subtotalConDescuento = Math.max(0, subtotal - descuentoGeneral);
        const igv = subtotalConDescuento * 0.18;
        const total = subtotalConDescuento + igv;
        
        const moneda = $('#tipo_moneda').val();
        const simbolo = moneda === 'PEN' ? 'S/' : '$';
        
        $('#subtotal').text(`${simbolo} ${subtotalConDescuento.toFixed(2)}`);
        $('#igv').text(`${simbolo} ${igv.toFixed(2)}`);
        $('#total').text(`${simbolo} ${total.toFixed(2)}`);
    }

    function renderizarProductos() {
        let html = '';
        
        if (productosAgregados.length === 0) {
            html = '<div class="alert alert-info text-center"><i class="bi bi-info-circle"></i> Busque y seleccione productos para agregar</div>';
        } else {
            productosAgregados.forEach((item, idx) => {
                const totalItem = (item.cantidad || 0) * (item.precio || 0);
                html += `
                    <div class="product-row" data-index="${idx}">
                        <input type="hidden" name="productos[${idx}][id]" value="${item.id}">
                        <input type="hidden" name="productos[${idx}][almacen_id]" value="${item.almacen_id || 1}">
                        <div class="row align-items-center">
                            <div class="col-md-5 mb-2 mb-md-0">
                                <label class="form-label fw-bold small text-muted">Producto</label>
                                <p class="mb-0"><strong>${escapeHtml(item.codigo)}</strong><br><small>${escapeHtml(item.descripcion)}</small></p>
                            </div>
                            <div class="col-md-2 mb-2 mb-md-0">
                                <label class="form-label fw-bold small text-muted">Cantidad</label>
                                <input type="number" class="form-control cantidad-input" data-index="${idx}" value="${item.cantidad}" min="1" step="1">
                            </div>
                            <div class="col-md-3 mb-2 mb-md-0">
                                <label class="form-label fw-bold small text-muted">Precio Unitario</label>
                                <div class="input-group">
                                    <span class="input-group-text">${$('#tipo_moneda').val() === 'PEN' ? 'S/' : '$'}</span>
                                    <input type="number" step="0.01" class="form-control precio-input" data-index="${idx}" value="${item.precio.toFixed(2)}" min="0">
                                </div>
                            </div>
                            <div class="col-md-2 text-end">
                                <label class="form-label small text-muted">&nbsp;</label>
                                <div>
                                    <button type="button" class="btn btn-sm btn-outline-danger remove-product w-100" data-index="${idx}">
                                        <i class="bi bi-trash3"></i> Eliminar
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-12">
                                <small class="text-muted">Total: <strong class="producto-total">${$('#tipo_moneda').val() === 'PEN' ? 'S/' : '$'} ${totalItem.toFixed(2)}</strong></small>
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
            let cantidad = parseInt($(this).val()) || 1;
            if (cantidad < 1) cantidad = 1;
            productosAgregados[idx].cantidad = cantidad;
            $(this).val(cantidad);
            renderizarProductos();
            actualizarTotales();
        });
        
        $('.precio-input').off('change').on('change', function() {
            const idx = $(this).data('index');
            let precio = parseFloat($(this).val()) || 0;
            if (precio < 0) precio = 0;
            productosAgregados[idx].precio = precio;
            renderizarProductos();
            actualizarTotales();
        });
        
        $('.remove-product').off('click').on('click', function() {
            const idx = $(this).data('index');
            Swal.fire({
                title: '¿Eliminar producto?',
                text: `¿Desea eliminar "${productosAgregados[idx].descripcion}" de la cotización?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    productosAgregados.splice(idx, 1);
                    renderizarProductos();
                    actualizarTotales();
                    Swal.fire('Eliminado', 'Producto eliminado correctamente', 'success');
                }
            });
        });
    }

    // Buscar productos
    $('#searchProducto').on('keyup', function() {
        clearTimeout(searchTimeout);
        const search = $(this).val();
        
        if (search.length < 2) {
            $('#searchResults').hide();
            return;
        }
        
        searchTimeout = setTimeout(function() {
            $.ajax({
                url: '{{ route("cotizaciones.search.productos") }}',
                type: 'GET',
                data: { q: search },
                beforeSend: function() {
                    $('#searchResults').html('<div class="p-3 text-center"><div class="spinner-border spinner-border-sm text-primary"></div> Buscando...</div>').show();
                },
                success: function(response) {
                    if (response.success && response.productos && response.productos.length > 0) {
                        let html = '';
                        response.productos.forEach(producto => {
                            const precio = parseFloat(producto.precio_venta) || 0;
                            html += `
                                <div class="product-result-item" 
                                     data-id="${producto.id}" 
                                     data-codigo="${escapeHtml(producto.codigo_interno)}" 
                                     data-descripcion="${escapeHtml(producto.descripcion)}" 
                                     data-precio="${precio}"
                                     data-stock="${producto.stock || 0}">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong>${escapeHtml(producto.codigo_interno)}</strong> - ${escapeHtml(producto.descripcion)}
                                        </div>
                                        <span class="badge ${producto.stock > 0 ? 'bg-success' : 'bg-danger'}">Stock: ${producto.stock || 0}</span>
                                    </div>
                                    <small class="text-muted">Precio: ${$('#tipo_moneda').val() === 'PEN' ? 'S/' : '$'} ${precio.toFixed(2)} | Unidad: ${producto.unidad || 'UNIDAD'}</small>
                                </div>
                            `;
                        });
                        $('#searchResults').html(html).show();
                        
                        $('.product-result-item').off('click').on('click', function() {
                            const stock = parseInt($(this).data('stock')) || 0;
                            const id = $(this).data('id');
                            const codigo = $(this).data('codigo');
                            const descripcion = $(this).data('descripcion');
                            const precio = parseFloat($(this).data('precio'));
                            
                            if (stock <= 0) {
                                Swal.fire({
                                    icon: 'warning',
                                    title: 'Sin stock',
                                    text: `El producto "${descripcion}" no tiene stock disponible`,
                                    confirmButtonColor: '#3085d6'
                                });
                                return;
                            }
                            
                            if (productosAgregados.some(p => p.id === id)) {
                                Swal.fire({
                                    icon: 'warning',
                                    title: 'Producto duplicado',
                                    text: 'Este producto ya está agregado a la cotización',
                                    confirmButtonColor: '#3085d6'
                                });
                                return;
                            }
                            
                            productosAgregados.push({
                                id: id,
                                codigo: codigo,
                                descripcion: descripcion,
                                cantidad: 1,
                                precio: precio,
                                almacen_id: 1
                            });
                            
                            renderizarProductos();
                            actualizarTotales();
                            $('#searchProducto').val('');
                            $('#searchResults').hide();
                            
                            Swal.fire({
                                icon: 'success',
                                title: 'Producto agregado',
                                text: descripcion,
                                timer: 1500,
                                showConfirmButton: false,
                                toast: true,
                                position: 'top-end'
                            });
                        });
                    } else {
                        $('#searchResults').html('<div class="p-3 text-center text-muted">No se encontraron productos</div>').show();
                    }
                },
                error: function() {
                    $('#searchResults').html('<div class="p-3 text-center text-danger">Error al buscar productos</div>').show();
                }
            });
        }, 300);
    });
    
    // Ocultar resultados
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#searchProducto, #searchResults').length) {
            $('#searchResults').hide();
        }
    });

    // Descuento general
    $('#descuento_general').on('keyup change', function() {
        actualizarTotales();
    });

    $('#btnAplicarDescuento').click(function() {
        actualizarTotales();
        Swal.fire({
            icon: 'success',
            title: 'Descuento aplicado',
            text: `Se ha aplicado un descuento de ${$('#monedaSimbolo').text()} ${parseFloat($('#descuento_general').val()).toFixed(2)}`,
            timer: 1500,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
        });
    });

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Guardar Cotización
    $('#btnGuardar').click(function() {
        const clienteId = $('#cliente_id').val();
        
        if (!clienteId) {
            Swal.fire({
                icon: 'warning',
                title: 'Cliente no seleccionado',
                text: 'Debe seleccionar un cliente',
                confirmButtonColor: '#3085d6'
            });
            return;
        }
        
        if (productosAgregados.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Sin productos',
                text: 'Debe agregar al menos un producto',
                confirmButtonColor: '#3085d6'
            });
            return;
        }
        
        const fechaValidez = $('#fecha_validez').val();
        if (!fechaValidez) {
            Swal.fire({
                icon: 'warning',
                title: 'Fecha validez requerida',
                text: 'Debe ingresar una fecha de validez',
                confirmButtonColor: '#3085d6'
            });
            return;
        }
        
        // Validar productos
        let hasError = false;
        for (const item of productosAgregados) {
            if (!item.cantidad || item.cantidad <= 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Cantidad inválida',
                    text: `El producto "${item.descripcion}" tiene cantidad inválida`,
                    confirmButtonColor: '#3085d6'
                });
                hasError = true;
                return;
            }
            if (!item.precio || item.precio <= 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Precio inválido',
                    text: `El producto "${item.descripcion}" tiene precio inválido`,
                    confirmButtonColor: '#3085d6'
                });
                hasError = true;
                return;
            }
        }
        
        if (hasError) return;
        
        const productos = productosAgregados.map(item => ({
            id: item.id,
            cantidad: item.cantidad,
            precio: item.precio,
            almacen_id: item.almacen_id || 1
        }));
        
        const data = {
            cliente_id: parseInt(clienteId),
            fecha_validez: fechaValidez,
            tipo_moneda: $('#tipo_moneda').val(),
            tipo_cambio: parseFloat($('#tipo_cambio').val()) || 1,
            descuento_general: parseFloat($('#descuento_general').val()) || 0,
            observaciones: $('#observaciones').val(),
            productos: productos
        };
        
        $('#btnGuardar').hide();
        $('#btnLoading').show();
        
        $.ajax({
            url: '/cotizaciones',
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
                        title: '¡Cotización Registrada!',
                        text: response.message || 'La cotización ha sido creada exitosamente',
                        confirmButtonColor: '#3085d6'
                    }).then(() => {
                        window.location.href = '/cotizaciones';
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message || 'Error al guardar la cotización'
                    });
                    $('#btnGuardar').show();
                    $('#btnLoading').hide();
                }
            },
            error: function(xhr) {
                let errorMsg = 'Error al guardar la cotización';
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