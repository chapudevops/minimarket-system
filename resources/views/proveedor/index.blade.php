@extends('layouts.master')

@section('title', 'Proveedores')
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
                            <i class="bi bi-truck"></i> Listado de Proveedores
                        </h4>
                        <p class="mb-0 text-muted small">Administra los proveedores de tu minimarket</p>
                    </div>
                    <div class="col text-end">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCreate">
                            <i class="bi bi-plus-circle"></i> Nuevo Proveedor
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <!-- Preloader -->
                <div id="preloader-table" class="text-center py-5" style="display: none;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="mt-2">Cargando datos...</p>
                </div>

                <!-- Alertas -->
                <div id="alert-messages"></div>

                <div class="table-responsive">
                    <table id="proveedoresTable" class="table table-striped table-bordered" style="width:100%">
                        <thead>
                             <tr>
                                <th width="3%">#</th>
                                <th width="8%">Tipo Doc.</th>
                                <th width="10%">N° Documento</th>
                                <th width="25%">Nombre/Razón Social</th>
                                <th width="10%">Teléfono</th>
                                <th width="10%">Departamento</th>
                                <th width="8%">Estado</th>
                                <th width="10%">Fecha Creación</th>
                                <th width="8%">Acciones</th>
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

<!-- Modales -->
@include('proveedor.partials.modal-create')
@include('proveedor.partials.modal-edit')
@include('proveedor.partials.modal-delete')

@endsection 

@section('scripts')  
<script src="{{ URL::asset('build/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ URL::asset('build/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
<script>
$(document).ready(function() {
    // Inicializar DataTable con AJAX
    var table = $('#proveedoresTable').DataTable({
        "processing": true,
        "serverSide": false,
        "ajax": {
            "url": "/proveedores/data",
            "type": "GET",
            "dataSrc": "data"
        },
        "pageLength": 10,
        "lengthMenu": [[5, 10, 15, 25, 50, -1], [5, 10, 15, 25, 50, "Todos"]],
        "columns": [
            { "data": "correlativo" },
            { "data": "tipo_documento" },
            { "data": "numero_documento" },
            { "data": "nombre_razon_social" },
            { "data": "telefono" },
            { "data": "departamento" },
            { "data": "estado_texto" },
            { "data": "created_at" },
            { 
                "data": "acciones",
                "orderable": false,
                "searchable": false
            }
        ],
        "order": [[3, 'asc']],
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

    // ========== CREAR PROVEEDOR ==========
    $('#formCreate').on('submit', function(e) {
        e.preventDefault();
        
        $('#btnGuardarCreate').hide();
        $('#btnLoadingCreate').show();
        $('#alert-messages').html('');
        $('.is-invalid').removeClass('is-invalid');
        
        var formData = new FormData(this);
        
        $.ajax({
            url: '/proveedores',
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
                            <i class="bi bi-exclamation-triangle"></i> Error al crear el proveedor
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
    
    // ========== EDITAR PROVEEDOR ==========
    $('#proveedoresTable').on('click', '.btn-edit', function() {
        var id = $(this).data('id');
        var tipo_documento = $(this).data('tipo_documento');
        var numero_documento = $(this).data('numero_documento');
        var nombre_razon_social = $(this).data('nombre_razon_social');
        var direccion = $(this).data('direccion');
        var telefono = $(this).data('telefono');
        var departamento = $(this).data('departamento');
        var provincia = $(this).data('provincia');
        var distrito = $(this).data('distrito');
        var estado = $(this).data('estado');
        
        $('#edit_id').val(id);
        $('#tipo_documento_edit').val(tipo_documento);
        $('#numero_documento_edit').val(numero_documento);
        $('#nombre_razon_social_edit').val(nombre_razon_social);
        $('#direccion_edit').val(direccion || '');
        $('#telefono_edit').val(telefono || '');
        $('#departamento_edit').val(departamento || '');
        $('#provincia_edit').val(provincia || '');
        $('#distrito_edit').val(distrito || '');
        $('#estado_edit').prop('checked', estado == 1);
    });
    
    $('#formEdit').on('submit', function(e) {
        e.preventDefault();
        
        var id = $('#edit_id').val();
        var url = '/proveedores/' + id;
        
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
                            <i class="bi bi-exclamation-triangle"></i> Error al actualizar el proveedor
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
    
    // ========== ELIMINAR PROVEEDOR ==========
    $('#proveedoresTable').on('click', '.btn-delete', function() {
        var id = $(this).data('id');
        var nombre = $(this).data('nombre');
        
        $('#delete_id').val(id);
        $('#delete-nombre').text(nombre);
        $('#modalDelete').modal('show');
    });
    
    $('#btnConfirmDelete').on('click', function() {
        var id = $('#delete_id').val();
        var url = '/proveedores/' + id;
        
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
                        <i class="bi bi-exclamation-triangle"></i> Error al eliminar el proveedor
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
    
    // Limpiar errores al abrir modales
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