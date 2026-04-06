<!-- Modal Editar Serie -->
<div class="modal fade" id="modalEdit" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-pencil"></i> Editar Serie
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
                            <label class="form-label fw-bold">Serie <span class="text-danger">*</span></label>
                            <input type="text" name="serie" id="serie_edit" class="form-control" placeholder="Ej: F001, B001" maxlength="10">
                            <div class="invalid-feedback" id="error-serie_edit"></div>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">Correlativo <span class="text-danger">*</span></label>
                            <input type="number" name="correlativo" id="correlativo_edit" class="form-control" min="0">
                            <div class="invalid-feedback" id="error-correlativo_edit"></div>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">Tipo de Comprobante <span class="text-danger">*</span></label>
                            <select name="tipo_comprobante" id="tipo_comprobante_edit" class="form-control">
                                <option value="FACTURA">Factura</option>
                                <option value="BOLETA">Boleta</option>
                                <option value="NOTA_CREDITO">Nota de Crédito</option>
                                <option value="NOTA_DEBITO">Nota de Débito</option>
                            </select>
                            <div class="invalid-feedback" id="error-tipo_comprobante_edit"></div>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">Caja <span class="text-danger">*</span></label>
                            <select name="caja_id" id="caja_id_edit" class="form-control">
                                <option value="">Seleccionar caja</option>
                            </select>
                            <div class="invalid-feedback" id="error-caja_id_edit"></div>
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