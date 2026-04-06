<!-- Modal Crear Gasto -->
<div class="modal fade" id="modalCreate" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-plus-circle"></i> Registrar Gasto
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formCreate">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">Fecha de Emisión <span class="text-danger">*</span></label>
                            <input type="date" name="fecha_emision" id="fecha_emision_create" class="form-control" value="{{ date('Y-m-d') }}">
                            <div class="invalid-feedback" id="error-fecha_emision_create"></div>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">Motivo <span class="text-danger">*</span></label>
                            <input type="text" name="motivo" id="motivo_create" class="form-control" placeholder="Ej: Pago de luz, Compra de útiles, etc.">
                            <div class="invalid-feedback" id="error-motivo_create"></div>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">Cuenta <span class="text-danger">*</span></label>
                            <input type="text" name="cuenta" id="cuenta_create" class="form-control" placeholder="Ej: Servicios Básicos, Papelería, Mantenimiento">
                            <div class="invalid-feedback" id="error-cuenta_create"></div>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">Monto <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">S/</span>
                                <input type="number" step="0.01" name="monto" id="monto_create" class="form-control" placeholder="0.00">
                            </div>
                            <div class="invalid-feedback" id="error-monto_create"></div>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">Detalle</label>
                            <textarea name="detalle" id="detalle_create" class="form-control" rows="3" placeholder="Detalle adicional del gasto..."></textarea>
                            <div class="invalid-feedback" id="error-detalle_create"></div>
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