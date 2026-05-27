<!-- Modal Crear/Editar Combo -->
<div class="modal fade" id="modalCombo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalComboTitle">
                    <i class="bi bi-plus-circle"></i> Nuevo Combo
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formCombo">
                @csrf
                <input type="hidden" id="combo_id" name="combo_id" value="">
                <div class="modal-body">
                    <div class="row g-3">
                        <!-- Nombre del combo -->
                        <div class="col-md-8">
                            <label for="nombre" class="form-label fw-semibold">Nombre del Combo <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Ej: Combo Desayuno" required>
                            <div class="invalid-feedback" id="error-nombre"></div>
                        </div>

                        <!-- Estado -->
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Estado</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" id="estado" name="estado" value="1" checked>
                                <label class="form-check-label" for="estado">Activo</label>
                            </div>
                        </div>

                        <!-- Descripción -->
                        <div class="col-md-12">
                            <label for="descripcion" class="form-label fw-semibold">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="2" placeholder="Descripción del combo (opcional)"></textarea>
                        </div>

                        <!-- Precio del combo -->
                        <div class="col-md-6">
                            <label for="precio_combo" class="form-label fw-semibold">Precio del Combo <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">S/</span>
                                <input type="number" class="form-control" id="precio_combo" name="precio_combo" step="0.01" min="0" placeholder="0.00" required>
                            </div>
                            <div class="invalid-feedback" id="error-precio_combo"></div>
                        </div>

                        <!-- Precio regular (calculado) -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Precio Regular (calculado)</label>
                            <div class="input-group">
                                <span class="input-group-text">S/</span>
                                <input type="text" class="form-control bg-light" id="precio_regular_display" readonly value="0.00">
                            </div>
                        </div>

                        <!-- Buscar productos -->
                        <div class="col-md-12">
                            <hr>
                            <label class="form-label fw-bold"><i class="bi bi-box-seam"></i> Productos del Combo <span class="text-danger">*</span></label>
                            <div class="input-group mb-2">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input type="text" class="form-control" id="buscarProducto" placeholder="Buscar producto por nombre o código...">
                            </div>
                            <div class="search-producto-results" id="resultadosBusqueda">
                                <!-- Resultados de búsqueda aquí -->
                            </div>
                        </div>

                        <!-- Lista de productos agregados -->
                        <div class="col-md-12">
                            <div id="listaProductosCombo">
                                <p class="text-muted text-center" id="sinProductos">
                                    <i class="bi bi-info-circle"></i> Busque y agregue productos al combo
                                </p>
                            </div>
                        </div>

                        <!-- Resumen de precios -->
                        <div class="col-md-12">
                            <div class="precio-resumen">
                                <div class="row">
                                    <div class="col-md-4 text-center">
                                        <small class="text-muted">Precio Regular</small>
                                        <h5 class="text-secondary mb-0" id="resumenRegular">S/ 0.00</h5>
                                    </div>
                                    <div class="col-md-4 text-center">
                                        <small class="text-muted">Precio Combo</small>
                                        <h5 class="text-primary mb-0" id="resumenCombo">S/ 0.00</h5>
                                    </div>
                                    <div class="col-md-4 text-center">
                                        <small class="text-muted">Ahorro</small>
                                        <h5 class="text-success mb-0" id="resumenAhorro">S/ 0.00</h5>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnGuardar">
                        <i class="bi bi-check-lg"></i> Guardar Combo
                    </button>
                    <button type="button" class="btn btn-primary" id="btnLoading" style="display: none;" disabled>
                        <span class="spinner-border spinner-border-sm me-1"></span> Guardando...
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
