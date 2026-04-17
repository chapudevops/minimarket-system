<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Terminal POS | {{ $empresa->nombre_razon_social ?? 'Minimarket' }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: linear-gradient(135deg, #e9f0ec 0%, #d4e4db 100%); font-family: 'Inter', sans-serif; height: 100vh; overflow: hidden; }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: rgba(0,0,0,0.05); border-radius: 10px; }
        ::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.2); border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #2c6e49; }
        .pos-container { display: flex; height: 100vh; gap: 0; background: #f4f7f9; }
        .products-panel { flex: 2.5; padding: 24px 28px; overflow-y: auto; background: #ffffff; border-radius: 32px 0 0 32px; margin: 16px 0 16px 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .search-box { margin-bottom: 28px; }
        .search-box input { width: 100%; padding: 14px 24px; border: 1.5px solid #e2e8f0; border-radius: 60px; font-size: 15px; font-weight: 500; background: #f8fafc; transition: all 0.25s ease; }
        .search-box input:focus { outline: none; border-color: #10b981; background: white; box-shadow: 0 4px 12px rgba(16,185,129,0.15); }
        .products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 20px; }
        .product-card { background: white; border-radius: 24px; padding: 16px 12px 12px 12px; cursor: pointer; transition: all 0.25s cubic-bezier(0.2, 0, 0, 1); border: 1px solid #eef2f6; text-align: center; }
        .product-card:hover { transform: translateY(-6px); border-color: #d1fae5; box-shadow: 0 20px 25px -12px rgba(16,185,129,0.2); }
        .product-card.disabled { opacity: 0.5; filter: grayscale(0.1); cursor: not-allowed; background: #f9fafb; pointer-events: none; }
        .product-img { text-align: center; margin-bottom: 12px; height: 85px; display: flex; align-items: center; justify-content: center; }
        .product-image { width: 70px; height: 70px; object-fit: contain; border-radius: 16px; background: #f9fafb; padding: 6px; }
        .product-card:hover .product-image { transform: scale(1.05); }
        .product-name { font-weight: 700; font-size: 14px; margin-bottom: 8px; color: #1e293b; line-height: 1.3; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .product-price { font-size: 18px; font-weight: 800; color: #059669; letter-spacing: -0.3px; }
        .product-stock { font-size: 11px; font-weight: 500; color: #64748b; margin-top: 6px; background: #f1f5f9; display: inline-block; padding: 2px 10px; border-radius: 30px; }
        .product-stock.sin-stock { background: #fee2e2; color: #dc2626; }
        .cart-panel { flex: 1.2; background: #ffffff; display: flex; flex-direction: column; box-shadow: -8px 0 25px rgba(0,0,0,0.08); margin: 16px 16px 16px 0; border-radius: 32px; overflow: hidden; }
        .cart-header { padding: 24px 20px; background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: white; }
        .cart-header h4 { margin: 0; font-size: 1.4rem; font-weight: 700; display: flex; align-items: center; gap: 10px; }
        .company-info { font-size: 11px; color: #cbd5e1; margin-top: 8px; }
        .cart-items { flex: 1; overflow-y: auto; padding: 20px; background: #fefefe; }
        .cart-item { display: flex; justify-content: space-between; align-items: center; padding: 12px 8px; border-bottom: 1px solid #edf2f7; }
        .cart-item:hover { background: #f8fafc; border-radius: 16px; }
        .cart-item-info { flex: 2; }
        .cart-item-name { font-weight: 700; font-size: 14px; color: #0f172a; }
        .cart-item-price { font-size: 12px; font-weight: 500; color: #64748b; }
        .cart-item-qty { display: flex; align-items: center; gap: 8px; background: #f1f5f9; padding: 4px 10px; border-radius: 40px; }
        .cart-item-qty button { width: 24px; height: 24px; border: none; border-radius: 30px; background: white; font-weight: 800; }
        .cart-item-qty button:active { transform: scale(0.92); }
        .cart-item-total { font-weight: 800; color: #059669; min-width: 70px; text-align: right; font-size: 14px; }
        .cart-item-remove { color: #f97316; cursor: pointer; margin-left: 12px; opacity: 0.7; }
        .cart-item-remove:hover { opacity: 1; color: #dc2626; }
        .cart-summary { padding: 20px; border-top: 2px solid #eef2ff; background: #ffffff; border-radius: 24px 24px 0 0; }
        .summary-row { display: flex; justify-content: space-between; margin-bottom: 12px; font-weight: 500; color: #334155; }
        .summary-total { font-size: 22px; font-weight: 800; color: #0f172a; border-top: 1px dashed #cbd5e1; padding-top: 12px; margin-top: 8px; }
        .cart-buttons { display: flex; gap: 12px; margin-top: 20px; }
        .cart-buttons button { flex: 1; padding: 14px 0; border: none; border-radius: 40px; font-weight: 700; transition: 0.2s; }
        .btn-cancel { background: #f1f5f9; color: #475569; }
        .btn-cancel:hover { background: #fee2e2; color: #b91c1c; }
        .btn-pay { background: linear-gradient(95deg, #059669, #10b981); color: white; box-shadow: 0 4px 10px rgba(5,150,105,0.3); }
        .btn-pay:hover { transform: scale(0.98); }
        .empty-cart { text-align: center; color: #94a3b8; padding: 50px 20px; }
        .modal-content { border: none; border-radius: 32px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); }
        .modal-header { border-bottom: 1px solid #f0f2f5; background: white; color: #0f172a; padding: 20px 28px; }
        .modal-footer { border-top: 1px solid #f0f2f5; padding: 16px 28px; }
        .btn-primary { background: #059669; border: none; border-radius: 40px; padding: 10px 22px; font-weight: 600; }
        .btn-primary:hover { background: #047857; }
        .form-control, .form-select { border-radius: 20px; border: 1.5px solid #e2e8f0; padding: 10px 16px; }
        .form-control:focus, .form-select:focus { border-color: #10b981; box-shadow: 0 0 0 3px rgba(16,185,129,0.2); }
        .select2-container--bootstrap-5 .select2-selection { border-radius: 20px !important; border: 1.5px solid #e2e8f0 !important; padding: 5px 0; }
        .select2-container--bootstrap-5.select2-container--focus .select2-selection, 
        .select2-container--bootstrap-5.select2-container--open .select2-selection { border-color: #10b981 !important; box-shadow: 0 0 0 3px rgba(16,185,129,0.2) !important; }
        @media (max-width: 900px) { .products-grid { grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); } }
        .toast-notification { position: fixed; bottom: 20px; right: 20px; z-index: 9999; background: #1e293b; color: white; padding: 12px 24px; border-radius: 50px; font-weight: 500; box-shadow: 0 10px 25px rgba(0,0,0,0.2); animation: slideIn 0.3s ease; }
        @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
    </style>
</head>
<body>
    <div class="pos-container">
        <div class="products-panel">
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="🔍 Buscar producto por nombre, código o barras..." autofocus>
            </div>
            <div class="products-grid" id="productsGrid">
                @foreach($productos as $producto)
                <div class="product-card" data-id="{{ $producto->id }}" data-nombre="{{ $producto->descripcion }}" data-precio="{{ $producto->precio_venta }}" data-foto="{{ $producto->foto_url }}" data-stock="{{ $producto->stock_en_almacen }}">
                    <div class="product-img">
                        <img src="{{ $producto->foto_url }}" alt="{{ $producto->descripcion }}" class="product-image" onerror="this.src='{{ URL::asset('build/images/default-product.png') }}'">
                    </div>
                    <div class="product-name">{{ $producto->descripcion }}</div>
                    <div class="product-price">S/ {{ number_format($producto->precio_venta, 2) }}</div>
                    <div class="product-stock {{ $producto->stock_en_almacen <= 0 ? 'sin-stock' : '' }}">📦 Stock: {{ $producto->stock_en_almacen }}</div>
                </div>
                @endforeach
            </div>
        </div>
        
        <div class="cart-panel">
            <div class="cart-header">
                <h4><i class="bi bi-bag-check-fill"></i> Carrito de compra</h4>
                <div class="company-info">{{ $empresa->razon_social ?? 'DISTRIBUIDORA BEJAR E.I.R.L.' }}<br>RUC: {{ $empresa->ruc ?? '20100066603' }}</div>
            </div>
            <div class="cart-items" id="cartItems">
                <div class="empty-cart"><i class="bi bi-cart-x" style="font-size: 52px;"></i><p class="mt-2">Agrega productos al carrito</p></div>
            </div>
            <div class="cart-summary">
                <div class="summary-row"><span>🚀 Subtotal</span><span id="subtotal">S/ 0.00</span></div>
                <div class="summary-row"><span>📊 IGV (18%)</span><span id="igv">S/ 0.00</span></div>
                <div class="summary-row summary-total"><span>💰 Total</span><span id="total">S/ 0.00</span></div>
                <div class="cart-buttons">
                    <button class="btn-cancel" id="btnCancelar"><i class="bi bi-trash3"></i> Cancelar</button>
                    <button class="btn-pay" id="btnPagar"><i class="bi bi-lightning-charge-fill"></i> Pagar ahora</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ========== MODALES DE NOTIFICACIÓN ========== -->
    
    <!-- Modal de Advertencia (Carrito Vacío) -->
    <div class="modal fade" id="modalAdvertencia" tabindex="-1">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-warning text-white border-0">
                    <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill"></i> Atención</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <i class="bi bi-cart-x" style="font-size: 54px; color: #f59e0b;"></i>
                    <p class="mt-3 fw-semibold" id="advertenciaMensaje">No hay productos en el carrito</p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-warning" data-bs-dismiss="modal">Entendido</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Error General -->
    <div class="modal fade" id="modalError" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white border-0">
                    <h5 class="modal-title"><i class="bi bi-x-octagon-fill"></i> Error</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <i class="bi bi-emoji-frown" style="font-size: 54px; color: #dc2626;"></i>
                    <p class="mt-3 fw-semibold" id="errorMensaje">Ha ocurrido un error</p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Éxito -->
    <div class="modal fade" id="modalExito" tabindex="-1">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-success text-white border-0">
                    <h5 class="modal-title"><i class="bi bi-check-circle-fill"></i> ¡Éxito!</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <i class="bi bi-emoji-smile" style="font-size: 54px; color: #22c55e;"></i>
                    <p class="mt-3 fw-semibold" id="exitoMensaje">Operación completada</p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-success" data-bs-dismiss="modal">Perfecto</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmación General -->
    <div class="modal fade" id="modalConfirmacion" tabindex="-1">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-info text-white border-0">
                    <h5 class="modal-title"><i class="bi bi-question-circle-fill"></i> Confirmar</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <i class="bi bi-chat-question" style="font-size: 54px; color: #0ea5e9;"></i>
                    <p class="mt-3 fw-semibold" id="confirmacionMensaje">¿Estás seguro?</p>
                </div>
                <div class="modal-footer justify-content-center gap-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-info text-white" id="btnConfirmarAccion">Confirmar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Confirmar Cancelar -->
    <div class="modal fade" id="modalConfirmarCancelar" tabindex="-1">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-warning-subtle border-0">
                    <h5 class="modal-title text-warning"><i class="bi bi-exclamation-triangle-fill"></i> Cancelar venta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <i class="bi bi-cart-x" style="font-size: 54px; color: #f97316;"></i>
                    <p class="mt-3 fw-semibold">¿Eliminar todos los productos?</p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Seguir vendiendo</button>
                    <button type="button" class="btn btn-danger" id="btnConfirmarCancelar">Sí, cancelar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Stock Insuficiente -->
    <div class="modal fade" id="modalStockInsuficiente" tabindex="-1">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white border-0">
                    <h5 class="modal-title"><i class="bi bi-exclamation-octagon"></i> Sin stock</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <i class="bi bi-emoji-frown" style="font-size: 48px;"></i>
                    <p class="mt-2" id="stockMensaje">No hay suficiente stock disponible</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Entendido</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Pago -->
    <div class="modal fade" id="modalPago" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-white border-bottom">
                    <h5 class="modal-title fw-bold"><i class="bi bi-credit-card-2-front-fill text-success"></i> Procesar pago</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formPago">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-3"><label class="form-label fw-semibold">Tipo comprobante</label><select name="tipo_comprobante" id="tipo_comprobante" class="form-select"><option value="BOLETA">BOLETA</option><option value="FACTURA">FACTURA</option><option value="NOTA">NOTA</option></select></div>
                            <div class="col-md-3"><label class="form-label fw-semibold">Serie</label><input type="text" id="serie" name="serie" class="form-control" readonly></div>
                            <div class="col-md-3"><label class="form-label fw-semibold">Número</label><input type="text" id="numero" name="numero" class="form-control" readonly></div>
                            <div class="col-md-3"><label class="form-label fw-semibold">Documento</label><input type="text" id="serie_documento" class="form-control" readonly></div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Cliente <button type="button" class="btn btn-sm btn-outline-primary rounded-pill ms-2" id="btnNuevoCliente"><i class="bi bi-plus-circle"></i> Nuevo</button></label>
                                <select id="clienteSelect" class="form-select" style="width: 100%;"><option value="">Seleccionar cliente...</option></select>
                                <input type="hidden" id="cliente_id" name="cliente_id">
                            </div>
                            <div class="col-md-6"><label class="form-label fw-semibold">Tipo venta</label><select name="tipo_venta" id="tipo_venta" class="form-select"><option value="CONTADO">Contado</option><option value="CREDITO">Crédito</option></select></div>
                            <div class="col-md-6"><label class="form-label fw-semibold">Forma pago</label><select name="forma_pago" id="forma_pago" class="form-select"><option value="EFECTIVO">Efectivo</option><option value="YAPE">Yape</option><option value="TRANSFERENCIA">Transferencia</option><option value="TARJETA">Tarjeta</option></select></div>
                        </div>
                        <div id="seccionContado" class="mt-3">
                            <div class="row g-3">
                                <div class="col-md-6"><label class="form-label fw-semibold">Total a pagar</label><div class="input-group"><span class="input-group-text bg-light">S/</span><input type="text" id="total_pagar" class="form-control bg-light" readonly></div></div>
                                <div class="col-md-6"><label class="form-label fw-semibold">Pagando</label><div class="input-group"><span class="input-group-text bg-light">S/</span><input type="number" step="0.01" name="pagado" id="pagado" class="form-control" value="0.00"></div></div>
                                <div class="col-md-6"><label class="form-label fw-semibold">Diferencia</label><div class="input-group"><span class="input-group-text bg-light">S/</span><input type="text" id="diferencia" class="form-control" readonly></div></div>
                            </div>
                        </div>
                        <div id="seccionCredito" style="display: none;" class="mt-3">
                            <div class="row g-3">
                                <div class="col-md-6"><label class="form-label fw-semibold">Total a crédito</label><input type="text" id="total_credito" class="form-control bg-light" readonly></div>
                                <div class="col-md-6"><label class="form-label fw-semibold">Número de cuotas</label><select name="numero_cuotas" id="numero_cuotas" class="form-select"><option value="1">1 Cuota</option><option value="2">2 Cuotas</option><option value="3">3 Cuotas</option><option value="4">4 Cuotas</option><option value="5">5 Cuotas</option><option value="6">6 Cuotas</option></select></div>
                            </div>
                            <div class="table-responsive mt-3"><table class="table table-bordered table-sm"><thead class="table-light"><tr><th>Cuota</th><th>Vencimiento</th><th>Monto</th></tr></thead><tbody id="cuotasBody"></tbody></table></div>
                        </div>
                        <div class="mt-3"><div class="form-check"><input class="form-check-input" type="checkbox" name="detraccion" id="detraccion" value="1"><label class="form-check-label"> Aplicar detracción</label></div></div>
                        <div class="mt-3"><textarea name="observaciones" id="observaciones" class="form-control" rows="2" placeholder="Observaciones (opcional)"></textarea></div>
                        <input type="hidden" id="productos_json" name="productos_json">
                        <input type="hidden" id="subtotal_hidden" name="subtotal">
                        <input type="hidden" id="igv_hidden" name="igv">
                        <input type="hidden" id="total_hidden" name="total">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success rounded-pill px-4" id="btnConfirmarPago"><i class="bi bi-check-lg"></i> Confirmar</button>
                    <button type="button" class="btn btn-success rounded-pill" id="btnLoadingPago" style="display: none;" disabled><span class="spinner-border spinner-border-sm me-2"></span>Procesando...</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Nuevo Cliente -->
    <div class="modal fade" id="modalNuevoCliente" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title fw-bold"><i class="bi bi-person-plus text-success"></i> Nuevo cliente</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <form id="formNuevoCliente">@csrf
                    <div class="modal-body">
                        <div class="mb-3"><label class="form-label fw-semibold">Tipo documento</label><select name="tipo_documento" class="form-select" required><option value="DNI">DNI</option><option value="RUC">RUC</option><option value="CE">CE</option></select></div>
                        <div class="mb-3"><label class="form-label fw-semibold">Número documento</label><input type="text" name="numero_documento" class="form-control" required></div>
                        <div class="mb-3"><label class="form-label fw-semibold">Nombre / Razón Social</label><input type="text" name="nombre_razon_social" class="form-control" required></div>
                        <div class="mb-3"><label class="form-label fw-semibold">Dirección</label><textarea name="direccion" class="form-control" rows="2"></textarea></div>
                        <div class="mb-3"><label class="form-label fw-semibold">Teléfono</label><input type="text" name="telefono" class="form-control"></div>
                        <div class="mb-3"><label class="form-label fw-semibold">Departamento</label><input type="text" name="departamento" class="form-control"></div>
                        <div class="mb-3"><label class="form-label fw-semibold">Provincia</label><input type="text" name="provincia" class="form-control"></div>
                        <div class="mb-3"><label class="form-label fw-semibold">Distrito</label><input type="text" name="distrito" class="form-control"></div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Guardar cliente</button></div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Resultado Venta -->
    <div class="modal fade" id="modalResultado" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-success text-white"><h5 class="modal-title"><i class="bi bi-check-circle-fill"></i> ¡Venta exitosa!</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="text-center"><i class="bi bi-receipt" style="font-size: 60px;"></i><h4 class="mt-2">Comprobante emitido</h4></div>
                    <div class="row mt-3"><div class="col-md-6"><strong>Documento:</strong> <span id="resultado_documento"></span><br><strong>Cliente:</strong> <span id="resultado_cliente_nombre"></span></div><div class="col-md-6"><strong>Total:</strong> <span id="resultado_total"></span><br><strong>Forma pago:</strong> <span id="resultado_forma_pago"></span></div></div>
                    <div class="mt-3 d-flex justify-content-end gap-2"><button class="btn btn-outline-warning" id="btnImprimirTicket"><i class="bi bi-printer"></i> Ticket</button><button class="btn btn-danger" id="btnDescargarPDF"><i class="bi bi-file-pdf"></i> PDF A4</button></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button></div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        let cart = [];
        let ultimaVentaId = null;
        let confirmacionCallback = null;
        let productosStock = new Map();

        // ========== FUNCIONES DE MODALES ==========
        function mostrarAdvertencia(mensaje) {
            $('#advertenciaMensaje').text(mensaje);
            $('#modalAdvertencia').modal('show');
            playErrorBeep();
        }

        function mostrarError(mensaje) {
            $('#errorMensaje').text(mensaje);
            $('#modalError').modal('show');
            playErrorBeep();
        }

        function mostrarExito(mensaje) {
            $('#exitoMensaje').text(mensaje);
            $('#modalExito').modal('show');
            playSuccessBeep();
        }

        function mostrarConfirmacion(mensaje, callback) {
            $('#confirmacionMensaje').text(mensaje);
            confirmacionCallback = callback;
            $('#modalConfirmacion').modal('show');
        }

        $('#btnConfirmarAccion').click(function() {
            $('#modalConfirmacion').modal('hide');
            if (confirmacionCallback) {
                confirmacionCallback();
                confirmacionCallback = null;
            }
        });

        // Inicializar mapa de stocks
        function inicializarMapaStocks() {
            $('.product-card').each(function() {
                const id = $(this).data('id');
                const stock = parseInt($(this).data('stock'));
                productosStock.set(id, stock);
            });
        }

        function actualizarStockProducto(productoId, cantidadVendida) {
            const stockActual = productosStock.get(productoId) || 0;
            const nuevoStock = Math.max(0, stockActual - cantidadVendida);
            productosStock.set(productoId, nuevoStock);
            
            const $productCard = $(`.product-card[data-id="${productoId}"]`);
            if ($productCard.length) {
                $productCard.data('stock', nuevoStock);
                $productCard.find('.product-stock').text(`📦 Stock: ${nuevoStock}`);
                if (nuevoStock <= 0) {
                    $productCard.find('.product-stock').addClass('sin-stock');
                    $productCard.addClass('disabled');
                } else {
                    $productCard.find('.product-stock').removeClass('sin-stock');
                    $productCard.removeClass('disabled');
                }
            }
            
            mostrarToast(`Stock actualizado: ${nuevoStock} unidades restantes`);
        }

        function mostrarToast(mensaje) {
            const toast = $(`<div class="toast-notification">${mensaje}</div>`);
            $('body').append(toast);
            setTimeout(() => toast.fadeOut(300, function() { $(this).remove(); }), 2500);
        }

        // Sistema de sonidos
        class POSBeep {
            constructor() { this.audioContext = null; this.init(); }
            init() { try { this.audioContext = new (window.AudioContext || window.webkitAudioContext)(); } catch(e) { console.log('Web Audio API no soportada'); } }
            playAddBeep() { if (!this.audioContext) return; if (this.audioContext.state === 'suspended') this.audioContext.resume(); const now = this.audioContext.currentTime; const oscillator = this.audioContext.createOscillator(); const gainNode = this.audioContext.createGain(); oscillator.connect(gainNode); gainNode.connect(this.audioContext.destination); oscillator.type = 'sine'; oscillator.frequency.value = 880; gainNode.gain.setValueAtTime(0.3, now); gainNode.gain.exponentialRampToValueAtTime(0.00001, now + 0.2); oscillator.start(now); oscillator.stop(now + 0.2); }
            playRemoveBeep() { if (!this.audioContext) return; if (this.audioContext.state === 'suspended') this.audioContext.resume(); const now = this.audioContext.currentTime; const oscillator = this.audioContext.createOscillator(); const gainNode = this.audioContext.createGain(); oscillator.connect(gainNode); gainNode.connect(this.audioContext.destination); oscillator.type = 'sine'; oscillator.frequency.value = 440; gainNode.gain.setValueAtTime(0.3, now); gainNode.gain.exponentialRampToValueAtTime(0.00001, now + 0.15); oscillator.start(now); oscillator.stop(now + 0.15); }
            playSuccessBeep() { if (!this.audioContext) return; if (this.audioContext.state === 'suspended') this.audioContext.resume(); const now = this.audioContext.currentTime; const osc1 = this.audioContext.createOscillator(); const gain1 = this.audioContext.createGain(); osc1.connect(gain1); gain1.connect(this.audioContext.destination); osc1.type = 'sine'; osc1.frequency.value = 523.25; gain1.gain.setValueAtTime(0.2, now); gain1.gain.exponentialRampToValueAtTime(0.00001, now + 0.15); osc1.start(now); osc1.stop(now + 0.15); const osc2 = this.audioContext.createOscillator(); const gain2 = this.audioContext.createGain(); osc2.connect(gain2); gain2.connect(this.audioContext.destination); osc2.type = 'sine'; osc2.frequency.value = 659.25; gain2.gain.setValueAtTime(0.2, now + 0.1); gain2.gain.exponentialRampToValueAtTime(0.00001, now + 0.25); osc2.start(now + 0.1); osc2.stop(now + 0.25); }
            playErrorBeep() { if (!this.audioContext) return; if (this.audioContext.state === 'suspended') this.audioContext.resume(); const now = this.audioContext.currentTime; const oscillator = this.audioContext.createOscillator(); const gainNode = this.audioContext.createGain(); oscillator.connect(gainNode); gainNode.connect(this.audioContext.destination); oscillator.type = 'square'; oscillator.frequency.value = 440; gainNode.gain.setValueAtTime(0.3, now); gainNode.gain.exponentialRampToValueAtTime(0.00001, now + 0.5); oscillator.start(now); oscillator.stop(now + 0.5); }
            playCancelBeep() { if (!this.audioContext) return; if (this.audioContext.state === 'suspended') this.audioContext.resume(); const now = this.audioContext.currentTime; const oscillator = this.audioContext.createOscillator(); const gainNode = this.audioContext.createGain(); oscillator.connect(gainNode); gainNode.connect(this.audioContext.destination); oscillator.type = 'sawtooth'; oscillator.frequency.value = 330; gainNode.gain.setValueAtTime(0.3, now); gainNode.gain.exponentialRampToValueAtTime(0.00001, now + 0.4); oscillator.start(now); oscillator.stop(now + 0.4); }
        }
        const posBeep = new POSBeep();
        function playAddBeep() { posBeep.playAddBeep(); }
        function playRemoveBeep() { posBeep.playRemoveBeep(); }
        function playSuccessBeep() { posBeep.playSuccessBeep(); }
        function playErrorBeep() { posBeep.playErrorBeep(); }
        function playCancelBeep() { posBeep.playCancelBeep(); }

        function mostrarModalStock(mensaje) {
            $('#stockMensaje').text(mensaje);
            $('#modalStockInsuficiente').modal('show');
            playErrorBeep();
        }

        function updateCartUI() {
            const cartContainer = $('#cartItems');
            const subtotalSpan = $('#subtotal');
            const igvSpan = $('#igv');
            const totalSpan = $('#total');
            if (cart.length === 0) {
                cartContainer.html(`<div class="empty-cart"><i class="bi bi-cart-x" style="font-size: 52px;"></i><p class="mt-2">Agrega productos al carrito</p></div>`);
                subtotalSpan.text('S/ 0.00'); igvSpan.text('S/ 0.00'); totalSpan.text('S/ 0.00');
                return;
            }
            let html = '', subtotal = 0;
            cart.forEach((item, idx) => {
                const itemTotal = item.precio * item.cantidad;
                subtotal += itemTotal;
                html += `<div class="cart-item" data-index="${idx}">
                            <div class="cart-item-info">
                                <div class="cart-item-name">${item.nombre}</div>
                                <div class="cart-item-price">S/ ${item.precio.toFixed(2)}</div>
                            </div>
                            <div class="cart-item-qty">
                                <button class="qty-minus" data-index="${idx}">-</button>
                                <span>${item.cantidad}</span>
                                <button class="qty-plus" data-index="${idx}">+</button>
                            </div>
                            <div class="cart-item-total">S/ ${itemTotal.toFixed(2)}</div>
                            <div class="cart-item-remove" data-index="${idx}"><i class="bi bi-trash3"></i></div>
                        </div>`;
            });
            cartContainer.html(html);
            const igv = subtotal * 0.18;
            const total = subtotal + igv;
            subtotalSpan.text(`S/ ${subtotal.toFixed(2)}`);
            igvSpan.text(`S/ ${igv.toFixed(2)}`);
            totalSpan.text(`S/ ${total.toFixed(2)}`);

            $('.qty-minus').off('click').on('click', function() {
                const idx = $(this).data('index');
                if (cart[idx].cantidad > 1) {
                    cart[idx].cantidad--;
                    updateCartUI();
                    playRemoveBeep();
                } else {
                    cart.splice(idx, 1);
                    updateCartUI();
                    playRemoveBeep();
                }
            });
            $('.qty-plus').off('click').on('click', function() {
                const idx = $(this).data('index');
                const stockDisponible = productosStock.get(cart[idx].id) || 0;
                if (cart[idx].cantidad + 1 > stockDisponible) {
                    mostrarModalStock(`No hay suficiente stock para "${cart[idx].nombre}". Stock disponible: ${stockDisponible}`);
                    return;
                }
                cart[idx].cantidad++;
                updateCartUI();
                playAddBeep();
            });
            $('.cart-item-remove').off('click').on('click', function() {
                const idx = $(this).data('index');
                cart.splice(idx, 1);
                updateCartUI();
                playRemoveBeep();
            });
        }

        function addToCart(producto) {
            const stockDisponible = productosStock.get(producto.id) || 0;
            const existing = cart.find(item => item.id === producto.id);
            const nuevaCantidad = existing ? existing.cantidad + 1 : 1;
            
            if (nuevaCantidad > stockDisponible) {
                mostrarModalStock(`No hay suficiente stock para "${producto.nombre}". Stock disponible: ${stockDisponible}`);
                return false;
            }
            
            if (existing) {
                existing.cantidad++;
            } else {
                cart.push({ id: producto.id, nombre: producto.nombre, precio: producto.precio, cantidad: 1, almacen_id: 1 });
            }
            updateCartUI();
            playAddBeep();
            return true;
        }

        // Evento para agregar productos
        $('.product-card').click(function() {
            if ($(this).hasClass('disabled')) return;
            const producto = {
                id: $(this).data('id'),
                nombre: $(this).data('nombre'),
                precio: parseFloat($(this).data('precio')),
                almacen_id: 1
            };
            addToCart(producto);
        });

        // Búsqueda de productos
        let searchTimeout;
        $('#searchInput').on('keyup', function() {
            clearTimeout(searchTimeout);
            const search = $(this).val();
            searchTimeout = setTimeout(function() {
                if (search.length > 0) {
                    $.ajax({
                        url: '/terminal/search',
                        type: 'GET',
                        data: { search: search },
                        success: function(response) {
                            if (response.success) {
                                let html = '';
                                response.data.forEach(producto => {
                                    const stockActual = productosStock.get(producto.id) ?? producto.stock_total;
                                    const stockClass = stockActual <= 0 ? 'disabled' : '';
                                    const precio = typeof producto.precio_venta === 'number' ? producto.precio_venta : parseFloat(producto.precio_venta);
                                    html += `<div class="product-card ${stockClass}" data-id="${producto.id}" data-nombre="${producto.descripcion}" data-precio="${precio}" data-foto="${producto.foto}" data-stock="${stockActual}">
                                                <div class="product-img"><img src="${producto.foto}" alt="${producto.descripcion}" class="product-image" onerror="this.src='{{ URL::asset('build/images/default-product.png') }}'"></div>
                                                <div class="product-name">${producto.descripcion}</div>
                                                <div class="product-price">S/ ${precio.toFixed(2)}</div>
                                                <div class="product-stock ${stockActual <= 0 ? 'sin-stock' : ''}">Stock: ${stockActual}</div>
                                            </div>`;
                                    productosStock.set(producto.id, stockActual);
                                });
                                $('#productsGrid').html(html);
                                $('.product-card').off('click').on('click', function() {
                                    if ($(this).hasClass('disabled')) return;
                                    const producto = {
                                        id: $(this).data('id'),
                                        nombre: $(this).data('nombre'),
                                        precio: parseFloat($(this).data('precio')),
                                        almacen_id: 1
                                    };
                                    addToCart(producto);
                                });
                            }
                        }
                    });
                } else {
                    location.reload();
                }
            }, 300);
        });

        // Cancelar venta
        $('#btnCancelar').click(function() {
            if (cart.length === 0) {
                mostrarAdvertencia('No hay productos en el carrito para cancelar');
                return;
            }
            $('#modalConfirmarCancelar').modal('show');
        });
        $('#btnConfirmarCancelar').click(function() {
            cart = [];
            updateCartUI();
            playCancelBeep();
            $('#modalConfirmarCancelar').modal('hide');
        });

        // Abrir modal de pago
        $('#btnPagar').click(function() {
            if (cart.length === 0) {
                mostrarAdvertencia('No hay productos en el carrito para procesar el pago');
                return;
            }
            const total = parseFloat($('#total').text().replace('S/ ', ''));
            $('#total_pagar').val(total.toFixed(2));
            $('#total_hidden').val(total);
            $('#subtotal_hidden').val(parseFloat($('#subtotal').text().replace('S/ ', '')));
            $('#igv_hidden').val(parseFloat($('#igv').text().replace('S/ ', '')));
            $('#pagado').val(total.toFixed(2));
            $('#diferencia').val('0.00');
            $('#total_credito').val(total.toFixed(2));
            $('#seccionContado').show();
            $('#seccionCredito').hide();
            $('#tipo_venta').val('CONTADO');
            cargarSerie();
            cargarClientesSelect2();
            $('#modalPago').modal('show');
        });

        function cargarSerie() {
            $.ajax({
                url: '/terminal/series',
                type: 'GET',
                data: { tipo: $('#tipo_comprobante').val() },
                success: function(response) {
                    if (response.success) {
                        $('#serie').val(response.serie);
                        $('#numero').val(response.numero);
                        $('#serie_documento').val(response.documento);
                    } else {
                        $('#serie').val('');
                        $('#numero').val('');
                        $('#serie_documento').val(response.message || 'Sin serie configurada');
                    }
                }
            });
        }

        function cargarClientesSelect2() {
            $('#clienteSelect').select2({
                dropdownParent: $('#modalPago'),
                theme: 'bootstrap-5',
                placeholder: 'Buscar cliente por nombre o documento...',
                allowClear: true,
                ajax: {
                    url: '/terminal/search-clientes',
                    dataType: 'json',
                    delay: 300,
                    data: function(params) { return { q: params.term || '' }; },
                    processResults: function(data) {
                        if (data.success && data.clientes) {
                            return { results: data.clientes.map(c => ({ id: c.id, text: c.numero_documento + ' - ' + c.nombre_razon_social })) };
                        }
                        return { results: [] };
                    }
                }
            });
            $('#clienteSelect').on('select2:select', function(e) { $('#cliente_id').val(e.params.data.id); });
            $('#clienteSelect').on('select2:clear', function() { $('#cliente_id').val(''); });
        }

        $('#tipo_comprobante').change(cargarSerie);
        $('#tipo_venta').change(function() {
            const total = parseFloat($('#total_pagar').val());
            if ($(this).val() === 'CREDITO') {
                $('#seccionContado').hide();
                $('#seccionCredito').show();
                $('#total_credito').val(total.toFixed(2));
                generarCuotas();
            } else {
                $('#seccionContado').show();
                $('#seccionCredito').hide();
                $('#pagado').val(total.toFixed(2));
                calcularDiferencia();
            }
        });

        function calcularDiferencia() {
            const total = parseFloat($('#total_pagar').val());
            const pagado = parseFloat($('#pagado').val()) || 0;
            $('#diferencia').val((pagado - total).toFixed(2));
        }
        $('#pagado').on('keyup change', calcularDiferencia);

        function generarCuotas() {
            const total = parseFloat($('#total_credito').val()) || 0;
            const cuotas = parseInt($('#numero_cuotas').val()) || 1;
            const montoCuota = total / cuotas;
            let html = '';
            for (let i = 1; i <= cuotas; i++) {
                const fecha = new Date();
                fecha.setMonth(fecha.getMonth() + i);
                html += `<tr><td class="text-center">${i}</td><td>${fecha.toISOString().split('T')[0]}</td><td class="text-end">S/ ${montoCuota.toFixed(2)}</td></tr>`;
            }
            $('#cuotasBody').html(html);
        }
        $('#numero_cuotas').change(generarCuotas);

        $('#btnNuevoCliente').click(() => $('#modalNuevoCliente').modal('show'));
        $('#formNuevoCliente').submit(function(e) {
            e.preventDefault();
            $.ajax({
                url: '/clientes',
                type: 'POST',
                data: $(this).serialize(),
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function(response) {
                    if (response.success) {
                        $('#modalNuevoCliente').modal('hide');
                        mostrarExito('Cliente registrado exitosamente');
                        $('#clienteSelect').empty().trigger('change');
                        cargarClientesSelect2();
                        $('#formNuevoCliente')[0].reset();
                    } else {
                        mostrarError(response.message || 'Error al guardar el cliente');
                    }
                },
                error: function(xhr) {
                    mostrarError(xhr.responseJSON?.message || 'Error al guardar cliente');
                }
            });
        });

        // Confirmar pago
        $('#btnConfirmarPago').click(function() {
            if (cart.length === 0) {
                mostrarAdvertencia('No hay productos en el carrito');
                return;
            }
            
            for (const item of cart) {
                const stockActual = productosStock.get(item.id) || 0;
                if (item.cantidad > stockActual) {
                    mostrarModalStock(`Stock insuficiente para "${item.nombre}". Disponible: ${stockActual}`);
                    return;
                }
            }
            
            const productos = cart.map(item => ({
                id: item.id,
                cantidad: item.cantidad,
                precio: item.precio,
                almacen_id: item.almacen_id || 1
            }));
            
            const formData = new FormData();
            formData.append('tipo_comprobante', $('#tipo_comprobante').val());
            formData.append('serie', $('#serie').val());
            formData.append('numero', $('#numero').val());
            formData.append('cliente_id', $('#cliente_id').val() || '');
            formData.append('tipo_venta', $('#tipo_venta').val());
            formData.append('forma_pago', $('#forma_pago').val());
            formData.append('pagado', $('#pagado').val());
            formData.append('detraccion', $('#detraccion').is(':checked') ? 1 : 0);
            formData.append('observaciones', $('#observaciones').val());
            formData.append('subtotal', $('#subtotal_hidden').val());
            formData.append('igv', $('#igv_hidden').val());
            formData.append('total', $('#total_hidden').val());
            formData.append('productos_json', JSON.stringify(productos));
            if ($('#tipo_venta').val() === 'CREDITO') {
                formData.append('numero_cuotas', $('#numero_cuotas').val());
            }
            productos.forEach((producto, index) => {
                formData.append(`productos[${index}][id]`, producto.id);
                formData.append(`productos[${index}][cantidad]`, producto.cantidad);
                formData.append(`productos[${index}][precio]`, producto.precio);
                formData.append(`productos[${index}][almacen_id]`, producto.almacen_id);
            });

            $('#btnConfirmarPago').hide();
            $('#btnLoadingPago').show();

            $.ajax({
                url: '/terminal/procesar-pago',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function(response) {
                    if (response.success) {
                        for (const item of cart) {
                            actualizarStockProducto(item.id, item.cantidad);
                        }
                        
                        $('#modalPago').modal('hide');
                        ultimaVentaId = response.data.venta_id;
                        $('#resultado_documento').text(response.data.documento);
                        $('#resultado_total').text('S/ ' + parseFloat(response.data.total).toFixed(2));
                        $('#resultado_forma_pago').text($('#forma_pago').val());
                        const clienteNombre = $('#clienteSelect').find('option:selected').text() || 'CLIENTES VARIOS';
                        $('#resultado_cliente_nombre').text(clienteNombre);
                        $('#modalResultado').modal('show');
                        cart = [];
                        updateCartUI();
                        playSuccessBeep();
                        $('#clienteSelect').val(null).trigger('change');
                        $('#cliente_id').val('');
                    } else {
                        mostrarError(response.message);
                    }
                },
                error: function(xhr) {
                    mostrarError(xhr.responseJSON?.message || 'Error al procesar el pago');
                },
                complete: function() {
                    $('#btnConfirmarPago').show();
                    $('#btnLoadingPago').hide();
                }
            });
        });

        $('#btnImprimirTicket').click(function() {
            if (ultimaVentaId) {
                window.open('/ventas/' + ultimaVentaId + '/ticket', '_blank', 'width=400,height=600');
            } else {
                mostrarAdvertencia('No hay una venta reciente para imprimir');
            }
        });
        
        $('#btnDescargarPDF').click(function() {
            if (ultimaVentaId) {
                window.open('/ventas/' + ultimaVentaId + '/pdf', '_blank');
            } else {
                mostrarAdvertencia('No hay una venta reciente para descargar');
            }
        });

        inicializarMapaStocks();
    </script>
</body>
</html>