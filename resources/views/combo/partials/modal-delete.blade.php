<!-- Modal Eliminar Combo -->
<div class="modal fade" id="modalDelete" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="bi bi-exclamation-triangle"></i> Confirmar Eliminación
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center py-4">
                <i class="bi bi-trash" style="font-size: 48px; color: #dc3545;"></i>
                <p class="mt-3">¿Estás seguro que deseas eliminar el combo:</p>
                <h6 class="fw-bold" id="delete-nombre"></h6>
                <input type="hidden" id="delete_id">
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="btnConfirmDelete">
                    <i class="bi bi-trash"></i> Eliminar
                </button>
                <button type="button" class="btn btn-danger" id="btnLoadingDelete" style="display: none;" disabled>
                    <span class="spinner-border spinner-border-sm me-1"></span> Eliminando...
                </button>
            </div>
        </div>
    </div>
</div>
