<!-- Modal Crear Serie -->
<div class="modal fade" id="modalCreate" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-plus-circle"></i> Registrar Serie
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formCreate">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">Serie <span class="text-danger">*</span></label>
                            <input type="text" name="serie" id="serie_create" class="form-control" placeholder="Ej: F001, B001" maxlength="10">
                            <div class="invalid-feedback" id="error-serie_create"></div>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">Correlativo <span class="text-danger">*</span></label>
                            <input type="number" name="correlativo" id="correlativo_create" class="form-control" placeholder="1" value="1" min="0">
                            <div class="invalid-feedback" id="error-correlativo_create"></div>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">Tipo de Comprobante <span class="text-danger">*</span></label>
                            <select name="tipo_comprobante" id="tipo_comprobante_create" class="form-control">
                                <option value="FACTURA">Factura</option>
                                <option value="BOLETA">Boleta</option>
                                <option value="NOTA_CREDITO">Nota de Crédito</option>
                                <option value="NOTA_DEBITO">Nota de Débito</option>
                            </select>
                            <div class="invalid-feedback" id="error-tipo_comprobante_create"></div>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">Caja <span class="text-danger">*</span></label>
                            <select name="caja_id" id="caja_id_create" class="form-control">
                                <option value="">Seleccionar caja</option>
                            </select>
                            <div class="invalid-feedback" id="error-caja_id_create"></div>
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