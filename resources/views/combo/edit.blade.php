@extends('layouts.master')

@section('title', 'Editar Combo')
@section('css')
    <style>
        .producto-combo-item {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px 16px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.2s;
        }
        .producto-combo-item:hover {
            background: #f0fdf4;
            border-color: #86efac;
        }
        .producto-combo-item .info { flex: 1; }
        .producto-combo-item .actions { display: flex; align-items: center; gap: 8px; }
        .producto-combo-item .remove-producto { cursor: pointer; color: #ef4444; font-size: 20px; transition: all 0.2s; }
        .producto-combo-item .remove-producto:hover { color: #dc2626; transform: scale(1.15); }
        
        .precio-resumen {
            background: linear-gradient(135deg, #f0fdf4, #dcfce7);
            border: 1px solid #bbf7d0;
            border-radius: 16px;
            padding: 20px;
        }
        .search-producto-results {
            max-height: 220px;
            overflow-y: auto;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            display: none;
            position: absolute;
            z-index: 10;
            background: white;
            width: 100%;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .search-producto-results .resultado-item {
            padding: 10px 16px;
            cursor: pointer;
            border-bottom: 1px solid #f1f5f9;
            transition: background 0.15s;
        }
        .search-producto-results .resultado-item:hover { background: #f0fdf4; }
        .search-producto-results .resultado-item:last-child { border-bottom: none; }
        .foto-preview-container {
            width: 150px;
            height: 150px;
            border: 2px dashed #cbd5e1;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background: #f8fafc;
            transition: border-color 0.2s;
        }
        .foto-preview-container:hover { border-color: #6366f1; }
        .foto-preview-container img { width: 100%; height: 100%; object-fit: cover; }
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
                            <i class="bi bi-pencil"></i> Editar Combo
                        </h4>
                        <p class="mb-0 text-muted small">Modifica los datos del combo "{{ $combo->nombre }}"</p>
                    </div>
                    <div class="col text-end">
                        <a href="{{ route('combos.index') }}" class="btn btn-secondary">
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

                <form action="{{ route('combos.update', $combo->id) }}" method="POST" id="formCombo" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <input type="hidden" id="productos_hidden" name="productos" value="[]">

                    <div class="row g-3">
                        <!-- Foto del combo -->
                        <div class="col-md-3 text-center">
                            <label class="form-label fw-bold">Foto del Combo</label>
                            <div class="foto-preview-container mx-auto mb-2" id="fotoPreviewContainer">
                                <img id="fotoPreviewImg" src="{{ $combo->foto_url }}" alt="Preview">
                            </div>
                            <input type="file" name="foto" id="foto" class="form-control" accept="image/*">
                            <small class="text-muted">JPG, PNG, GIF - Máx. 2MB</small>
                        </div>

                        <!-- Info del combo -->
                        <div class="col-md-9">
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label for="nombre" class="form-label fw-bold">Nombre del Combo <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Ej: Combo Desayuno" value="{{ old('nombre', $combo->nombre) }}" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Estado</label>
                                    <div class="form-check form-switch mt-2">
                                        <input class="form-check-input" type="checkbox" id="estado" name="estado" value="1" {{ $combo->estado ? 'checked' : '' }}>
                                        <label class="form-check-label" for="estado">Activo</label>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <label for="descripcion" class="form-label fw-bold">Descripción</label>
                                    <textarea class="form-control" id="descripcion" name="descripcion" rows="2" placeholder="Descripción del combo (opcional)">{{ old('descripcion', $combo->descripcion) }}</textarea>
                                </div>
                                <div class="col-md-6">
                                    <label for="precio_combo" class="form-label fw-bold">Precio del Combo <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">S/</span>
                                        <input type="number" class="form-control" id="precio_combo" name="precio_combo" step="0.01" min="0" placeholder="0.00" value="{{ old('precio_combo', $combo->precio_combo) }}" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Precio Regular (calculado)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">S/</span>
                                        <input type="text" class="form-control bg-light" id="precio_regular_display" readonly value="{{ number_format($combo->precio_regular, 2) }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Buscar productos -->
                    <h6 class="fw-bold mb-3"><i class="bi bi-box-seam"></i> Productos del Combo <span class="text-danger">*</span></h6>
                    
                    <div class="position-relative mb-3">
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control" id="buscarProducto" placeholder="Buscar producto por nombre o código...">
                        </div>
                        <div class="search-producto-results" id="resultadosBusqueda"></div>
                    </div>

                    <!-- Lista de productos agregados -->
                    <div id="listaProductosCombo">
                        <div class="alert alert-info text-center" id="sinProductos">
                            <i class="bi bi-info-circle"></i> Busque y agregue productos al combo
                        </div>
                    </div>

                    <!-- Resumen de precios -->
                    <div class="precio-resumen mt-3">
                        <div class="row text-center">
                            <div class="col-md-4">
                                <small class="text-muted">Precio Regular</small>
                                <h5 class="text-secondary mb-0" id="resumenRegular">S/ 0.00</h5>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted">Precio Combo</small>
                                <h5 class="text-primary mb-0" id="resumenCombo">S/ 0.00</h5>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted">Ahorro</small>
                                <h5 class="text-success mb-0" id="resumenAhorro">S/ 0.00</h5>
                            </div>
                        </div>
                    </div>

                    <div class="text-end mt-4">
                        <a href="{{ route('combos.index') }}" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary" id="btnGuardar">
                            <i class="bi bi-save"></i> Actualizar Combo
                        </button>
                        <button type="button" class="btn btn-primary" id="btnLoading" style="display: none;" disabled>
                            <span class="spinner-border spinner-border-sm me-2"></span> Actualizando...
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
    // Productos precargados del combo
    var productosCombo = @json($productosDelCombo);
    var searchTimeout;

    // Renderizar productos que ya están en el combo
    renderizarProductosCombo();

    // Previsualización de foto
    $('#foto').on('change', function(e) {
        var file = e.target.files[0];
        if (file) {
            var reader = new FileReader();
            reader.onload = function(e) {
                $('#fotoPreviewImg').attr('src', e.target.result);
            };
            reader.readAsDataURL(file);
        }
    });

    // Buscar productos
    $('#buscarProducto').on('keyup', function() {
        clearTimeout(searchTimeout);
        var query = $(this).val();
        if (query.length < 2) { $('#resultadosBusqueda').hide().html(''); return; }
        
        searchTimeout = setTimeout(function() {
            $.ajax({
                url: '{{ route("combos.search.productos") }}',
                type: 'GET',
                data: { q: query },
                success: function(response) {
                    if (response.success && response.productos.length > 0) {
                        var html = '';
                        response.productos.forEach(function(p) {
                            var yaAgregado = productosCombo.some(item => item.producto_id === p.id);
                            html += `<div class="resultado-item ${yaAgregado ? 'bg-light text-muted' : ''}" 
                                         data-id="${p.id}" data-codigo="${p.codigo_interno}" 
                                         data-descripcion="${p.descripcion}" data-precio="${p.precio_venta}"
                                         ${yaAgregado ? 'style="pointer-events:none;opacity:0.5"' : ''}>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div><strong>${p.codigo_interno}</strong> - ${p.descripcion}</div>
                                            <span class="badge bg-success">S/ ${p.precio_venta.toFixed(2)}</span>
                                        </div>
                                    </div>`;
                        });
                        $('#resultadosBusqueda').html(html).show();
                        
                        $('.resultado-item').off('click').on('click', function() {
                            var productoId = parseInt($(this).data('id'));
                            if (productosCombo.some(item => item.producto_id === productoId)) return;
                            productosCombo.push({
                                producto_id: productoId,
                                codigo_interno: $(this).data('codigo'),
                                descripcion: $(this).data('descripcion'),
                                precio_venta: parseFloat($(this).data('precio')),
                                cantidad: 1
                            });
                            renderizarProductosCombo();
                            $('#buscarProducto').val('');
                            $('#resultadosBusqueda').hide().html('');
                        });
                    } else {
                        $('#resultadosBusqueda').html('<div class="resultado-item text-muted text-center"><i class="bi bi-search"></i> No se encontraron productos</div>').show();
                    }
                }
            });
        }, 350);
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('#buscarProducto, #resultadosBusqueda').length) {
            $('#resultadosBusqueda').hide();
        }
    });

    function renderizarProductosCombo() {
        if (productosCombo.length === 0) {
            $('#listaProductosCombo').html('<div class="alert alert-info text-center" id="sinProductos"><i class="bi bi-info-circle"></i> Busque y agregue productos al combo</div>');
            actualizarHidden();
            calcularResumen();
            return;
        }
        var html = '';
        productosCombo.forEach(function(item, index) {
            var subtotal = item.precio_venta * item.cantidad;
            html += `<div class="producto-combo-item" data-index="${index}">
                        <div class="info">
                            <strong>${item.codigo_interno}</strong> - ${item.descripcion}
                            <br><small class="text-muted">Precio unit: S/ ${item.precio_venta.toFixed(2)} | Subtotal: S/ ${subtotal.toFixed(2)}</small>
                        </div>
                        <div class="actions">
                            <div class="input-group input-group-sm" style="width: 120px;">
                                <button type="button" class="btn btn-outline-secondary btn-qty-minus" data-index="${index}">-</button>
                                <input type="number" class="form-control text-center input-cantidad" data-index="${index}" value="${item.cantidad}" min="1" style="max-width: 50px;">
                                <button type="button" class="btn btn-outline-secondary btn-qty-plus" data-index="${index}">+</button>
                            </div>
                            <i class="bi bi-x-circle-fill remove-producto" data-index="${index}"></i>
                        </div>
                    </div>`;
        });
        $('#listaProductosCombo').html(html);

        $('.btn-qty-minus').off('click').on('click', function() {
            var idx = $(this).data('index');
            if (productosCombo[idx].cantidad > 1) { productosCombo[idx].cantidad--; renderizarProductosCombo(); }
        });
        $('.btn-qty-plus').off('click').on('click', function() {
            var idx = $(this).data('index');
            productosCombo[idx].cantidad++;
            renderizarProductosCombo();
        });
        $('.input-cantidad').off('change').on('change', function() {
            var idx = $(this).data('index');
            var val = parseInt($(this).val());
            if (val >= 1) { productosCombo[idx].cantidad = val; renderizarProductosCombo(); }
        });
        $('.remove-producto').off('click').on('click', function() {
            var idx = $(this).data('index');
            productosCombo.splice(idx, 1);
            renderizarProductosCombo();
        });

        actualizarHidden();
        calcularResumen();
    }

    function actualizarHidden() {
        var data = productosCombo.map(function(item) {
            return { producto_id: item.producto_id, cantidad: item.cantidad };
        });
        $('#productos_hidden').val(JSON.stringify(data));
    }

    function calcularResumen() {
        var precioRegular = 0;
        productosCombo.forEach(function(item) { precioRegular += item.precio_venta * item.cantidad; });
        var precioCombo = parseFloat($('#precio_combo').val()) || 0;
        var ahorro = precioRegular - precioCombo;
        if (ahorro < 0) ahorro = 0;
        $('#precio_regular_display').val(precioRegular.toFixed(2));
        $('#resumenRegular').text('S/ ' + precioRegular.toFixed(2));
        $('#resumenCombo').text('S/ ' + precioCombo.toFixed(2));
        $('#resumenAhorro').text('S/ ' + ahorro.toFixed(2));
    }

    $('#precio_combo').on('keyup change', function() { calcularResumen(); });

    // Validar antes de enviar
    $('#formCombo').on('submit', function(e) {
        if (productosCombo.length === 0) {
            e.preventDefault();
            alert('Debe agregar al menos un producto al combo');
            return false;
        }
        actualizarHidden();
        $('#btnGuardar').hide();
        $('#btnLoading').show();
    });
});
</script>
@endsection
