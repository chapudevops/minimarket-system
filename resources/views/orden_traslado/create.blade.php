@extends('layouts.master')

@section('title', 'Nueva Orden de Traslado')
@section('css')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
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
                            <i class="bi bi-plus-circle"></i> Nueva Orden de Traslado
                        </h4>
                        <p class="mb-0 text-muted small">Crea una nueva orden para trasladar productos entre almacenes</p>
                    </div>
                    <div class="col text-end">
                        <a href="{{ route('traslados.index') }}" class="btn btn-secondary">
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

                <form action="{{ route('traslados.store') }}" method="POST" id="formTraslado">
                    @csrf
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Serie</label>
                            <input type="text" name="serie" class="form-control" value="{{ $serie }}" readonly>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Número</label>
                            <input type="text" name="numero" class="form-control" value="{{ $numero }}" readonly>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Fecha de emisión <span class="text-danger">*</span></label>
                            <input type="date" name="fecha_emision" class="form-control @error('fecha_emision') is-invalid @enderror" value="{{ old('fecha_emision', date('Y-m-d')) }}" required>
                            @error('fecha_emision')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Fecha de vencimiento <span class="text-danger">*</span></label>
                            <input type="date" name="fecha_vencimiento" class="form-control @error('fecha_vencimiento') is-invalid @enderror" value="{{ old('fecha_vencimiento', date('Y-m-d', strtotime('+7 days'))) }}" required>
                            @error('fecha_vencimiento')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Almacén Despacho (Origen) <span class="text-danger">*</span></label>
                            <select name="almacen_origen_id" id="almacen_origen_id" class="form-control @error('almacen_origen_id') is-invalid @enderror" required>
                                <option value="">Seleccionar almacén</option>
                                @foreach($almacenes as $almacen)
                                    <option value="{{ $almacen->id }}" {{ old('almacen_origen_id') == $almacen->id ? 'selected' : '' }}>
                                        {{ $almacen->descripcion }}
                                    </option>
                                @endforeach
                            </select>
                            @error('almacen_origen_id')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Almacén Destino (Receptor) <span class="text-danger">*</span></label>
                            <select name="almacen_destino_id" id="almacen_destino_id" class="form-control @error('almacen_destino_id') is-invalid @enderror" required>
                                <option value="">Seleccionar almacén</option>
                                @foreach($almacenes as $almacen)
                                    <option value="{{ $almacen->id }}" {{ old('almacen_destino_id') == $almacen->id ? 'selected' : '' }}>
                                        {{ $almacen->descripcion }}
                                    </option>
                                @endforeach
                            </select>
                            @error('almacen_destino_id')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">Observaciones</label>
                            <textarea name="observaciones" class="form-control" rows="2" placeholder="Observaciones adicionales...">{{ old('observaciones') }}</textarea>
                        </div>
                    </div>

                    <hr>

                    <h6 class="fw-bold mb-3">
                        <i class="bi bi-box-seam"></i> Productos a Trasladar
                    </h6>

                    <!-- Agregar Producto -->
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Agregar Producto</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label class="form-label fw-bold">Producto</label>
                                    <input type="text" id="searchProducto" class="form-control" placeholder="Buscar por nombre o código...">
                                    <div id="searchResults" class="search-product-result mt-2"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Lista de productos agregados -->
                    <div id="productosList" class="mb-3">
                        @if(old('productos'))
                            @foreach(old('productos') as $index => $producto)
                                <div class="product-row" data-index="{{ $index }}">
                                    <input type="hidden" name="productos[{{ $index }}][id]" value="{{ $producto['id'] }}">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">Producto</label>
                                            <p class="mb-0">{{ $producto['descripcion'] ?? '' }}</p>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label fw-bold">Cantidad</label>
                                            <input type="number" name="productos[{{ $index }}][cantidad]" class="form-control" value="{{ $producto['cantidad'] }}" min="1" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-bold">Precio Unitario</label>
                                            <input type="number" step="0.01" name="productos[{{ $index }}][precio]" class="form-control" value="{{ $producto['precio'] }}" required>
                                        </div>
                                        <div class="col-md-1">
                                            <label class="form-label">&nbsp;</label>
                                            <div>
                                                <i class="bi bi-trash3 remove-product fs-5"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>

                    <div class="text-end">
                        <a href="{{ route('traslados.index') }}" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary" id="btnGuardar">
                            <i class="bi bi-save"></i> Guardar Orden de Traslado
                        </button>
                        <button type="button" class="btn btn-primary" id="btnLoading" style="display: none;" disabled>
                            <span class="spinner-border spinner-border-sm me-2"></span> Guardando...
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
$(document).ready(function() {
    let searchTimeout;
    let currentIndex = {{ count(old('productos', [])) }};
    
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
                url: '{{ route("traslados.search.productos") }}',
                type: 'GET',
                data: { q: search },
                success: function(response) {
                    if (response.success && response.productos.length > 0) {
                        let html = '';
                        response.productos.forEach(producto => {
                            html += `
                                <div class="product-result-item" data-id="${producto.id}" data-codigo="${producto.codigo_interno}" data-descripcion="${producto.descripcion}" data-precio="${producto.precio_venta}">
                                    <strong>${producto.codigo_interno}</strong> - ${producto.descripcion}<br>
                                    <small>Precio: S/ ${producto.precio_venta.toFixed(2)} | Unidad: ${producto.unidad}</small>
                                </div>
                            `;
                        });
                        $('#searchResults').html(html).show();
                        
                        $('.product-result-item').off('click').on('click', function() {
                            const id = $(this).data('id');
                            const codigo = $(this).data('codigo');
                            const descripcion = $(this).data('descripcion');
                            const precio = $(this).data('precio');
                            
                            agregarProducto(id, codigo, descripcion, precio);
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
    
    // Ocultar resultados al hacer clic fuera
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#searchProducto, #searchResults').length) {
            $('#searchResults').hide();
        }
    });
    
    // Agregar producto
    function agregarProducto(id, codigo, descripcion, precio) {
        // Verificar si el producto ya está agregado
        let existe = false;
        $('.product-row').each(function() {
            const inputId = $(this).find('input[name$="[id]"]').val();
            if (inputId == id) {
                existe = true;
            }
        });
        
        if (existe) {
            alert('Este producto ya está agregado');
            return;
        }
        
        const html = `
            <div class="product-row" data-index="${currentIndex}">
                <input type="hidden" name="productos[${currentIndex}][id]" value="${id}">
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Producto</label>
                        <p class="mb-0"><strong>${codigo}</strong> - ${descripcion}</p>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">Cantidad</label>
                        <input type="number" name="productos[${currentIndex}][cantidad]" class="form-control" value="1" min="1" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Precio Unitario</label>
                        <input type="number" step="0.01" name="productos[${currentIndex}][precio]" class="form-control" value="${precio}" required>
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
        
        $('#productosList').append(html);
        currentIndex++;
        
        // Evento para eliminar producto
        $('.remove-product').off('click').on('click', function() {
            $(this).closest('.product-row').remove();
        });
    }
    
    // Evento para eliminar producto (para los que ya existían)
    $('.remove-product').on('click', function() {
        $(this).closest('.product-row').remove();
    });
    
    // Validar stock al seleccionar almacén origen
    $('#almacen_origen_id').on('change', function() {
        const almacenId = $(this).val();
        if (almacenId) {
            $('.product-row').each(function() {
                const productoId = $(this).find('input[name$="[id]"]').val();
                const cantidadInput = $(this).find('input[name$="[cantidad]"]');
                
                $.ajax({
                    url: '{{ route("traslados.stock.producto") }}',
                    type: 'GET',
                    data: { producto_id: productoId, almacen_id: almacenId },
                    success: function(response) {
                        if (response.success) {
                            const stock = response.stock;
                            cantidadInput.attr('max', stock);
                            cantidadInput.parent().append(`<small class="text-muted">Stock disponible: ${stock}</small>`);
                        }
                    }
                });
            });
        }
    });
    
    // Validar que almacén origen y destino sean diferentes
    $('#almacen_destino_id').on('change', function() {
        const origen = $('#almacen_origen_id').val();
        const destino = $(this).val();
        
        if (origen && destino && origen === destino) {
            alert('El almacén de origen y destino no pueden ser iguales');
            $(this).val('');
        }
    });
    
    // Submit del formulario
    $('#formTraslado').on('submit', function(e) {
        if ($('#productosList .product-row').length === 0) {
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