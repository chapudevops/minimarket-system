
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