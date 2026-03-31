<!-- Modal Crear Proveedor -->
<div class="modal fade" id="modalCreate" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-plus-circle"></i> Nuevo Proveedor
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formCreate">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Tipo Documento <span class="text-danger">*</span></label>
                            <select name="tipo_documento" id="tipo_documento_create" class="form-control">
                                <option value="DNI">DNI</option>
                                <option value="RUC" selected>RUC</option>
                                <option value="CE">Carné de Extranjería</option>
                            </select>
                            <div class="invalid-feedback" id="error-tipo_documento_create"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Número Documento <span class="text-danger">*</span></label>
                            <input type="text" name="numero_documento" id="numero_documento_create" class="form-control" placeholder="20123456789">
                            <div class="invalid-feedback" id="error-numero_documento_create"></div>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">Nombre o Razón Social <span class="text-danger">*</span></label>
                            <input type="text" name="nombre_razon_social" id="nombre_razon_social_create" class="form-control" placeholder="Distribuidora Ejemplo S.A.C.">
                            <div class="invalid-feedback" id="error-nombre_razon_social_create"></div>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">Dirección</label>
                            <textarea name="direccion" id="direccion_create" class="form-control" rows="2" placeholder="Av. Principal 123"></textarea>
                            <div class="invalid-feedback" id="error-direccion_create"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Teléfono</label>
                            <input type="text" name="telefono" id="telefono_create" class="form-control" placeholder="987654321">
                            <div class="invalid-feedback" id="error-telefono_create"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-check mt-4">
                                <input type="checkbox" name="estado" class="form-check-input" id="estado_create" value="1" checked>
                                <label class="form-check-label fw-bold" for="estado_create">Activo</label>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Departamento</label>
                            <input type="text" name="departamento" id="departamento_create" class="form-control" placeholder="Lima">
                            <div class="invalid-feedback" id="error-departamento_create"></div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Provincia</label>
                            <input type="text" name="provincia" id="provincia_create" class="form-control" placeholder="Lima">
                            <div class="invalid-feedback" id="error-provincia_create"></div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Distrito</label>
                            <input type="text" name="distrito" id="distrito_create" class="form-control" placeholder="Ate">
                            <div class="invalid-feedback" id="error-distrito_create"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Cancelar
                    </button>
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