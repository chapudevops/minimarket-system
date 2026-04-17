@extends('layouts.master')

@section('title', 'Nueva Guía de Remisión')
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
        .section-title {
            background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%);
            padding: 10px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .section-title h5 {
            margin: 0;
            color: #1a2a3a;
            font-weight: 600;
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
        .badge-motivo {
            font-size: 10px;
            padding: 4px 8px;
        }
        .ubigeo-result {
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            display: none;
            position: absolute;
            background: white;
            z-index: 1000;
            width: 100%;
        }
        .ubigeo-item {
            padding: 8px 12px;
            cursor: pointer;
            border-bottom: 1px solid #f0f0f0;
        }
        .ubigeo-item:hover {
            background: #e8f5e9;
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
                            <i class="bi bi-truck"></i> Nueva Guía de Remisión
                        </h4>
                        <p class="mb-0 text-muted small">Registra una nueva guía de remisión electrónica</p>
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
                            <input type="text" id="serie_documento" class="form-control bg-light" readonly>
                            <input type="hidden" id="serie" name="serie">
                            <input type="hidden" id="numero" name="numero">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Fecha de Emisión</label>
                            <input type="text" class="form-control bg-light" value="{{ date('d/m/Y H:i:s') }}" readonly>
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
                                <option value="04">04 - TRASLADO ENTRE ESTABLECIMIENTOS</option>
                                <option value="05">05 - CONSIGNACIÓN</option>
                                <option value="06">06 - OTROS</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Destinatario (Cliente) <span class="text-danger">*</span></label>
                            <select id="cliente_id" class="form-control" style="width: 100%;">
                                <option value="">🔍 Buscar cliente por nombre o documento...</option>
                                @foreach($clientes as $cliente)
                                    <option value="{{ $cliente->id }}" 
                                        data-documento="{{ $cliente->numero_documento }}"
                                        data-nombre="{{ $cliente->nombre_razon_social }}"
                                        data-direccion="{{ $cliente->direccion ?? '-' }}">
                                        {{ $cliente->numero_documento }} - {{ $cliente->nombre_razon_social }}
                                    </option>
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
                                    <small>DIRECCIÓN</small>
                                    <p class="mb-0 fw-bold" id="infoDireccionCliente">-</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Puntos de Partida y Llegada -->
                    <div class="section-title mt-3">
                        <h5><i class="bi bi-geo-alt"></i> Puntos de Partida y Llegada</h5>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3 text-primary">📍 PUNTO DE PARTIDA</h6>
                            <div class="mb-3 position-relative">
                                <label class="form-label fw-bold">Ubigeo de Partida <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" id="ubigeo_partida" class="form-control" placeholder="Ej: 110101" maxlength="6">
                                    <button type="button" class="btn btn-outline-primary" id="btnBuscarUbigeoPartida">
                                        <i class="bi bi-search"></i> Buscar
                                    </button>
                                </div>
                                <div id="ubigeoResultsPartida" class="ubigeo-result"></div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Dirección de Partida <span class="text-danger">*</span></label>
                                <textarea id="direccion_partida" class="form-control" rows="2" placeholder="MZA. E LOTE. 2 CAS. SAN MARTIN ICA - ICA - ICA"></textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3 text-success">📍 PUNTO DE LLEGADA</h6>
                            <div class="mb-3 position-relative">
                                <label class="form-label fw-bold">Ubigeo de Llegada <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" id="ubigeo_llegada" class="form-control" placeholder="Ej: 150101" maxlength="6">
                                    <button type="button" class="btn btn-outline-primary" id="btnBuscarUbigeoLlegada">
                                        <i class="bi bi-search"></i> Buscar
                                    </button>
                                </div>
                                <div id="ubigeoResultsLlegada" class="ubigeo-result"></div>
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
                                <select id="conductor_id" class="form-control" style="width: 100%;">
                                    <option value="">🔍 Seleccionar conductor...</option>
                                    @foreach($conductores as $conductor)
                                        <option value="{{ $conductor->id }}">{{ $conductor->nombre }} - Lic: {{ $conductor->licencia }} - Doc: {{ $conductor->documento }}</option>
                                    @endforeach
                                </select>
                                <button type="button" class="btn btn-primary" id="btnNuevoConductor">
                                    <i class="bi bi-plus-circle"></i> Nuevo
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Vehículo</label>
                            <div class="input-group">
                                <select id="vehiculo_id" class="form-control" style="width: 100%;">
                                    <option value="">🔍 Seleccionar vehículo...</option>
                                    @foreach($vehiculos as $vehiculo)
                                        <option value="{{ $vehiculo->id }}">{{ $vehiculo->placa }} - {{ $vehiculo->marca }} {{ $vehiculo->modelo }} ({{ $vehiculo->color }})</option>
                                    @endforeach
                                </select>
                                <button type="button" class="btn btn-primary" id="btnNuevoVehiculo">
                                    <i class="bi bi-plus-circle"></i> Nuevo
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Bienes a Trasladar -->
                    <div class="section-title mt-3">
                        <h5><i class="bi bi-box-seam"></i> Bienes a Trasladar</h5>
                    </div>
                    <div class="mb-3 position-relative">
                        <label class="form-label fw-bold">Buscar producto</label>
                        <input type="text" id="searchProducto" class="form-control" placeholder="Buscar por nombre o código de barras...">
                        <div id="searchResults" class="search-product-result mt-2"></div>
                    </div>

                    <div id="productosList" class="mb-3">
                        <div class="alert alert-info text-center">
                            <i class="bi bi-info-circle"></i> Busque y seleccione productos para agregar
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Observaciones Adicionales</label>
                        <textarea id="observaciones" class="form-control" rows="2" placeholder="Información extra para la guía de remisión..."></textarea>
                    </div>

                    <div class="text-end">
                        <a href="{{ route('guias-remision.index') }}" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary" id="btnGuardar">
                            <i class="bi bi-save"></i> Guardar Guía de Remisión
                        </button>
                        <button type="button" class="btn btn-primary" id="btnLoading" style="display: none;" disabled>
                            <span class="spinner-border spinner-border-sm me-2"></span> Procesando...
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
                <h5 class="modal-title"><i class="bi bi-person-badge"></i> Nuevo Conductor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-bold">Nombre Completo</label>
                    <input type="text" id="conductor_nombre" class="form-control" placeholder="Juan Pérez">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Licencia</label>
                    <input type="text" id="conductor_licencia" class="form-control" placeholder="A2B-1234">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Documento</label>
                    <input type="text" id="conductor_documento" class="form-control" placeholder="DNI: 12345678">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Teléfono</label>
                    <input type="text" id="conductor_telefono" class="form-control" placeholder="999 999 999">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnGuardarConductor">Guardar Conductor</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nuevo Vehículo -->
<div class="modal fade" id="modalNuevoVehiculo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-car-front"></i> Nuevo Vehículo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-bold">Placa</label>
                    <input type="text" id="vehiculo_placa" class="form-control" placeholder="ABC-123" style="text-transform: uppercase">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Marca</label>
                    <input type="text" id="vehiculo_marca" class="form-control" placeholder="Toyota">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Modelo</label>
                    <input type="text" id="vehiculo_modelo" class="form-control" placeholder="Hilux">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Color</label>
                    <input type="text" id="vehiculo_color" class="form-control" placeholder="Blanco">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnGuardarVehiculo">Guardar Vehículo</button>
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
        
        $('#infoDocumento').text(documento);
        $('#infoNombreCliente').text(nombre);
        $('#infoDireccionCliente').text(direccion);
        $('#infoClienteContainer').show();
    });

    // ========== SELECT2 PARA CONDUCTOR ==========
    $('#conductor_id').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: '🔍 Buscar conductor...',
        allowClear: true,
        language: {
            noResults: function() {
                return "No se encontraron conductores";
            }
        }
    });

    // ========== SELECT2 PARA VEHÍCULO ==========
    $('#vehiculo_id').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: '🔍 Buscar vehículo...',
        allowClear: true,
        language: {
            noResults: function() {
                return "No se encontraron vehículos";
            }
        }
    });

    // Cargar serie del documento
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

    function renderizarProductos() {
        let html = '';
        
        if (productosAgregados.length === 0) {
            html = '<div class="alert alert-info text-center"><i class="bi bi-info-circle"></i> Busque y seleccione productos para agregar</div>';
        } else {
            productosAgregados.forEach((item, idx) => {
                html += `
                    <div class="product-row" data-index="${idx}">
                        <input type="hidden" name="productos[${idx}][id]" value="${item.id}">
                        <div class="row align-items-center">
                            <div class="col-md-5">
                                <label class="form-label fw-bold small text-muted">Producto</label>
                                <p class="mb-0"><strong>${escapeHtml(item.codigo)}</strong><br><small>${escapeHtml(item.descripcion)}</small></p>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-bold small text-muted">Unidad</label>
                                <p class="mb-0">${item.unidad || 'UNIDAD'}</p>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold small text-muted">Cantidad</label>
                                <input type="number" class="form-control cantidad-input" data-index="${idx}" value="${item.cantidad}" min="1" step="1">
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
                    </div>
                `;
            });
        }
        
        $('#productosList').html(html);
        
        $('.cantidad-input').off('change').on('change', function() {
            const idx = $(this).data('index');
            let cantidad = parseInt($(this).val()) || 1;
            if (cantidad < 1) cantidad = 1;
            productosAgregados[idx].cantidad = cantidad;
            $(this).val(cantidad);
        });
        
        $('.remove-product').off('click').on('click', function() {
            const idx = $(this).data('index');
            Swal.fire({
                title: '¿Eliminar producto?',
                text: `¿Desea eliminar "${productosAgregados[idx].descripcion}" de la guía?`,
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
                url: '{{ route("guias-remision.search.productos") }}',
                type: 'GET',
                data: { q: search },
                beforeSend: function() {
                    $('#searchResults').html('<div class="p-3 text-center"><div class="spinner-border spinner-border-sm text-primary"></div> Buscando...</div>').show();
                },
                success: function(response) {
                    if (response.success && response.productos && response.productos.length > 0) {
                        let html = '';
                        response.productos.forEach(producto => {
                            html += `
                                <div class="product-result-item" 
                                     data-id="${producto.id}" 
                                     data-codigo="${escapeHtml(producto.codigo_interno)}" 
                                     data-descripcion="${escapeHtml(producto.descripcion)}" 
                                     data-unidad="${producto.unidad || 'UNIDAD'}">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong>${escapeHtml(producto.codigo_interno)}</strong> - ${escapeHtml(producto.descripcion)}
                                        </div>
                                    </div>
                                    <small class="text-muted">Unidad: ${producto.unidad || 'UNIDAD'} | Stock: ${producto.stock || 0}</small>
                                </div>
                            `;
                        });
                        $('#searchResults').html(html).show();
                        
                        $('.product-result-item').off('click').on('click', function() {
                            const id = $(this).data('id');
                            const codigo = $(this).data('codigo');
                            const descripcion = $(this).data('descripcion');
                            const unidad = $(this).data('unidad');
                            
                            if (productosAgregados.some(p => p.id === id)) {
                                Swal.fire({
                                    icon: 'warning',
                                    title: 'Producto duplicado',
                                    text: 'Este producto ya está agregado a la guía',
                                    confirmButtonColor: '#3085d6'
                                });
                                return;
                            }
                            
                            productosAgregados.push({
                                id: id,
                                codigo: codigo,
                                descripcion: descripcion,
                                cantidad: 1,
                                unidad: unidad
                            });
                            
                            renderizarProductos();
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

    // Buscar Ubigeo Partida
    $('#btnBuscarUbigeoPartida').click(function() {
        const search = $('#ubigeo_partida').val();
        if (search.length < 2) return;
        
        $.ajax({
            url: '{{ route("guias-remision.ubigeo") }}',
            type: 'GET',
            data: { q: search },
            success: function(response) {
                if (response.length > 0) {
                    let html = '';
                    response.forEach(ubigeo => {
                        html += `<div class="ubigeo-item" data-codigo="${ubigeo.codigo}" data-texto="${ubigeo.departamento} - ${ubigeo.provincia} - ${ubigeo.distrito}">
                                    <strong>${ubigeo.codigo}</strong> - ${ubigeo.departamento} - ${ubigeo.provincia} - ${ubigeo.distrito}
                                </div>`;
                    });
                    $('#ubigeoResultsPartida').html(html).show();
                    
                    $('.ubigeo-item').off('click').on('click', function() {
                        $('#ubigeo_partida').val($(this).data('codigo'));
                        $('#ubigeoResultsPartida').hide();
                    });
                } else {
                    $('#ubigeoResultsPartida').html('<div class="p-3 text-center text-muted">No se encontraron ubicaciones</div>').show();
                }
            }
        });
    });

    // Buscar Ubigeo Llegada
    $('#btnBuscarUbigeoLlegada').click(function() {
        const search = $('#ubigeo_llegada').val();
        if (search.length < 2) return;
        
        $.ajax({
            url: '{{ route("guias-remision.ubigeo") }}',
            type: 'GET',
            data: { q: search },
            success: function(response) {
                if (response.length > 0) {
                    let html = '';
                    response.forEach(ubigeo => {
                        html += `<div class="ubigeo-item" data-codigo="${ubigeo.codigo}" data-texto="${ubigeo.departamento} - ${ubigeo.provincia} - ${ubigeo.distrito}">
                                    <strong>${ubigeo.codigo}</strong> - ${ubigeo.departamento} - ${ubigeo.provincia} - ${ubigeo.distrito}
                                </div>`;
                    });
                    $('#ubigeoResultsLlegada').html(html).show();
                    
                    $('.ubigeo-item').off('click').on('click', function() {
                        $('#ubigeo_llegada').val($(this).data('codigo'));
                        $('#ubigeoResultsLlegada').hide();
                    });
                } else {
                    $('#ubigeoResultsLlegada').html('<div class="p-3 text-center text-muted">No se encontraron ubicaciones</div>').show();
                }
            }
        });
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('#ubigeo_partida, #btnBuscarUbigeoPartida, #ubigeoResultsPartida').length) {
            $('#ubigeoResultsPartida').hide();
        }
        if (!$(e.target).closest('#ubigeo_llegada, #btnBuscarUbigeoLlegada, #ubigeoResultsLlegada').length) {
            $('#ubigeoResultsLlegada').hide();
        }
    });

    // Nuevo Conductor
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
        
        if (!data.nombre || !data.licencia) {
            Swal.fire({
                icon: 'warning',
                title: 'Campos requeridos',
                text: 'Nombre y licencia son obligatorios',
                confirmButtonColor: '#3085d6'
            });
            return;
        }
        
        $.ajax({
            url: '/guias-remision/conductores',
            type: 'POST',
            data: JSON.stringify(data),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(response) {
                if (response.success) {
                    $('#conductor_id').append(new Option(`${response.conductor.nombre} - Lic: ${response.conductor.licencia}`, response.conductor.id, true, true));
                    $('#conductor_id').trigger('change');
                    $('#modalNuevoConductor').modal('hide');
                    $('#conductor_nombre, #conductor_licencia, #conductor_documento, #conductor_telefono').val('');
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Conductor registrado',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error al guardar conductor'
                });
            }
        });
    });

    // Nuevo Vehículo
    $('#btnNuevoVehiculo').click(function() {
        $('#modalNuevoVehiculo').modal('show');
    });

    $('#btnGuardarVehiculo').click(function() {
        const data = {
            placa: $('#vehiculo_placa').val().toUpperCase(),
            marca: $('#vehiculo_marca').val(),
            modelo: $('#vehiculo_modelo').val(),
            color: $('#vehiculo_color').val()
        };
        
        if (!data.placa || !data.marca) {
            Swal.fire({
                icon: 'warning',
                title: 'Campos requeridos',
                text: 'Placa y marca son obligatorios',
                confirmButtonColor: '#3085d6'
            });
            return;
        }
        
        $.ajax({
            url: '/guias-remision/vehiculos',
            type: 'POST',
            data: JSON.stringify(data),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(response) {
                if (response.success) {
                    $('#vehiculo_id').append(new Option(`${response.vehiculo.placa} - ${response.vehiculo.marca} ${response.vehiculo.modelo}`, response.vehiculo.id, true, true));
                    $('#vehiculo_id').trigger('change');
                    $('#modalNuevoVehiculo').modal('hide');
                    $('#vehiculo_placa, #vehiculo_marca, #vehiculo_modelo, #vehiculo_color').val('');
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Vehículo registrado',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error al guardar vehículo'
                });
            }
        });
    });

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Guardar Guía de Remisión
    $('#formGuia').on('submit', function(e) {
        e.preventDefault();
        
        if (productosAgregados.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Sin productos',
                text: 'Debe agregar al menos un producto',
                confirmButtonColor: '#3085d6'
            });
            return;
        }
        
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
        
        const productos = productosAgregados.map(item => ({
            id: item.id,
            cantidad: item.cantidad
        }));
        
        const data = {
            fecha_traslado: $('#fecha_traslado').val(),
            motivo_traslado: $('#motivo_traslado').val(),
            cliente_id: parseInt(clienteId),
            peso_bruto_total: parseFloat($('#peso_bruto_total').val()) || 0,
            modalidad_traslado: $('#modalidad_traslado').val(),
            ubigeo_partida: $('#ubigeo_partida').val(),
            direccion_partida: $('#direccion_partida').val(),
            ubigeo_llegada: $('#ubigeo_llegada').val(),
            direccion_llegada: $('#direccion_llegada').val(),
            conductor_id: $('#conductor_id').val() || null,
            vehiculo_id: $('#vehiculo_id').val() || null,
            observaciones: $('#observaciones').val(),
            productos: productos
        };
        
        $('#btnGuardar').hide();
        $('#btnLoading').show();
        
        $.ajax({
            url: '/guias-remision',
            type: 'POST',
            data: JSON.stringify(data),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Guía de Remisión Registrada!',
                        text: response.message || 'La guía de remisión ha sido creada exitosamente',
                        confirmButtonColor: '#3085d6'
                    }).then(() => {
                        window.location.href = '/guias-remision';
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message || 'Error al guardar la guía'
                    });
                    $('#btnGuardar').show();
                    $('#btnLoading').hide();
                }
            },
            error: function(xhr) {
                let errorMsg = 'Error al guardar la guía de remisión';
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