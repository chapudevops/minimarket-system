<!-- Modal Editar Proveedor -->
<div class="modal fade" id="modalEdit" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-pencil"></i> Editar Proveedor
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formEdit">
                @csrf
                @method('PUT')
                <input type="hidden" id="edit_id" name="id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Tipo Documento <span class="text-danger">*</span></label>
                            <select name="tipo_documento" id="tipo_documento_edit" class="form-control">
                                <option value="DNI">DNI</option>
                                <option value="RUC">RUC</option>
                                <option value="CE">Carné de Extranjería</option>
                            </select>
                            <div class="invalid-feedback" id="error-tipo_documento_edit"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Número Documento <span class="text-danger">*</span></label>
                            <input type="text" name="numero_documento" id="numero_documento_edit" class="form-control">
                            <div class="invalid-feedback" id="error-numero_documento_edit"></div>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">Nombre o Razón Social <span class="text-danger">*</span></label>
                            <input type="text" name="nombre_razon_social" id="nombre_razon_social_edit" class="form-control">
                            <div class="invalid-feedback" id="error-nombre_razon_social_edit"></div>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">Dirección</label>
                            <textarea name="direccion" id="direccion_edit" class="form-control" rows="2"></textarea>
                            <div class="invalid-feedback" id="error-direccion_edit"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Teléfono</label>
                            <input type="text" name="telefono" id="telefono_edit" class="form-control">
                            <div class="invalid-feedback" id="error-telefono_edit"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-check mt-4">
                                <input type="checkbox" name="estado" class="form-check-input" id="estado_edit" value="1">
                                <label class="form-check-label fw-bold" for="estado_edit">Activo</label>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Departamento</label>
                            <input type="text" name="departamento" id="departamento_edit" class="form-control">
                            <div class="invalid-feedback" id="error-departamento_edit"></div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Provincia</label>
                            <input type="text" name="provincia" id="provincia_edit" class="form-control">
                            <div class="invalid-feedback" id="error-provincia_edit"></div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Distrito</label>
                            <input type="text" name="distrito" id="distrito_edit" class="form-control">
                            <div class="invalid-feedback" id="error-distrito_edit"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Cancelar
                    </button>
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