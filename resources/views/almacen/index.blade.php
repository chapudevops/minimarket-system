@extends('layouts.master')

@section('title', 'Almacenes')
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
                            <i class="bi bi-building"></i> Listado de Almacenes
                        </h4>
                        <p class="mb-0 text-muted small">Administra los almacenes de tu minimarket</p>
                    </div>
                    <div class="col text-end">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCreate">
                            <i class="bi bi-plus-circle"></i> Nuevo Almacén
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div id="alert-messages"></div>

                <div class="table-responsive">
                    <table id="almacenesTable" class="table table-striped table-bordered" style="width:100%">
                        <thead>
                            <tr>
                                <th width="5%">#</th>
                                <th width="60%">Descripción</th>
                                <th width="15%">Establecimiento</th>
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
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-plus-circle"></i> Nuevo Almacén
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formCreate">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">Descripción <span class="text-danger">*</span></label>
                            <textarea name="descripcion" id="descripcion_create" class="form-control" rows="4" placeholder="Ingrese la descripción del almacén..."></textarea>
                            <div class="invalid-feedback" id="error-descripcion_create"></div>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">Establecimiento <span class="text-danger">*</span></label>
                            <select name="establecimiento" id="establecimiento_create" class="form-control">
                                <option value="Oficina Principal">Oficina Principal</option>
                            </select>
                            <div class="invalid-feedback" id="error-establecimiento_create"></div>
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
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-pencil"></i> Editar Almacén
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formEdit">
                @csrf
                @method('PUT')
                <input type="hidden" id="edit_id" name="id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">Descripción <span class="text-danger">*</span></label>
                            <textarea name="descripcion" id="descripcion_edit" class="form-control" rows="4"></textarea>
                            <div class="invalid-feedback" id="error-descripcion_edit"></div>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">Establecimiento <span class="text-danger">*</span></label>
                            <select name="establecimiento" id="establecimiento_edit" class="form-control">
                                <option value="Oficina Principal">Oficina Principal</option>
                            </select>
                            <div class="invalid-feedback" id="error-establecimiento_edit"></div>
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

<!-- Modal Ver Detalles -->
<div class="modal fade" id="modalView" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title text-white">
                    <i class="bi bi-info-circle"></i> Detalles del Almacén
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label class="fw-bold text-muted">Descripción:</label>
                        <p class="mb-0" id="view_descripcion"></p>
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="fw-bold text-muted">Establecimiento:</label>
                        <p class="mb-0" id="view_establecimiento"></p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="fw-bold text-muted">Fecha Creación:</label>
                        <p class="mb-0" id="view_created_at"></p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="fw-bold text-muted">Última Actualización:</label>
                        <p class="mb-0" id="view_updated_at"></p>
                    </div>
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
                <p>¿Estás seguro de eliminar este almacén?</p>
                <p class="fw-bold text-danger" id="delete-descripcion"></p>
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
    var table = $('#almacenesTable').DataTable({
        "processing": true,
        "serverSide": false,
        "ajax": {
            "url": "/almacenes/data",
            "type": "GET",
            "dataSrc": "data"
        },
        "pageLength": 10,
        "lengthMenu": [[5, 10, 15, 25, 50, -1], [5, 10, 15, 25, 50, "Todos"]],
        "columns": [
            { "data": "correlativo" },
            { "data": "descripcion" },
            { "data": "establecimiento" },
            { "data": "created_at" },
            { 
                "data": "acciones",
                "orderable": false,
                "searchable": false
            }
        ],
        "order": [[0, 'desc']],
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

    // ========== VER DETALLES ==========
    $('#almacenesTable').on('click', '.btn-view', function() {
        var descripcion = $(this).data('descripcion');
        var establecimiento = $(this).data('establecimiento');
        var created_at = $(this).data('created_at');
        
        $('#view_descripcion').text(descripcion);
        $('#view_establecimiento').text(establecimiento);
        $('#view_created_at').text(created_at);
        
        // Obtener updated_at desde el servidor
        var id = $(this).data('id');
        $.ajax({
            url: '/almacenes/' + id,
            type: 'GET',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    $('#view_updated_at').text(response.data.updated_at);
                }
            }
        });
    });

    // ========== CREAR ALMACÉN ==========
    $('#formCreate').on('submit', function(e) {
        e.preventDefault();
        
        $('#btnGuardarCreate').hide();
        $('#btnLoadingCreate').show();
        $('#alert-messages').html('');
        $('.is-invalid').removeClass('is-invalid');
        
        var formData = new FormData(this);
        
        $.ajax({
            url: '/almacenes',
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
                            <i class="bi bi-exclamation-triangle"></i> Error al crear el almacén
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
    
    // ========== EDITAR ALMACÉN ==========
    $('#almacenesTable').on('click', '.btn-edit', function() {
        var id = $(this).data('id');
        var descripcion = $(this).data('descripcion');
        var establecimiento = $(this).data('establecimiento');
        
        $('#edit_id').val(id);
        $('#descripcion_edit').val(descripcion);
        $('#establecimiento_edit').val(establecimiento);
    });
    
    $('#formEdit').on('submit', function(e) {
        e.preventDefault();
        
        var id = $('#edit_id').val();
        var url = '/almacenes/' + id;
        
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
                            <i class="bi bi-exclamation-triangle"></i> Error al actualizar el almacén
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
    
    // ========== ELIMINAR ALMACÉN ==========
    $('#almacenesTable').on('click', '.btn-delete', function() {
        var id = $(this).data('id');
        var descripcion = $(this).data('descripcion');
        
        $('#delete_id').val(id);
        $('#delete-descripcion').text(descripcion);
        $('#modalDelete').modal('show');
    });
    
    $('#btnConfirmDelete').on('click', function() {
        var id = $('#delete_id').val();
        var url = '/almacenes/' + id;
        
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
                        <i class="bi bi-exclamation-triangle"></i> Error al eliminar el almacén
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