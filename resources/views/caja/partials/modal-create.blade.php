<!-- Modal Crear Caja -->
<div class="modal fade" id="modalCreate" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-plus-circle"></i> Nueva Caja
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formCreate">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Descripción</label>
                        <textarea name="descripcion" id="descripcion_create" class="form-control" rows="5" placeholder="Ingrese la descripción de la caja..."></textarea>
                        <div class="invalid-feedback" id="error-descripcion_create"></div>
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