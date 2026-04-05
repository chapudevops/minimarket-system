@extends('layouts.master')

@section('title', 'Productos')
@section('css')
    <link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
@endsection 

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h4 class="mb-0">
                            <i class="bi bi-box-seam"></i> Listado de Productos
                        </h4>
                        <p class="mb-0 text-muted small">Administra los productos de tu minimarket</p>
                    </div>
                    <div class="col text-end">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCreate">
                            <i class="bi bi-plus-circle"></i> Nuevo Producto
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div id="alert-messages"></div>

                <div class="table-responsive">
                    <table id="productosTable" class="table table-striped table-bordered" style="width:100%">
                        <thead>
                            <tr>
                                <th width="3%">#</th>
                                <th width="10%">Código Interno</th>
                                <th width="20%">Descripción</th>
                                <th width="8%">Unidad</th>
                                <th width="10%">Precio Venta</th>
                                <th width="8%">Stock</th>
                                <th width="8%">Estado</th>
                                <th width="10%">Fecha Creación</th>
                                <th width="10%">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Los datos se cargarán vía AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Crear -->
<div class="modal fade" id="modalCreate" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-plus-circle"></i> Nuevo Producto
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formCreate">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Código Interno <span class="text-danger">*</span></label>
                            <input type="text" name="codigo_interno" id="codigo_interno_create" class="form-control" placeholder="PROD001">
                            <div class="invalid-feedback" id="error-codigo_interno_create"></div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Código de Barras</label>
                            <input type="text" name="codigo_barras" id="codigo_barras_create" class="form-control" placeholder="789123456001">
                            <div class="invalid-feedback" id="error-codigo_barras_create"></div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Unidad <span class="text-danger">*</span></label>
                            <select name="unidad" id="unidad_create" class="form-control">
                                <option value="UNIDAD">UNIDAD</option>
                                <option value="KG">KG</option>
                                <option value="LITRO">LITRO</option>
                                <option value="DOCENA">DOCENA</option>
                                <option value="CAJA">CAJA</option>
                                <option value="HORA">HORA (SERVICIOS)</option>
                                <option value="MES">MES (SERVICIOS)</option>
                            </select>
                            <div class="invalid-feedback" id="error-unidad_create"></div>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">Descripción <span class="text-danger">*</span></label>
                            <textarea name="descripcion" id="descripcion_create" class="form-control" rows="2" placeholder="Descripción del producto..."></textarea>
                            <div class="invalid-feedback" id="error-descripcion_create"></div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Marca</label>
                            <input type="text" name="marca" id="marca_create" class="form-control" placeholder="HP, Logitech, etc.">
                            <div class="invalid-feedback" id="error-marca_create"></div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Presentación</label>
                            <input type="text" name="presentacion" id="presentacion_create" class="form-control" placeholder="Caja, Blister, etc.">
                            <div class="invalid-feedback" id="error-presentacion_create"></div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Operación <span class="text-danger">*</span></label>
                            <select name="operacion" id="operacion_create" class="form-control">
                                <option value="GRAVADO">Gravado - Operación Onerosa</option>
                                <option value="EXONERADO">Exonerado - Operación Onerosa</option>
                                <option value="INAFECTO">Inafecto - Operación Onerosa</option>
                            </select>
                            <div class="invalid-feedback" id="error-operacion_create"></div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Precio Compra <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="precio_compra" id="precio_compra_create" class="form-control" placeholder="0.00">
                            <div class="invalid-feedback" id="error-precio_compra_create"></div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Precio Venta <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="precio_venta" id="precio_venta_create" class="form-control" placeholder="0.00">
                            <div class="invalid-feedback" id="error-precio_venta_create"></div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Fecha de Vencimiento</label>
                            <input type="date" name="fecha_vencimiento" id="fecha_vencimiento_create" class="form-control">
                            <div class="invalid-feedback" id="error-fecha_vencimiento_create"></div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Tipo Producto <span class="text-danger">*</span></label>
                            <select name="tipo_producto" id="tipo_producto_create" class="form-control">
                                <option value="PRODUCTO">Producto</option>
                                <option value="SERVICIO">Servicio</option>
                            </select>
                            <div class="invalid-feedback" id="error-tipo_producto_create"></div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="detraccion" class="form-check-input" id="detraccion_create" value="1">
                                <label class="form-check-label fw-bold" for="detraccion_create">Configuración de Detracción</label>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Stock Inicial</label>
                            <input type="number" name="stock" id="stock_create" class="form-control" placeholder="0" value="0">
                            <div class="invalid-feedback" id="error-stock_create"></div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Stock Mínimo</label>
                            <input type="number" name="stock_minimo" id="stock_minimo_create" class="form-control" placeholder="0" value="0">
                            <div class="invalid-feedback" id="error-stock_minimo_create"></div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="estado" class="form-check-input" id="estado_create" value="1" checked>
                                <label class="form-check-label fw-bold" for="estado_create">Activo</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnGuardarCreate">
                        <i class="bi bi-save"></i> Guardar
                    </button>
                    <button type="button" class="btn btn-primary" id="btnLoadingCreate" style="display: none;" disabled>
                        <span class="spinner-border spinner-border-sm me-2"></span> Guardando...
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar -->
<div class="modal fade" id="modalEdit" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-pencil"></i> Editar Producto
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formEdit">
                @csrf
                @method('PUT')
                <input type="hidden" id="edit_id" name="id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Código Interno <span class="text-danger">*</span></label>
                            <input type="text" name="codigo_interno" id="codigo_interno_edit" class="form-control">
                            <div class="invalid-feedback" id="error-codigo_interno_edit"></div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Código de Barras</label>
                            <input type="text" name="codigo_barras" id="codigo_barras_edit" class="form-control">
                            <div class="invalid-feedback" id="error-codigo_barras_edit"></div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Unidad <span class="text-danger">*</span></label>
                            <select name="unidad" id="unidad_edit" class="form-control">
                                <option value="UNIDAD">UNIDAD</option>
                                <option value="KG">KG</option>
                                <option value="LITRO">LITRO</option>
                                <option value="DOCENA">DOCENA</option>
                                <option value="CAJA">CAJA</option>
                                <option value="HORA">HORA (SERVICIOS)</option>
                                <option value="MES">MES (SERVICIOS)</option>
                            </select>
                            <div class="invalid-feedback" id="error-unidad_edit"></div>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">Descripción <span class="text-danger">*</span></label>
                            <textarea name="descripcion" id="descripcion_edit" class="form-control" rows="2"></textarea>
                            <div class="invalid-feedback" id="error-descripcion_edit"></div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Marca</label>
                            <input type="text" name="marca" id="marca_edit" class="form-control">
                            <div class="invalid-feedback" id="error-marca_edit"></div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Presentación</label>
                            <input type="text" name="presentacion" id="presentacion_edit" class="form-control">
                            <div class="invalid-feedback" id="error-presentacion_edit"></div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Operación <span class="text-danger">*</span></label>
                            <select name="operacion" id="operacion_edit" class="form-control">
                                <option value="GRAVADO">Gravado - Operación Onerosa</option>
                                <option value="EXONERADO">Exonerado - Operación Onerosa</option>
                                <option value="INAFECTO">Inafecto - Operación Onerosa</option>
                            </select>
                            <div class="invalid-feedback" id="error-operacion_edit"></div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Precio Compra <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="precio_compra" id="precio_compra_edit" class="form-control">
                            <div class="invalid-feedback" id="error-precio_compra_edit"></div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Precio Venta <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="precio_venta" id="precio_venta_edit" class="form-control">
                            <div class="invalid-feedback" id="error-precio_venta_edit"></div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Fecha de Vencimiento</label>
                            <input type="date" name="fecha_vencimiento" id="fecha_vencimiento_edit" class="form-control">
                            <div class="invalid-feedback" id="error-fecha_vencimiento_edit"></div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Tipo Producto <span class="text-danger">*</span></label>
                            <select name="tipo_producto" id="tipo_producto_edit" class="form-control">
                                <option value="PRODUCTO">Producto</option>
                                <option value="SERVICIO">Servicio</option>
                            </select>
                            <div class="invalid-feedback" id="error-tipo_producto_edit"></div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="detraccion" class="form-check-input" id="detraccion_edit" value="1">
                                <label class="form-check-label fw-bold" for="detraccion_edit">Configuración de Detracción</label>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Stock</label>
                            <input type="number" name="stock" id="stock_edit" class="form-control" readonly>
                            <small class="text-muted">El stock se actualiza con movimientos</small>
                            <div class="invalid-feedback" id="error-stock_edit"></div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Stock Mínimo</label>
                            <input type="number" name="stock_minimo" id="stock_minimo_edit" class="form-control">
                            <div class="invalid-feedback" id="error-stock_minimo_edit"></div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="estado" class="form-check-input" id="estado_edit" value="1">
                                <label class="form-check-label fw-bold" for="estado_edit">Activo</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnGuardarEdit">
                        <i class="bi bi-save"></i> Actualizar
                    </button>
                    <button type="button" class="btn btn-primary" id="btnLoadingEdit" style="display: none;" disabled>
                        <span class="spinner-border spinner-border-sm me-2"></span> Actualizando...
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Ver Detalles (Normal) -->
<div class="modal fade" id="modalView" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title text-white">
                    <i class="bi bi-info-circle"></i> Detalles del Producto
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="productoDetails">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="mt-2">Cargando detalles...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Eliminar -->
<div class="modal fade" id="modalDelete" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title text-white">
                    <i class="bi bi-exclamation-triangle"></i> Confirmar Eliminación
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de eliminar este producto?</p>
                <p class="fw-bold text-danger" id="delete-nombre"></p>
                <input type="hidden" id="delete_id">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="btnConfirmDelete">
                    <i class="bi bi-trash"></i> Eliminar
                </button>
                <button type="button" class="btn btn-danger" id="btnLoadingDelete" style="display: none;" disabled>
                    <span class="spinner-border spinner-border-sm me-2"></span> Eliminando...
                </button>
            </div>
        </div>
    </div>
