@extends('layouts.master')

@section('title', 'Nueva Nota de Venta')
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
        }
        .product-result-item {
            padding: 10px;
            border-bottom: 1px solid #dee2e6;
            cursor: pointer;
            transition: background 0.2s;
        }
        .product-result-item:hover {
            background: #f8f9fa;
        }
        .product-result-item:last-child {
            border-bottom: none;
        }
        .position-relative {
            position: relative;
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
                            <i class="bi bi-plus-circle"></i> Nueva Nota de Venta
                        </h4>
                        <p class="mb-0 text-muted small">Registra una nueva nota de venta</p>
                    </div>
                    <div class="col text-end">
                        <a href="{{ route('notas-venta.index') }}" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Cliente <span class="text-danger">*</span></label>
                        <select id="cliente_id" class="form-control">
                            <option value="">Seleccionar cliente</option>
                            @foreach($clientes as $cliente)
                                <option value="{{ $cliente->id }}">{{ $cliente->numero_documento }} - {{ $cliente->nombre_razon_social }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Serie</label>
                        <input type="text" id="serie_documento" class="form-control" readonly>
                        <input type="hidden" id="serie" name="serie">
                        <input type="hidden" id="numero" name="numero">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Tipo de Nota</label>
                        <select id="tipo_nota" class="form-control">
                            <option value="CREDITO_FISCAL">Crédito Fiscal</option>
                            <option value="DEBITO_FISCAL">Débito Fiscal</option>
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

                <h6 class="fw-bold mb-3"><i class="bi bi-box-seam"></i> Productos</h6>

                <!-- Agregar Producto -->
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">Agregar Producto</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12 mb-3 position-relative">
                                <label class="form-label fw-bold">Producto</label>
                                <input type="text" id="searchProducto" class="form-control" placeholder="Buscar por nombre o código...">
                                <div id="searchResults" class="search-product-result"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Lista de productos agregados -->
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
                    <a href="{{ route('notas-venta.index') }}" class="btn btn-secondary">Cancelar</a>
                    <button type="button" class="btn btn-primary" id="btnGuardar">
                        <i class="bi bi-save"></i> Guardar Nota de Venta
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
    let productosAgregados = [];
    let currentIndex = 0;
    let searchTimeout;

    function cargarSerie() {
        $.ajax({
            url: '/notas-venta/serie',
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

    function actualizarTotales() {
        let subtotal = 0;
        productosAgregados.forEach(item => {
            subtotal += item.cantidad * item.precio;
        });
        const igv = subtotal * 0.18;
        const total = subtotal + igv;
        
        $('#subtotal').text(`S/ ${subtotal.toFixed(2)}`);
        $('#igv').text(`S/ ${igv.toFixed(2)}`);
        $('#total').text(`S/ ${total.toFixed(2)}`);
    }

    function renderizarProductos() {
        let html = '';
        productosAgregados.forEach((item, idx) => {
            html += `
                <div class="product-row" data-index="${idx}">
                    <input type="hidden" name="productos[${idx}][id]" value="${item.id}">
                    <input type="hidden" name="productos[${idx}][almacen_id]" value="${item.almacen_id || 1}">
                    <div class="row">
                        <div class="col-md-5">
                            <label class="form-label fw-bold">Producto</label>
                            <p class="mb-0"><strong>${item.codigo}</strong> - ${item.descripcion}</p>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Cantidad</label>
                            <input type="number" class="form-control cantidad-input" data-index="${idx}" value="${item.cantidad}" min="1">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Precio Unitario</label>
                            <input type="number" step="0.01" class="form-control precio-input" data-index="${idx}" value="${item.precio}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div>
                                <button type="button" class="btn btn-sm btn-danger remove-product" data-index="${idx}">
                                    <i class="bi bi-trash"></i> Eliminar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        $('#productosList').html(html);
        
        $('.cantidad-input').on('change', function() {
            const idx = $(this).data('index');
            productosAgregados[idx].cantidad = parseInt($(this).val()) || 0;
            actualizarTotales();
        });
        
        $('.precio-input').on('change', function() {
            const idx = $(this).data('index');
            productosAgregados[idx].precio = parseFloat($(this).val()) || 0;
            actualizarTotales();
        });
        
        $('.remove-product').on('click', function() {
            const idx = $(this).data('index');
            productosAgregados.splice(idx, 1);
            renderizarProductos();
            actualizarTotales();
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
                url: '{{ route("notas-venta.search.productos") }}',
                type: 'GET',
                data: { q: search },
                success: function(response) {
                    if (response.success && response.productos.length > 0) {
                        let html = '';
                        response.productos.forEach(producto => {
                            html += `
                                <div class="product-result-item" 
                                     data-id="${producto.id}" 
                                     data-codigo="${producto.codigo_interno}" 
                                     data-descripcion="${producto.descripcion.replace(/"/g, '&quot;')}" 
                                     data-precio="${producto.precio_venta}">
                                    <strong>${producto.codigo_interno}</strong> - ${producto.descripcion}<br>
                                    <small>Precio: S/ ${producto.precio_venta.toFixed(2)} | Unidad: ${producto.unidad} | Stock: ${producto.stock}</small>
                                </div>
                            `;
                        });
                        $('#searchResults').html(html).show();
                        
                        $('.product-result-item').off('click').on('click', function() {
                            const id = $(this).data('id');
                            const codigo = $(this).data('codigo');
                            const descripcion = $(this).data('descripcion');
                            const precio = parseFloat($(this).data('precio'));
                            
                            // Verificar si ya está agregado
                            if (productosAgregados.some(p => p.id === id)) {
                                alert('Este producto ya está agregado');
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
                        });
                    } else {
                        $('#searchResults').html('<div class="p-3 text-center text-muted">No se encontraron productos</div>').show();
                    }
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

    $('#btnGuardar').click(function() {
        if (productosAgregados.length === 0) {
            alert('Debe agregar al menos un producto');
            return;
        }
        
        const clienteId = $('#cliente_id').val();
        if (!clienteId) {
            alert('Debe seleccionar un cliente');
            return;
        }
        
        const productos = productosAgregados.map(item => ({
            id: item.id,
            cantidad: item.cantidad,
            precio: item.precio,
            almacen_id: item.almacen_id || 1
        }));
        
        const data = {
            cliente_id: clienteId,
            tipo_nota: $('#tipo_nota').val(),
            detraccion: $('#detraccion').is(':checked'),
            observaciones: $('#observaciones').val(),
            productos: productos
        };
        
        $.ajax({
            url: '/notas-venta',
            type: 'POST',
            data: JSON.stringify(data),
            contentType: 'application/json',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    window.location.href = '/notas-venta';
                } else {
                    alert(response.message);
                }
            },
            error: function(xhr) {
                alert(xhr.responseJSON?.message || 'Error al guardar la nota de venta');
            }
        });
    });
});
</script>
@endsection