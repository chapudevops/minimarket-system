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
                <p>¿Estás seguro de eliminar este almacén?</p>
                <p class="fw-bold text-danger" id="delete-descripcion"></p>
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