<!-- Modal Cerrar Caja -->
<div class="modal fade" id="modalCerrar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title text-white">
                    <i class="bi bi-x-circle"></i> Cerrar Caja
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formCerrar">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">Responsable</label>
                            <input type="text" class="form-control" id="cerrar_responsable" readonly>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">Monto Apertura</label>
                            <input type="text" class="form-control" id="cerrar_monto_inicial" readonly>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">Monto de Cierre <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">S/</span>
                                <input type="number" step="0.01" name="monto_cierre" id="monto_cierre" class="form-control" placeholder="0.00" required>
                            </div>
                            <div class="invalid-feedback" id="error-monto_cierre"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger" id="btnGuardarCerrar">
                        <i class="bi bi-check-circle"></i> Cerrar Caja
                    </button>
                    <button type="button" class="btn btn-danger" id="btnLoadingCerrar" style="display: none;" disabled>
                        <span class="spinner-border spinner-border-sm me-2"></span> Cerrando...
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>