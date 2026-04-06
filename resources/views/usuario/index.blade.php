@extends('layouts.master')

@section('title', 'Usuarios')
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
                            <i class="bi bi-people"></i> Listado de Usuarios
                        </h4>
                        <p class="mb-0 text-muted small">Administra los usuarios del sistema</p>
                    </div>
                    <div class="col text-end">
                        <button type="button" class="btn btn-primary" id="btnNuevoUsuario">
                            <i class="bi bi-plus-circle"></i> Nuevo Usuario
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div id="alert-messages"></div>

                <div class="table-responsive">
                    <table id="usuariosTable" class="table table-striped table-bordered" style="width:100%">
                        <thead>
                            <tr>
                                <th width="3%">#</th>
                                <th width="15%">Nombre</th>
                                <th width="20%">Email</th>
                                <th width="10%">Caja</th>
                                <th width="12%">Almacén</th>
                                <th width="15%">Roles</th>
                                <th width="8%">Estado</th>
                                <th width="8%">Fecha Creación</th>
                                <th width="9%">Acciones</th>
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

<!-- Modal Crear/Editar Usuario -->
<div class="modal fade" id="modalUsuario" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalUsuarioTitle">
                    <i class="bi bi-person-plus"></i> Nuevo Usuario
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formUsuario">
                @csrf
                <input type="hidden" id="usuario_id" name="id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Nombres <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name" class="form-control" placeholder="Nombres completos">
                            <div class="invalid-feedback" id="error-name"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Correo Electrónico <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="email" class="form-control" placeholder="correo@ejemplo.com">
                            <div class="invalid-feedback" id="error-email"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Contraseña <span class="text-danger" id="passwordRequired">*</span></label>
                            <input type="password" name="password" id="password" class="form-control" placeholder="••••••••">
                            <div class="invalid-feedback" id="error-password"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Confirmar Contraseña</label>
                            <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" placeholder="••••••••">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Asignar Caja</label>
                            <select name="caja_id" id="caja_id" class="form-control">
                                <option value="">Seleccionar caja</option>
                            </select>
                            <div class="invalid-feedback" id="error-caja_id"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Asignar Almacén</label>
                            <select name="almacen_id" id="almacen_id" class="form-control">
                                <option value="">Seleccionar almacén</option>
                            </select>
                            <div class="invalid-feedback" id="error-almacen_id"></div>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">Roles <span class="text-danger">*</span></label>
                            <div class="row" id="rolesContainer">
                                <!-- Los roles se cargarán vía AJAX -->
                            </div>
                            <div class="invalid-feedback" id="error-roles"></div>
                        </div>
                        <div class="col-md-12 mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="estado" class="form-check-input" id="estado" value="1" checked>
                                <label class="form-check-label fw-bold" for="estado">Activo</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnGuardar">
                        <i class="bi bi-save"></i> Guardar
                    </button>
                    <button type="button" class="btn btn-primary" id="btnLoading" style="display: none;" disabled>
                        <span class="spinner-border spinner-border-sm me-2"></span> Guardando...
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
                    <i class="bi bi-person-badge"></i> Detalles del Usuario
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="usuarioDetails">
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
                <p>¿Estás seguro de eliminar este usuario?</p>
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

<!-- Modal Cambiar Estado -->
<div class="modal fade" id="modalToggleStatus" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">
                    <i class="bi bi-exclamation-triangle"></i> Confirmar Cambio de Estado
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de <strong id="toggle-action"></strong> este usuario?</p>
                <p class="fw-bold" id="toggle-nombre"></p>
                <input type="hidden" id="toggle_id">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-warning" id="btnConfirmToggle">
                    <i class="bi bi-check-circle"></i> Confirmar
                </button>
            </div>
        </div>
    </div>
</div>

@endsection 

@section('scripts')  
<script src="{{ URL::asset('build/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ URL::asset('build/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
<script src="{{ URL::asset('build/js/usuario/config.js')}}"></script>
@endsection