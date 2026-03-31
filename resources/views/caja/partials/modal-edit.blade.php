<!-- Modal Editar Caja -->
<div class="modal fade" id="modalEdit" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-pencil"></i> Editar Caja
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formEdit">
                @csrf
                @method('PUT')
                <input type="hidden" id="edit_id" name="id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Descripción</label>
                        <textarea name="descripcion" id="descripcion_edit" class="form-control" rows="5" placeholder="Ingrese la descripción de la caja..."></textarea>
                        <div class="invalid-feedback" id="error-descripcion_edit"></div>
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