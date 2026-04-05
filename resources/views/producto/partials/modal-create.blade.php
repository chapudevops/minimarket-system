<!-- Modal Crear/Editar Producto -->
<div class="modal fade" id="modalProducto" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalProductoTitle">
                    <i class="bi bi-plus-circle"></i> Nuevo Producto
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formProducto">
                @csrf
                <input type="hidden" id="producto_id" name="id">
                <div class="modal-body">
                    <!-- Tabs -->
                    <ul class="nav nav-tabs" id="productoTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="info-tab" data-bs-toggle="tab" data-bs-target="#info-pane" type="button" role="tab">
                                <i class="bi bi-info-circle"></i> Información
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="almacen-tab" data-bs-toggle="tab" data-bs-target="#almacen-pane" type="button" role="tab">
                                <i class="bi bi-building"></i> Almacenes
                            </button>
                        </li>
                    </ul>
                    
                    <div class="tab-content mt-3">
                        <!-- Tab Información -->
                        <div class="tab-pane fade show active" id="info-pane" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Código Interno <span class="text-danger">*</span></label>
                                    <input type="text" name="codigo_interno" id="codigo_interno" class="form-control" placeholder="PROD001">
                                    <div class="invalid-feedback" id="error-codigo_interno"></div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Código de Barras</label>
                                    <input type="text" name="codigo_barras" id="codigo_barras" class="form-control" placeholder="789123456001">
                                    <div class="invalid-feedback" id="error-codigo_barras"></div>
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label class="form-label fw-bold">Descripción <span class="text-danger">*</span></label>
                                    <textarea name="descripcion" id="descripcion" class="form-control" rows="2" placeholder="Descripción del producto..."></textarea>
                                    <div class="invalid-feedback" id="error-descripcion"></div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Unidad <span class="text-danger">*</span></label>
                                    <select name="unidad" id="unidad" class="form-control">
                                        <option value="UNIDAD">UNIDAD</option>
                                        <option value="KG">KG</option>
                                        <option value="LITRO">LITRO</option>
                                        <option value="DOCENA">DOCENA</option>
                                        <option value="CAJA">CAJA</option>
                                        <option value="HORA">HORA (SERVICIOS)</option>
                                        <option value="MES">MES (SERVICIOS)</option>
                                    </select>
                                    <div class="invalid-feedback" id="error-unidad"></div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Marca</label>
                                    <input type="text" name="marca" id="marca" class="form-control" placeholder="HP, Logitech, etc.">
                                    <div class="invalid-feedback" id="error-marca"></div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Presentación</label>
                                    <input type="text" name="presentacion" id="presentacion" class="form-control" placeholder="Caja, Blister, etc.">
                                    <div class="invalid-feedback" id="error-presentacion"></div>
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label class="form-label fw-bold">Operación <span class="text-danger">*</span></label>
                                    <select name="operacion" id="operacion" class="form-control">
                                        <option value="GRAVADO">Gravado - Operación Onerosa</option>
                                        <option value="EXONERADO">Exonerado - Operación Onerosa</option>
                                        <option value="INAFECTO">Inafecto - Operación Onerosa</option>
                                    </select>
                                    <div class="invalid-feedback" id="error-operacion"></div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Precio Compra <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" name="precio_compra" id="precio_compra" class="form-control" placeholder="0.00">
                                    <div class="invalid-feedback" id="error-precio_compra"></div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Precio Venta <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" name="precio_venta" id="precio_venta" class="form-control" placeholder="0.00">
                                    <div class="invalid-feedback" id="error-precio_venta"></div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Fecha de Vencimiento</label>
                                    <input type="date" name="fecha_vencimiento" id="fecha_vencimiento" class="form-control">
                                    <div class="invalid-feedback" id="error-fecha_vencimiento"></div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Tipo Producto <span class="text-danger">*</span></label>
                                    <select name="tipo_producto" id="tipo_producto" class="form-control">
                                        <option value="PRODUCTO">Producto</option>
                                        <option value="SERVICIO">Servicio</option>
                                    </select>
                                    <div class="invalid-feedback" id="error-tipo_producto"></div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="form-check mt-4">
                                        <input type="checkbox" name="detraccion" class="form-check-input" id="detraccion" value="1">
                                        <label class="form-check-label fw-bold" for="detraccion">Configuración de Detracción</label>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Stock Mínimo</label>
                                    <input type="number" name="stock_minimo" id="stock_minimo" class="form-control" placeholder="0" value="0">
                                    <div class="invalid-feedback" id="error-stock_minimo"></div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="form-check mt-4">
                                        <input type="checkbox" name="estado" class="form-check-input" id="estado" value="1" checked>
                                        <label class="form-check-label fw-bold" for="estado">Activo</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Tab Almacenes -->
                        <div class="tab-pane fade" id="almacen-pane" role="tabpanel">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <small class="text-muted add-almacen-btn" id="btnAgregarAlmacen">
                                            <i class="bi bi-plus-circle"></i> Para agregar más Almacenes haga click Aquí
                                        </small>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-bordered" id="tablaAlmacenes">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>ALMACÉN</th>
                                                    <th width="150">STOCK</th>
                                                    <th width="50"></th>
                                                </tr>
                                            </thead>
                                            <tbody id="tbodyAlmacenes">
                                                <!-- Los almacenes se cargarán vía AJAX -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-primary" id="btnGuardar">
                        <i class="bi bi-save"></i> Guardar
                    </button>
                    <button type="button" class="btn btn-primary" id="btnLoading" style="display: none;" disabled>
                        <span class="spinner-border spinner-border-sm me-2"></span> Guardando...
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>