@extends('layouts.master')

@section('title', 'Nueva Compra')
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
        }
        .remove-product {
            cursor: pointer;
            color: #dc3545;
            font-size: 1.2rem;
            transition: all 0.2s;
        }
        .remove-product:hover {
            color: #a71d2a;
            transform: scale(1.1);
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
        .producto-seleccionado {
            background: #e8f5e9;
            border-left: 4px solid #4caf50;
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
                            <input type="text" name="serie" class="form-control" placeholder="Ej: F001">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label fw-bold">Número</label>
                            <input type="text" name="numero" class="form-control" placeholder="Ej: 0001">
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
                                <select name="proveedor_id" id="proveedorSelect" class="form-control" required style="width: 100%;">
                                    <option value="">Seleccionar proveedor...</option>
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
                                        <label class="form-check-label">💵 Efectivo</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input type="radio" name="tipo_pago" class="form-check-input" value="TRANSFERENCIA">
                                        <label class="form-check-label">🏦 Transferencia</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input type="radio" name="tipo_pago" class="form-check-input" value="CREDITO">
                                        <label class="form-check-label">📆 Crédito</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <h6 class="fw-bold mb-3">
                        <i class="bi bi-box-seam"></i> Productos
                    </h6>

                    <!-- Agregar Producto con Select2 -->
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Agregar Producto</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-10 mb-3">
                                    <label class="form-label fw-bold">Buscar Producto</label>
                                    <select id="productoSelect" class="form-control" style="width: 100%;">
                                        <option value="">Buscar producto por nombre o código...</option>
                                    </select>
                                </div>
                                <div class="col-md-2 mb-3 d-flex align-items-end">
                                    <button type="button" class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#modalNuevoProducto">
                                        <i class="bi bi-plus-circle"></i> Nuevo Producto
                                    </button>
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
                <h5 class="modal-title"><i class="bi bi-truck"></i> Nuevo Proveedor</h5>
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
                <h5 class="modal-title"><i class="bi bi-box"></i> Nuevo Producto</h5>
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
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    let productosAgregados = [];
    let searchTimeout;

    // ========== SELECT2 PARA PROVEEDOR ==========
    $('#proveedorSelect').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Seleccionar proveedor...',
        allowClear: true,
        language: {
            noResults: function() {
                return "No se encontraron proveedores";
            }
        }
    });

    // ========== SELECT2 PARA PRODUCTO CON AJAX ==========
    $('#productoSelect').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Buscar producto por nombre o código...',
        minimumInputLength: 2,
        allowClear: true,
        ajax: {
            url: '{{ route("compras.search.productos") }}',
            dataType: 'json',
            delay: 300,
            data: function(params) {
                return {
                    q: params.term || ''
                };
            },
            processResults: function(data) {
                if (data.success && data.productos) {
                    return {
                        results: data.productos.map(function(producto) {
                            return {
                                id: producto.id,
                                text: producto.codigo_interno + ' - ' + producto.descripcion,
                                data: producto
                            };
                        })
                    };
                }
                return { results: [] };
            },
            cache: true
        },
        language: {
            inputTooShort: function() {
                return "Ingrese al menos 2 caracteres para buscar";
            },
            noResults: function() {
                return "No se encontraron productos";
            }
        }
    });

    // Evento cuando se selecciona un producto
    $('#productoSelect').on('select2:select', function(e) {
        const producto = e.params.data.data;
        
        // Verificar si ya está agregado
        if (productosAgregados.some(p => p.id === producto.id)) {
            Swal.fire({
                icon: 'warning',
                title: 'Producto duplicado',
                text: 'Este producto ya está agregado a la compra',
                confirmButtonColor: '#3085d6'
            });
            $('#productoSelect').val(null).trigger('change');
            return;
        }
        
        // Agregar producto a la lista
        productosAgregados.push({
            id: producto.id,
            codigo: producto.codigo_interno,
            descripcion: producto.descripcion,
            cantidad: 1,
            precio_unitario: parseFloat(producto.precio_compra) || 0,
            unidad: producto.unidad || 'UNIDAD'
        });
        
        renderProductos();
        $('#productoSelect').val(null).trigger('change');
        
        // Mostrar notificación de éxito
        Swal.fire({
            icon: 'success',
            title: 'Producto agregado',
            text: producto.descripcion,
            timer: 1500,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
        });
    });

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
    }

    // Función para renderizar productos
    function renderProductos() {
        let html = '';
        productosAgregados.forEach((item, idx) => {
            const totalItem = item.cantidad * item.precio_unitario;
            html += `
                <div class="product-row" data-index="${idx}">
                    <input type="hidden" name="productos[${idx}][producto_id]" value="${item.id}">
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted">Producto</label>
                            <p class="mb-0"><strong>${item.codigo}</strong> - ${item.descripcion}</p>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold small text-muted">Und.</label>
                            <p class="mb-0">${item.unidad}</p>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold small text-muted">Cantidad</label>
                            <input type="number" name="productos[${idx}][cantidad]" class="form-control cantidad-input" value="${item.cantidad}" min="1" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold small text-muted">Precio Unitario</label>
                            <div class="input-group">
                                <span class="input-group-text">S/</span>
                                <input type="number" step="0.01" name="productos[${idx}][precio_unitario]" class="form-control precio-input" value="${item.precio_unitario.toFixed(2)}" required>
                            </div>
                        </div>
                        <div class="col-md-1">
                            <label class="form-label fw-bold small text-muted">Total</label>
                            <p class="mb-0 fw-bold text-success">S/ ${totalItem.toFixed(2)}</p>
                        </div>
                        <div class="col-md-1 text-end">
                            <i class="bi bi-trash3 remove-product fs-4" title="Eliminar producto"></i>
                        </div>
                    </div>
                </div>
            `;
        });
        
        if (productosAgregados.length === 0) {
            html = '<div class="alert alert-info text-center">No hay productos agregados. Busque y seleccione productos para agregar.</div>';
        }
        
        $('#productosList').html(html);
        
        // Eventos para cambios en cantidad/precio
        $('.cantidad-input').off('change').on('change', function() {
            const index = $(this).closest('.product-row').data('index');
            productosAgregados[index].cantidad = parseInt($(this).val()) || 1;
            renderProductos();
            actualizarTotales();
        });
        
        $('.precio-input').off('change').on('change', function() {
            const index = $(this).closest('.product-row').data('index');
            productosAgregados[index].precio_unitario = parseFloat($(this).val()) || 0;
            renderProductos();
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

    // Guardar nuevo proveedor
    $('#formProveedor').on('submit', function(e) {
        e.preventDefault();
        const btn = $(this).find('button[type="submit"]');
        const originalText = btn.html();
        btn.html('<span class="spinner-border spinner-border-sm me-2"></span> Guardando...').prop('disabled', true);
        
        $.ajax({
            url: '{{ route("proveedores.store") }}',
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    $('#modalNuevoProveedor').modal('hide');
                    // Recargar select2 de proveedores
                    $.ajax({
                        url: '{{ route("proveedores.data") }}',
                        type: 'GET',
                        success: function(data) {
                            const select = $('#proveedorSelect');
                            select.empty().append('<option value="">Seleccionar proveedor...</option>');
                            if (data.data) {
                                data.data.forEach(proveedor => {
                                    select.append(`<option value="${proveedor.id}">${proveedor.numero_documento} - ${proveedor.nombre_razon_social}</option>`);
                                });
                            }
                            select.val(response.data.id).trigger('change');
                        }
                    });
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Proveedor registrado',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
                $('#formProveedor')[0].reset();
            },
            error: function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: xhr.responseJSON?.message || 'Error al guardar proveedor'
                });
            },
            complete: function() {
                btn.html(originalText).prop('disabled', false);
            }
        });
    });
    
    // Guardar nuevo producto rápido
    $('#formProductoRapido').on('submit', function(e) {
        e.preventDefault();
        const btn = $(this).find('button[type="submit"]');
        const originalText = btn.html();
        btn.html('<span class="spinner-border spinner-border-sm me-2"></span> Guardando...').prop('disabled', true);
        
        $.ajax({
            url: '{{ route("productos.store") }}',
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    $('#modalNuevoProducto').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Producto registrado',
                        text: 'El producto ha sido creado exitosamente',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    // Limpiar el select2 para que pueda buscar el nuevo producto
                    $('#productoSelect').val(null).trigger('change');
                }
                $('#formProductoRapido')[0].reset();
            },
            error: function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: xhr.responseJSON?.message || 'Error al guardar producto'
                });
            },
            complete: function() {
                btn.html(originalText).prop('disabled', false);
            }
        });
    });
    
    // Submit del formulario
    $('#formCompra').on('submit', function(e) {
        if (productosAgregados.length === 0) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Carrito vacío',
                text: 'Debe agregar al menos un producto a la compra',
                confirmButtonColor: '#3085d6'
            });
            return false;
        }
        
        $('#btnGuardar').hide();
        $('#btnLoading').show();
    });
});
</script>
@endsection