</div>

@endsection 

@section('scripts')  
<script src="{{ URL::asset('build/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ URL::asset('build/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
<script>
$(document).ready(function() {
    // Inicializar DataTable con AJAX
    var table = $('#productosTable').DataTable({
        "processing": true,
        "serverSide": false,
        "ajax": {
            "url": "/productos/data",
            "type": "GET",
            "dataSrc": "data"
        },
        "pageLength": 10,
        "lengthMenu": [[5, 10, 15, 25, 50, -1], [5, 10, 15, 25, 50, "Todos"]],
        "columns": [
            { "data": "correlativo" },
            { "data": "codigo_interno" },
            { "data": "descripcion" },
            { "data": "unidad" },
            { "data": "precio_venta" },
            { "data": "stock" },
            { "data": "estado_texto" },
            { "data": "created_at" },
            { 
                "data": "acciones",
                "orderable": false,
                "searchable": false
            }
        ],
        "order": [[2, 'asc']],
        "language": {
            "sProcessing": "Procesando...",
            "sLengthMenu": "Mostrar _MENU_ registros",
            "sZeroRecords": "No se encontraron resultados",
            "sEmptyTable": "Ningún dato disponible en esta tabla",
            "sInfo": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
            "sInfoEmpty": "Mostrando registros del 0 al 0 de un total de 0 registros",
            "sInfoFiltered": "(filtrado de un total de _MAX_ registros)",
            "sSearch": "Buscar:",
            "oPaginate": {
                "sFirst": "Primero",
                "sLast": "Último",
                "sNext": "Siguiente",
                "sPrevious": "Anterior"
            }
        }
    });

    // ========== VER DETALLES (MODAL NORMAL) ==========
    $('#productosTable').on('click', '.btn-view', function() {
        var id = $(this).data('id');
        
        $('#productoDetails').html(`
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <p class="mt-2">Cargando detalles...</p>
            </div>
        `);
        
        $('#modalView').modal('show');
        
        $.ajax({
            url: '/productos/' + id,
            type: 'GET',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    var data = response.data;
                    $('#productoDetails').html(`
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="fw-bold text-muted">Código Interno:</label>
                                <p class="mb-0">${data.codigo_interno}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="fw-bold text-muted">Código de Barras:</label>
                                <p class="mb-0">${data.codigo_barras}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="fw-bold text-muted">Unidad:</label>
                                <p class="mb-0">${data.unidad}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="fw-bold text-muted">Marca:</label>
                                <p class="mb-0">${data.marca}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="fw-bold text-muted">Presentación:</label>
                                <p class="mb-0">${data.presentacion}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="fw-bold text-muted">Operación:</label>
                                <p class="mb-0">${data.operacion_texto}</p>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="fw-bold text-muted">Descripción:</label>
                                <p class="mb-0">${data.descripcion}</p>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="fw-bold text-muted">Precio Compra:</label>
                                <p class="mb-0">${data.precio_compra}</p>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="fw-bold text-muted">Precio Venta:</label>
                                <p class="mb-0">${data.precio_venta}</p>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="fw-bold text-muted">Fecha Vencimiento:</label>
                                <p class="mb-0">${data.fecha_vencimiento}</p>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="fw-bold text-muted">Tipo Producto:</label>
                                <p class="mb-0">${data.tipo_producto_texto}</p>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="fw-bold text-muted">Detracción:</label>
                                <p class="mb-0">${data.detraccion_texto}</p>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="fw-bold text-muted">Stock:</label>
                                <p class="mb-0"><span class="badge bg-primary">${data.stock} unidades</span></p>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="fw-bold text-muted">Stock Mínimo:</label>
                                <p class="mb-0">${data.stock_minimo} unidades</p>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="fw-bold text-muted">Estado:</label>
                                <p class="mb-0">${data.estado_texto == 'Activo' ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-danger">Inactivo</span>'}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="fw-bold text-muted">Fecha Creación:</label>
                                <p class="mb-0">${data.created_at}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="fw-bold text-muted">Última Actualización:</label>
                                <p class="mb-0">${data.updated_at}</p>
                            </div>
                        </div>
                    `);
                }
            },
            error: function(xhr) {
                $('#productoDetails').html(`
                    <div class="alert alert-danger m-3">
                        <i class="bi bi-exclamation-triangle"></i> Error al cargar los detalles
                    </div>
                `);
            }
        });
    });

    // ========== CREAR PRODUCTO ==========
    $('#formCreate').on('submit', function(e) {
        e.preventDefault();
        
        $('#btnGuardarCreate').hide();
        $('#btnLoadingCreate').show();
        $('#alert-messages').html('');
        $('.is-invalid').removeClass('is-invalid');
        
        var formData = new FormData(this);
        
        $.ajax({
            url: '/productos',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    $('#modalCreate').modal('hide');
                    $('#formCreate')[0].reset();
                    
                    $('#alert-messages').html(`
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle"></i> ${response.message}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    `);
                    
                    table.ajax.reload();
                    
                    setTimeout(function() {
                        $('.alert-success').fadeOut();
                    }, 3000);
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    var errors = xhr.responseJSON.errors;
                    $.each(errors, function(field, messages) {
                        $('#' + field + '_create').addClass('is-invalid');
                        $('#error-' + field + '_create').html(messages[0]);
                    });
                } else {
                    $('#alert-messages').html(`
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle"></i> Error al crear el producto
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    `);
                }
            },
            complete: function() {
                $('#btnGuardarCreate').show();
                $('#btnLoadingCreate').hide();
            }
        });
    });
    
    // ========== EDITAR PRODUCTO ==========
    $('#productosTable').on('click', '.btn-edit', function() {
        var id = $(this).data('id');
        var codigo_interno = $(this).data('codigo_interno');
        var codigo_barras = $(this).data('codigo_barras');
        var unidad = $(this).data('unidad');
        var descripcion = $(this).data('descripcion');
        var marca = $(this).data('marca');
        var presentacion = $(this).data('presentacion');
        var operacion = $(this).data('operacion');
        var precio_compra = $(this).data('precio_compra');
        var precio_venta = $(this).data('precio_venta');
        var fecha_vencimiento = $(this).data('fecha_vencimiento');
        var tipo_producto = $(this).data('tipo_producto');
        var detraccion = $(this).data('detraccion');
        var stock = $(this).data('stock');
        var stock_minimo = $(this).data('stock_minimo');
        var estado = $(this).data('estado');
        
        $('#edit_id').val(id);
        $('#codigo_interno_edit').val(codigo_interno);
        $('#codigo_barras_edit').val(codigo_barras || '');
        $('#unidad_edit').val(unidad);
        $('#descripcion_edit').val(descripcion);
        $('#marca_edit').val(marca || '');
        $('#presentacion_edit').val(presentacion || '');
        $('#operacion_edit').val(operacion);
        $('#precio_compra_edit').val(precio_compra);
        $('#precio_venta_edit').val(precio_venta);
        $('#fecha_vencimiento_edit').val(fecha_vencimiento || '');
        $('#tipo_producto_edit').val(tipo_producto);
        $('#detraccion_edit').prop('checked', detraccion == 1);
        $('#stock_edit').val(stock || 0);
        $('#stock_minimo_edit').val(stock_minimo || 0);
        $('#estado_edit').prop('checked', estado == 1);
    });
    
    $('#formEdit').on('submit', function(e) {
        e.preventDefault();
        
        var id = $('#edit_id').val();
        var url = '/productos/' + id;
        
        $('#btnGuardarEdit').hide();
        $('#btnLoadingEdit').show();
        $('.is-invalid').removeClass('is-invalid');
        
        var formData = new FormData(this);
        formData.append('_method', 'PUT');
        
        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                $('#modalEdit').modal('hide');
                
                $('#alert-messages').html(`
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle"></i> ${response.message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `);
                
                table.ajax.reload();
                
                setTimeout(function() {
                    $('.alert-success').fadeOut();
                }, 3000);
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    var errors = xhr.responseJSON.errors;
                    $.each(errors, function(field, messages) {
                        $('#' + field + '_edit').addClass('is-invalid');
                        $('#error-' + field + '_edit').html(messages[0]);
                    });
                } else {
                    $('#alert-messages').html(`
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle"></i> Error al actualizar el producto
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    `);
                }
            },
            complete: function() {
                $('#btnGuardarEdit').show();
                $('#btnLoadingEdit').hide();
            }
        });
    });
    
    // ========== ELIMINAR PRODUCTO ==========
    $('#productosTable').on('click', '.btn-delete', function() {
        var id = $(this).data('id');
        var nombre = $(this).data('nombre');
        
        $('#delete_id').val(id);
        $('#delete-nombre').text(nombre);
        $('#modalDelete').modal('show');
    });
    
    $('#btnConfirmDelete').on('click', function() {
        var id = $('#delete_id').val();
        var url = '/productos/' + id;
        
        $(this).hide();
        $('#btnLoadingDelete').show();
        
        $.ajax({
            url: url,
            type: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                $('#modalDelete').modal('hide');
                
                $('#alert-messages').html(`
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle"></i> ${response.message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `);
                
                table.ajax.reload();
                
                setTimeout(function() {
                    $('.alert-success').fadeOut();
                }, 3000);
            },
            error: function(xhr) {
                $('#alert-messages').html(`
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle"></i> Error al eliminar el producto
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `);
            },
            complete: function() {
                $('#btnConfirmDelete').show();
                $('#btnLoadingDelete').hide();
            }
        });
    });
    
    // Limpiar errores al cerrar modales
    $('#modalCreate').on('hidden.bs.modal', function() {
        $('#formCreate')[0].reset();
        $('.is-invalid').removeClass('is-invalid');
    });
    
    $('#modalEdit').on('hidden.bs.modal', function() {
        $('.is-invalid').removeClass('is-invalid');
    });
});
</script>
@endsection