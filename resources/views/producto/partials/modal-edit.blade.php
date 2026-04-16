<!-- Modal Editar Producto -->
<div class="modal fade" id="modalEdit" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-pencil"></i> Editar Producto
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formEdit">
                @csrf
                @method('PUT')
                <input type="hidden" id="edit_id" name="id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Código Interno <span class="text-danger">*</span></label>
                            <input type="text" name="codigo_interno" id="codigo_interno_edit" class="form-control">
                            <div class="invalid-feedback" id="error-codigo_interno_edit"></div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Código de Barras</label>
                            <input type="text" name="codigo_barras" id="codigo_barras_edit" class="form-control">
                            <div class="invalid-feedback" id="error-codigo_barras_edit"></div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Unidad <span class="text-danger">*</span></label>
                            <select name="unidad" id="unidad_edit" class="form-control">
                                <option value="UNIDAD">UNIDAD</option>
                                <option value="KG">KG</option>
                                <option value="LITRO">LITRO</option>
                                <option value="DOCENA">DOCENA</option>
                                <option value="CAJA">CAJA</option>
                                <option value="HORA">HORA (SERVICIOS)</option>
                                <option value="MES">MES (SERVICIOS)</option>
                            </select>
                            <div class="invalid-feedback" id="error-unidad_edit"></div>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">Descripción <span class="text-danger">*</span></label>
                            <textarea name="descripcion" id="descripcion_edit" class="form-control" rows="2"></textarea>
                            <div class="invalid-feedback" id="error-descripcion_edit"></div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Marca</label>
                            <input type="text" name="marca" id="marca_edit" class="form-control">
                            <div class="invalid-feedback" id="error-marca_edit"></div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Presentación</label>
                            <input type="text" name="presentacion" id="presentacion_edit" class="form-control">
                            <div class="invalid-feedback" id="error-presentacion_edit"></div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Operación <span class="text-danger">*</span></label>
                            <select name="operacion" id="operacion_edit" class="form-control">
                                <option value="GRAVADO">Gravado - Operación Onerosa</option>
                                <option value="EXONERADO">Exonerado - Operación Onerosa</option>
                                <option value="INAFECTO">Inafecto - Operación Onerosa</option>
                            </select>
                            <div class="invalid-feedback" id="error-operacion_edit"></div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Precio Compra <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="precio_compra" id="precio_compra_edit" class="form-control">
                            <div class="invalid-feedback" id="error-precio_compra_edit"></div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Precio Venta <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="precio_venta" id="precio_venta_edit" class="form-control">
                            <div class="invalid-feedback" id="error-precio_venta_edit"></div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Fecha de Vencimiento</label>
                            <input type="date" name="fecha_vencimiento" id="fecha_vencimiento_edit" class="form-control">
                            <div class="invalid-feedback" id="error-fecha_vencimiento_edit"></div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Tipo Producto <span class="text-danger">*</span></label>
                            <select name="tipo_producto" id="tipo_producto_edit" class="form-control">
                                <option value="PRODUCTO">Producto</option>
                                <option value="SERVICIO">Servicio</option>
                            </select>
                            <div class="invalid-feedback" id="error-tipo_producto_edit"></div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="form-check mt-4">
                                <input type="checkbox" name="detraccion" class="form-check-input" id="detraccion_edit" value="1">
                                <label class="form-check-label fw-bold" for="detraccion_edit">Configuración de Detracción</label>
                            </div>
                        </div>

                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Stock</label>
                            <input type="number" name="stock" id="stock_edit" class="form-control" readonly>
                            <small class="text-muted">El stock se actualiza con movimientos</small>
                            <div class="invalid-feedback" id="error-stock_edit"></div>
                        </div>
                        <div class="col-md-12 mb-3">
                        <label class="form-label fw-bold">Foto del Producto</label>
                        <div id="foto-preview-edit" class="mb-2">
                            <img id="foto-preview-img-edit" src="" width="100" height="100" class="border rounded p-1" style="object-fit: cover;">
                        </div>
                        <input type="file" name="foto" id="foto_edit" class="form-control" accept="image/*">
                        <small class="text-muted">JPG, PNG, GIF - Máx. 2MB</small>
                        <div class="invalid-feedback" id="error-foto_edit"></div>
                    </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Stock Mínimo</label>
                            <input type="number" name="stock_minimo" id="stock_minimo_edit" class="form-control">
                            <div class="invalid-feedback" id="error-stock_minimo_edit"></div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="form-check mt-4">
                                <input type="checkbox" name="estado" class="form-check-input" id="estado_edit" value="1">
                                <label class="form-check-label fw-bold" for="estado_edit">Activo</label>
                            </div>
                        </div>
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