<!-- Modal Crear Producto -->
<div class="modal fade" id="modalCreate" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-plus-circle"></i> Nuevo Producto
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formCreate">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Código Interno <span class="text-danger">*</span></label>
                            <input type="text" name="codigo_interno" id="codigo_interno_create" class="form-control" placeholder="PROD001">
                            <div class="invalid-feedback" id="error-codigo_interno_create"></div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Código de Barras</label>
                            <input type="text" name="codigo_barras" id="codigo_barras_create" class="form-control" placeholder="789123456001">
                            <div class="invalid-feedback" id="error-codigo_barras_create"></div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Unidad <span class="text-danger">*</span></label>
                            <select name="unidad" id="unidad_create" class="form-control">
                                <option value="UNIDAD">UNIDAD</option>
                                <option value="KG">KG</option>
                                <option value="LITRO">LITRO</option>
                                <option value="DOCENA">DOCENA</option>
                                <option value="CAJA">CAJA</option>
                                <option value="HORA">HORA (SERVICIOS)</option>
                                <option value="MES">MES (SERVICIOS)</option>
                            </select>
                            <div class="invalid-feedback" id="error-unidad_create"></div>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">Descripción <span class="text-danger">*</span></label>
                            <textarea name="descripcion" id="descripcion_create" class="form-control" rows="2" placeholder="Descripción del producto..."></textarea>
                            <div class="invalid-feedback" id="error-descripcion_create"></div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Marca</label>
                            <input type="text" name="marca" id="marca_create" class="form-control" placeholder="HP, Logitech, etc.">
                            <div class="invalid-feedback" id="error-marca_create"></div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Presentación</label>
                            <input type="text" name="presentacion" id="presentacion_create" class="form-control" placeholder="Caja, Blister, etc.">
                            <div class="invalid-feedback" id="error-presentacion_create"></div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Operación <span class="text-danger">*</span></label>
                            <select name="operacion" id="operacion_create" class="form-control">
                                <option value="GRAVADO">Gravado - Operación Onerosa</option>
                                <option value="EXONERADO">Exonerado - Operación Onerosa</option>
                                <option value="INAFECTO">Inafecto - Operación Onerosa</option>
                            </select>
                            <div class="invalid-feedback" id="error-operacion_create"></div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Precio Compra <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="precio_compra" id="precio_compra_create" class="form-control" placeholder="0.00">
                            <div class="invalid-feedback" id="error-precio_compra_create"></div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Precio Venta <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="precio_venta" id="precio_venta_create" class="form-control" placeholder="0.00">
                            <div class="invalid-feedback" id="error-precio_venta_create"></div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Fecha de Vencimiento</label>
                            <input type="date" name="fecha_vencimiento" id="fecha_vencimiento_create" class="form-control">
                            <div class="invalid-feedback" id="error-fecha_vencimiento_create"></div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Tipo Producto <span class="text-danger">*</span></label>
                            <select name="tipo_producto" id="tipo_producto_create" class="form-control">
                                <option value="PRODUCTO">Producto</option>
                                <option value="SERVICIO">Servicio</option>
                            </select>
                            <div class="invalid-feedback" id="error-tipo_producto_create"></div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="form-check mt-4">
                                <input type="checkbox" name="detraccion" class="form-check-input" id="detraccion_create" value="1">
                                <label class="form-check-label fw-bold" for="detraccion_create">Configuración de Detracción</label>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Stock Inicial</label>
                            <input type="number" name="stock" id="stock_create" class="form-control" placeholder="0" value="0">
                            <div class="invalid-feedback" id="error-stock_create"></div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Stock Mínimo</label>
                            <input type="number" name="stock_minimo" id="stock_minimo_create" class="form-control" placeholder="0" value="0">
                            <div class="invalid-feedback" id="error-stock_minimo_create"></div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="form-check mt-4">
                                <input type="checkbox" name="estado" class="form-check-input" id="estado_create" value="1" checked>
                                <label class="form-check-label fw-bold" for="estado_create">Activo</label>
                            </div>
                        </div>
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