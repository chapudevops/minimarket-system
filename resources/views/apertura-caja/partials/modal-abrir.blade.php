<!-- Modal Abrir Caja -->
<div class="modal fade" id="modalAbrir" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-cash"></i> Abrir Caja
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formAbrir">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">Responsable</label>
                            <input type="text" class="form-control" value="{{ auth()->user()->name }}" readonly>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">Monto Inicial <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">S/</span>
                                <input type="number" step="0.01" name="monto_inicial" id="monto_inicial" class="form-control" placeholder="0.00" required>
                            </div>
                            <div class="invalid-feedback" id="error-monto_inicial"></div>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">Fecha de Emisión <span class="text-danger">*</span></label>
                            <input type="date" name="fecha_apertura" id="fecha_apertura" class="form-control" value="{{ date('Y-m-d') }}" required>
                            <div class="invalid-feedback" id="error-fecha_apertura"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnGuardarAbrir">
                        <i class="bi bi-save"></i> Abrir Caja
                    </button>
                    <button type="button" class="btn btn-primary" id="btnLoadingAbrir" style="display: none;" disabled>
                        <span class="spinner-border spinner-border-sm me-2"></span> Abriendo...
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>