@extends('layouts.master')

@section('title', 'Nueva Compra')
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
                            <i class="bi bi-plus-circle"></i> Nueva Compra
                        </h4>
                        <p class="mb-0 text-muted small">Registra una nueva compra de mercadería</p>
                    </div>
                    <div class="col text-end">
                        <a href="{{ route('compras.index') }}" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle"></i> {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <form action="{{ route('compras.store') }}" method="POST" id="formCompra">
                    @csrf
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Tipo Comprobante</label>
                            <select name="tipo_comprobante" class="form-control" required>
                                <option value="FACTURA">FACTURA ELECTRÓNICA</option>
                                <option value="BOLETA">BOLETA ELECTRÓNICA</option>
                                <option value="NOTA_CREDITO">NOTA DE CRÉDITO</option>
                            </select>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label fw-bold">Serie</label>
                            <input type="text" name="serie" class="form-control" value="">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label fw-bold">Número</label>
                            <input type="text" name="numero" class="form-control" value="">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label fw-bold">Fecha de emisión</label>
                            <input type="date" name="fecha_emision" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Fecha de vencimiento</label>
                            <input type="date" name="fecha_vencimiento" class="form-control" value="{{ date('Y-m-d', strtotime('+7 days')) }}" required>
                        </div>
                        <div class="col-md-5 mb-3">
                            <label class="form-label fw-bold">Proveedor <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <select name="proveedor_id" id="proveedor_id" class="form-control" required>
                                    <option value="">Seleccionar proveedor</option>
                                    @foreach($proveedores as $proveedor)
                                        <option value="{{ $proveedor->id }}">{{ $proveedor->numero_documento }} - {{ $proveedor->nombre_razon_social }}</option>
                                    @endforeach
                                </select>
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevoProveedor">
                                    <i class="bi bi-plus-circle"></i> Nuevo
                                </button>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Establecimiento / Almacén</label>
                            <select name="almacen_id" class="form-control" required>
                                <option value="">Seleccionar almacén</option>
                                @foreach($almacenes as $almacen)
                                    <option value="{{ $almacen->id }}">{{ $almacen->descripcion }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Tipo de cambio</label>
                            <input type="number" step="0.0001" name="tipo_cambio" class="form-control" value="1.0000" required>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">Pago</label>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input type="radio" name="tipo_pago" class="form-check-input" value="EFECTIVO" checked>
                                        <label class="form-check-label">Efectivo</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input type="radio" name="tipo_pago" class="form-check-input" value="TRANSFERENCIA">
                                        <label class="form-check-label">Transferencia</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input type="radio" name="tipo_pago" class="form-check-input" value="CREDITO">
                                        <label class="form-check-label">Crédito</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <h6 class="fw-bold mb-3">
                        <i class="bi bi-box-seam"></i> Productos
                    </h6>

                    <!-- Agregar Producto -->
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Agregar Producto</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12 mb-3 position-relative">
                                    <label class="form-label fw-bold">Producto</label>
                                    <div class="input-group">
                                        <input type="text" id="searchProducto" class="form-control" placeholder="Buscar por nombre o código...">
                                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevoProducto">
                                            <i class="bi bi-plus-circle"></i> Nuevo
                                        </button>
                                    </div>
                                    <div id="searchResults" class="search-product-result"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Lista de productos agregados -->
                    <div id="productosList" class="mb-3"></div>

                    <!-- Totales -->
                    <div class="row">
                        <div class="col-md-8"></div>
                        <div class="col-md-4">
                            <table class="table table-bordered">
                                <tr>
                                    <td class="fw-bold">OP. Gravadas:</td>
                                    <td class="text-end" id="subtotal">S/ 0.00</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">IGV (18%):</td>
                                    <td class="text-end" id="igv">S/ 0.00</td>
                                </tr>
                                <tr class="table-primary">
                                    <td class="fw-bold">Total:</td>
                                    <td class="text-end fw-bold" id="total">S/ 0.00</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Observaciones</label>
                        <textarea name="observaciones" class="form-control" rows="2" placeholder="Observaciones adicionales..."></textarea>
                    </div>

                    <div class="text-end">
                        <a href="{{ route('compras.index') }}" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary" id="btnGuardar">
                            <i class="bi bi-save"></i> Registrar Compra
                        </button>
                        <button type="button" class="btn btn-primary" id="btnLoading" style="display: none;" disabled>
                            <span class="spinner-border spinner-border-sm me-2"></span> Registrando...
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nuevo Proveedor -->
<div class="modal fade" id="modalNuevoProveedor" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nuevo Proveedor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formProveedor">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Tipo Documento</label>
                        <select name="tipo_documento" class="form-control" required>
                            <option value="RUC">RUC</option>
                            <option value="DNI">DNI</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Número Documento</label>
                        <input type="text" name="numero_documento" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nombre/Razón Social</label>
                        <input type="text" name="nombre_razon_social" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Dirección</label>
                        <textarea name="direccion" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Teléfono</label>
                        <input type="text" name="telefono" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Proveedor</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Nuevo Producto -->
<div class="modal fade" id="modalNuevoProducto" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nuevo Producto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formProductoRapido">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Código Interno</label>
                        <input type="text" name="codigo_interno" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Descripción</label>
                        <textarea name="descripcion" class="form-control" rows="2" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Unidad</label>
                        <select name="unidad" class="form-control">
                            <option value="UNIDAD">UNIDAD</option>
                            <option value="KG">KG</option>
                            <option value="LITRO">LITRO</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Precio Compra</label>
                        <input type="number" step="0.01" name="precio_compra" class="form-control" value="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Precio Venta</label>
                        <input type="number" step="0.01" name="precio_venta" class="form-control" value="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Producto</button>
                </div>
            </form>
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

    // Función para actualizar totales
    function actualizarTotales() {
        let subtotal = 0;
        productosAgregados.forEach(item => {
            subtotal += item.cantidad * item.precio_unitario;
        });
        let igv = subtotal * 0.18;
        let total = subtotal + igv;
        
        $('#subtotal').text('S/ ' + subtotal.toFixed(2));
        $('#igv').text('S/ ' + igv.toFixed(2));
        $('#total').text('S/ ' + total.toFixed(2));
        
        // Actualizar campos ocultos
        $('<input>').attr('type', 'hidden').attr('name', 'subtotal').val(subtotal).appendTo('#formCompra');
        $('<input>').attr('type', 'hidden').attr('name', 'igv').val(igv).appendTo('#formCompra');
        $('<input>').attr('type', 'hidden').attr('name', 'total').val(total).appendTo('#formCompra');
    }

    // Función para renderizar productos
    function renderProductos() {
        let html = '';
        productosAgregados.forEach((item, idx) => {
            html += `
                <div class="product-row" data-index="${idx}">
                    <input type="hidden" name="productos[${idx}][producto_id]" value="${item.id}">
                    <div class="row">
                        <div class="col-md-5">
                            <label class="form-label fw-bold">Producto</label>
                            <p class="mb-0"><strong>${item.codigo}</strong> - ${item.descripcion}</p>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Und.</label>
                            <p class="mb-0">${item.unidad}</p>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Cantidad</label>
                            <input type="number" name="productos[${idx}][cantidad]" class="form-control cantidad-input" value="${item.cantidad}" min="1" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Precio Unitario</label>
                            <input type="number" step="0.01" name="productos[${idx}][precio_unitario]" class="form-control precio-input" value="${item.precio_unitario}" required>
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">&nbsp;</label>
                            <div>
                                <i class="bi bi-trash3 remove-product fs-5"></i>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        $('#productosList').html(html);
        
        // Eventos para cambios en cantidad/precio
        $('.cantidad-input, .precio-input').on('change', function() {
            const index = $(this).closest('.product-row').data('index');
            const row = productosAgregados[index];
            row.cantidad = parseInt($(this).closest('.product-row').find('.cantidad-input').val());
            row.precio_unitario = parseFloat($(this).closest('.product-row').find('.precio-input').val());
            actualizarTotales();
        });
        
        $('.remove-product').off('click').on('click', function() {
            const index = $(this).closest('.product-row').data('index');
            productosAgregados.splice(index, 1);
            renderProductos();
            actualizarTotales();
        });
        
        actualizarTotales();
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
                url: '{{ route("compras.search.productos") }}',
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
                                     data-descripcion="${producto.descripcion}" 
                                     data-precio="${producto.precio_compra}"
                                     data-unidad="${producto.unidad}">
                                    <strong>${producto.codigo_interno}</strong> - ${producto.descripcion}<br>
                                    <small>Precio compra: S/ ${parseFloat(producto.precio_compra).toFixed(2)} | Unidad: ${producto.unidad}</small>
                                </div>
                            `;
                        });
                        $('#searchResults').html(html).show();
                        
                        $('.product-result-item').off('click').on('click', function() {
                            const id = $(this).data('id');
                            const codigo = $(this).data('codigo');
                            const descripcion = $(this).data('descripcion');
                            const precio = parseFloat($(this).data('precio'));
                            const unidad = $(this).data('unidad');
                            
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
                                precio_unitario: precio,
                                unidad: unidad
                            });
                            
                            renderProductos();
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
    
    // Guardar nuevo proveedor
    $('#formProveedor').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: '{{ route("proveedores.store") }}',
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    $('#modalNuevoProveedor').modal('hide');
                    location.reload();
                }
            }
        });
    });
    
    // Guardar nuevo producto rápido
    $('#formProductoRapido').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: '{{ route("productos.store") }}',
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    $('#modalNuevoProducto').modal('hide');
                    location.reload();
                }
            }
        });
    });
    
    // Submit del formulario
    $('#formCompra').on('submit', function(e) {
        if (productosAgregados.length === 0) {
            e.preventDefault();
            alert('Debe agregar al menos un producto');
            return false;
        }
        
        $('#btnGuardar').hide();
        $('#btnLoading').show();
    });
});
</script>
@endsection