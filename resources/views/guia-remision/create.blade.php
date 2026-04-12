@extends('layouts.master')

@section('title', 'Nueva Guía de Remisión')
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
        .section-title {
            background: #f0f2f5;
            padding: 10px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .section-title h5 {
            margin: 0;
            color: #2c3e50;
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
                            <i class="bi bi-plus-circle"></i> Nueva Guía de Remisión
                        </h4>
                        <p class="mb-0 text-muted small">Registra una nueva guía de remisión</p>
                    </div>
                    <div class="col text-end">
                        <a href="{{ route('guias-remision.index') }}" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <form id="formGuia">
                    @csrf
                    <!-- Datos del Traslado -->
                    <div class="section-title">
                        <h5><i class="bi bi-truck"></i> Datos del Traslado</h5>
                    </div>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Serie</label>
                            <input type="text" id="serie_documento" class="form-control" readonly>
                            <input type="hidden" id="serie" name="serie">
                            <input type="hidden" id="numero" name="numero">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Fecha de Emisión</label>
                            <input type="text" class="form-control" value="{{ date('d/m/Y H:i:s') }}" readonly>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Fecha de Traslado <span class="text-danger">*</span></label>
                            <input type="date" id="fecha_traslado" class="form-control" value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Motivo del Traslado <span class="text-danger">*</span></label>
                            <select id="motivo_traslado" class="form-control">
                                <option value="01">01 - VENTA</option>
                                <option value="02">02 - COMPRA</option>
                                <option value="03">03 - DEVOLUCIÓN</option>
                                <option value="04">04 - TRASLADO</option>
                                <option value="05">05 - CONSIGNACIÓN</option>
                                <option value="06">06 - OTROS</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Destinatario (Cliente) <span class="text-danger">*</span></label>
                            <select id="cliente_id" class="form-control">
                                <option value="">Seleccionar cliente</option>
                                @foreach($clientes as $cliente)
                                    <option value="{{ $cliente->id }}">{{ $cliente->numero_documento }} - {{ $cliente->nombre_razon_social }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Peso Bruto Total <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" step="0.001" id="peso_bruto_total" class="form-control" value="0">
                                <span class="input-group-text">KGM</span>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Modalidad de Traslado <span class="text-danger">*</span></label>
                            <select id="modalidad_traslado" class="form-control">
                                <option value="01">01 - TRANSPORTE PÚBLICO</option>
                                <option value="02" selected>02 - TRANSPORTE PRIVADO</option>
                            </select>
                        </div>
                    </div>

                    <!-- Puntos de Partida y Llegada -->
                    <div class="section-title mt-3">
                        <h5><i class="bi bi-geo-alt"></i> Puntos de Partida y Llegada</h5>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3">PUNTO DE PARTIDA</h6>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Ubigeo de Partida <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" id="ubigeo_partida" class="form-control" placeholder="110101" maxlength="6">
                                    <button type="button" class="btn btn-primary" id="btnBuscarUbigeoPartida"><i class="bi bi-search"></i> Buscar</button>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Dirección de Partida <span class="text-danger">*</span></label>
                                <textarea id="direccion_partida" class="form-control" rows="2" placeholder="MZA. E LOTE. 2 CAS. SAN MARTIN ICA - ICA - ICA"></textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3">PUNTO DE LLEGADA</h6>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Ubigeo de Llegada <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" id="ubigeo_llegada" class="form-control" placeholder="Ej: 150101" maxlength="6">
                                    <button type="button" class="btn btn-primary" id="btnBuscarUbigeoLlegada"><i class="bi bi-search"></i> Buscar</button>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Dirección de Llegada <span class="text-danger">*</span></label>
                                <textarea id="direccion_llegada" class="form-control" rows="2" placeholder="Dirección completa"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Datos del Transporte -->
                    <div class="section-title mt-3">
                        <h5><i class="bi bi-truck"></i> Datos del Transporte</h5>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Conductor / Chofer</label>
                            <div class="input-group">
                                <select id="conductor_id" class="form-control">
                                    <option value="">Seleccionar conductor</option>
                                    @foreach($conductores as $conductor)
                                        <option value="{{ $conductor->id }}">{{ $conductor->nombre }} - Lic: {{ $conductor->licencia }}</option>
                                    @endforeach
                                </select>
                                <button type="button" class="btn btn-primary" id="btnNuevoConductor"><i class="bi bi-plus-circle"></i> Nuevo</button>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Vehículo</label>
                            <div class="input-group">
                                <select id="vehiculo_id" class="form-control">
                                    <option value="">Seleccionar vehículo</option>
                                    @foreach($vehiculos as $vehiculo)
                                        <option value="{{ $vehiculo->id }}">{{ $vehiculo->placa }} - {{ $vehiculo->marca }} {{ $vehiculo->modelo }}</option>
                                    @endforeach
                                </select>
                                <button type="button" class="btn btn-primary" id="btnNuevoVehiculo"><i class="bi bi-plus-circle"></i> Nuevo</button>
                            </div>
                        </div>
                    </div>

                    <!-- Bienes a Trasladar -->
                    <div class="section-title mt-3">
                        <h5><i class="bi bi-box-seam"></i> Bienes a Trasladar</h5>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Buscar producto</label>
                        <input type="text" id="searchProducto" class="form-control" placeholder="Buscar por nombre o código de barras y presione Enter...">
                        <div id="searchResults" class="search-product-result mt-2"></div>
                    </div>

                    <div id="productosList" class="mb-3"></div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Observaciones Adicionales</label>
                        <textarea id="observaciones" class="form-control" rows="2" placeholder="Información extra para la guía de remisión..."></textarea>
                    </div>

                    <div class="text-end">
                        <a href="{{ route('guias-remision.index') }}" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary" id="btnGuardar">
                            <i class="bi bi-save"></i> Guardar Guía de Remisión
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nuevo Conductor -->
<div class="modal fade" id="modalNuevoConductor" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nuevo Conductor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-bold">Nombre</label>
                    <input type="text" id="conductor_nombre" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Licencia</label>
                    <input type="text" id="conductor_licencia" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Documento</label>
                    <input type="text" id="conductor_documento" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Teléfono</label>
                    <input type="text" id="conductor_telefono" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnGuardarConductor">Guardar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nuevo Vehículo -->
<div class="modal fade" id="modalNuevoVehiculo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nuevo Vehículo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-bold">Placa</label>
                    <input type="text" id="vehiculo_placa" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Marca</label>
                    <input type="text" id="vehiculo_marca" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Modelo</label>
                    <input type="text" id="vehiculo_modelo" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Color</label>
                    <input type="text" id="vehiculo_color" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnGuardarVehiculo">Guardar</button>
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
            url: '/guias-remision/serie',
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

    function renderizarProductos() {
        let html = '';
        productosAgregados.forEach((item, idx) => {
            html += `
                <div class="product-row" data-index="${idx}">
                    <input type="hidden" name="productos[${idx}][id]" value="${item.id}">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <p class="mb-0"><strong>${item.codigo}</strong> - ${item.descripcion}</p>
                        </div>
                        <div class="col-md-2">
                            <p class="mb-0">${item.unidad}</p>
                        </div>
                        <div class="col-md-2">
                            <input type="number" class="form-control cantidad-input" data-index="${idx}" value="${item.cantidad}" min="1">
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-sm btn-danger remove-product" data-index="${idx}">
                                <i class="bi bi-trash"></i> Eliminar
                            </button>
                        </div>
                    </div>
                </div>
            `;
        });
        $('#productosList').html(html);
        
        $('.cantidad-input').on('change', function() {
            const idx = $(this).data('index');
            productosAgregados[idx].cantidad = parseInt($(this).val()) || 0;
        });
        
        $('.remove-product').on('click', function() {
            const idx = $(this).data('index');
            productosAgregados.splice(idx, 1);
            renderizarProductos();
        });
    }

    // Buscar productos
    $('#searchProducto').on('keyup', function(e) {
        if (e.key === 'Enter') {
            const search = $(this).val();
            if (search.length < 2) return;
            
            $.ajax({
                url: '{{ route("guias-remision.search.productos") }}',
                type: 'GET',
                data: { q: search },
                success: function(response) {
                    if (response.success && response.productos.length > 0) {
                        const producto = response.productos[0];
                        
                        if (productosAgregados.some(p => p.id === producto.id)) {
                            alert('Este producto ya está agregado');
                            return;
                        }
                        
                        productosAgregados.push({
                            id: producto.id,
                            codigo: producto.codigo_interno,
                            descripcion: producto.descripcion,
                            cantidad: 1,
                            unidad: producto.unidad
                        });
                        
                        renderizarProductos();
                        $('#searchProducto').val('');
                    } else {
                        alert('Producto no encontrado');
                    }
                }
            });
        }
    });

    // Conductor
    $('#btnNuevoConductor').click(function() {
        $('#modalNuevoConductor').modal('show');
    });

    $('#btnGuardarConductor').click(function() {
        const data = {
            nombre: $('#conductor_nombre').val(),
            licencia: $('#conductor_licencia').val(),
            documento: $('#conductor_documento').val(),
            telefono: $('#conductor_telefono').val()
        };
        
        $.ajax({
            url: '/guias-remision/conductores',
            type: 'POST',
            data: JSON.stringify(data),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(response) {
                if (response.success) {
                    $('#conductor_id').append(`<option value="${response.conductor.id}" selected>${response.conductor.nombre} - Lic: ${response.conductor.licencia}</option>`);
                    $('#modalNuevoConductor').modal('hide');
                    $('#conductor_nombre, #conductor_licencia, #conductor_documento, #conductor_telefono').val('');
                }
            }
        });
    });

    // Vehículo
    $('#btnNuevoVehiculo').click(function() {
        $('#modalNuevoVehiculo').modal('show');
    });

    $('#btnGuardarVehiculo').click(function() {
        const data = {
            placa: $('#vehiculo_placa').val(),
            marca: $('#vehiculo_marca').val(),
            modelo: $('#vehiculo_modelo').val(),
            color: $('#vehiculo_color').val()
        };
        
        $.ajax({
            url: '/guias-remision/vehiculos',
            type: 'POST',
            data: JSON.stringify(data),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(response) {
                if (response.success) {
                    $('#vehiculo_id').append(`<option value="${response.vehiculo.id}" selected>${response.vehiculo.placa} - ${response.vehiculo.marca} ${response.vehiculo.modelo}</option>`);
                    $('#modalNuevoVehiculo').modal('hide');
                    $('#vehiculo_placa, #vehiculo_marca, #vehiculo_modelo, #vehiculo_color').val('');
                }
            }
        });
    });

    // Guardar
    $('#formGuia').on('submit', function(e) {
        e.preventDefault();
        
        if (productosAgregados.length === 0) {
            alert('Debe agregar al menos un producto');
            return;
        }
        
        if (!$('#cliente_id').val()) {
            alert('Debe seleccionar un cliente');
            return;
        }
        
        const productos = productosAgregados.map(item => ({
            id: item.id,
            cantidad: item.cantidad
        }));
        
        const data = {
            fecha_traslado: $('#fecha_traslado').val(),
            motivo_traslado: $('#motivo_traslado').val(),
            cliente_id: $('#cliente_id').val(),
            peso_bruto_total: $('#peso_bruto_total').val(),
            modalidad_traslado: $('#modalidad_traslado').val(),
            ubigeo_partida: $('#ubigeo_partida').val(),
            direccion_partida: $('#direccion_partida').val(),
            ubigeo_llegada: $('#ubigeo_llegada').val(),
            direccion_llegada: $('#direccion_llegada').val(),
            conductor_id: $('#conductor_id').val(),
            vehiculo_id: $('#vehiculo_id').val(),
            observaciones: $('#observaciones').val(),
            productos: productos
        };
        
        $.ajax({
            url: '/guias-remision',
            type: 'POST',
            data: JSON.stringify(data),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(response) {
                if (response.success) {
                    window.location.href = '/guias-remision';
                } else {
                    alert(response.message);
                }
            },
            error: function(xhr) {
                alert(xhr.responseJSON?.message || 'Error al guardar la guía de remisión');
            }
        });
    });
});
</script>
@endsection