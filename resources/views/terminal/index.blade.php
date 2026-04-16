<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Terminal POS - {{ $empresa->nombre_razon_social ?? 'Minimarket' }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f0f2f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; height: 100vh; overflow: hidden; }
        .pos-container { display: flex; height: 100vh; }
        .products-panel { flex: 2; padding: 20px; overflow-y: auto; background: #f8f9fa; }
        .search-box { margin-bottom: 20px; }
        .search-box input { width: 100%; padding: 12px 20px; border: 2px solid #e9ecef; border-radius: 10px; font-size: 16px; transition: all 0.3s; }
        .search-box input:focus { outline: none; border-color: #28a745; box-shadow: 0 0 0 3px rgba(40,167,69,0.1); }
        .products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; }
        .product-card { background: white; border-radius: 12px; padding: 15px; cursor: pointer; transition: all 0.3s; border: 1px solid #e9ecef; text-align: center; }
        .product-card:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); border-color: #28a745; }
        .product-card.disabled { opacity: 0.5; cursor: not-allowed; background: #f8f9fa; }
        .product-img { text-align: center; margin-bottom: 10px; }
        .product-image { width: 80px; height: 80px; object-fit: contain; border-radius: 8px; background: #f8f9fa; padding: 5px; transition: transform 0.3s ease; }
        .product-card:hover .product-image { transform: scale(1.05); }
        .product-name { font-weight: 600; font-size: 14px; margin-bottom: 8px; color: #2c3e50; }
        .product-price { font-size: 18px; font-weight: bold; color: #28a745; }
        .product-stock { font-size: 11px; color: #6c757d; margin-top: 5px; }
        .cart-panel { flex: 1; background: white; border-left: 1px solid #e9ecef; display: flex; flex-direction: column; box-shadow: -2px 0 10px rgba(0,0,0,0.05); }
        .cart-header { padding: 20px; background: #2c3e50; color: white; }
        .cart-header h4 { margin: 0; font-size: 18px; }
        .company-info { font-size: 12px; color: #ecf0f1; margin-top: 5px; }
        .cart-items { flex: 1; overflow-y: auto; padding: 15px; }
        .cart-item { display: flex; justify-content: space-between; align-items: center; padding: 10px; border-bottom: 1px solid #e9ecef; margin-bottom: 10px; }
        .cart-item-info { flex: 2; }
        .cart-item-name { font-weight: 600; font-size: 14px; }
        .cart-item-price { font-size: 12px; color: #6c757d; }
        .cart-item-qty { display: flex; align-items: center; gap: 8px; }
        .cart-item-qty button { width: 25px; height: 25px; border: none; border-radius: 5px; background: #f8f9fa; font-weight: bold; }
        .cart-item-qty span { width: 30px; text-align: center; }
        .cart-item-total { font-weight: bold; color: #28a745; min-width: 70px; text-align: right; }
        .cart-item-remove { color: #dc3545; cursor: pointer; margin-left: 10px; }
        .cart-summary { padding: 20px; border-top: 2px solid #e9ecef; background: #f8f9fa; }
        .summary-row { display: flex; justify-content: space-between; margin-bottom: 10px; }
        .summary-total { font-size: 20px; font-weight: bold; color: #28a745; border-top: 1px solid #dee2e6; padding-top: 10px; margin-top: 10px; }
        .cart-buttons { display: flex; gap: 10px; margin-top: 15px; }
        .cart-buttons button { flex: 1; padding: 12px; border: none; border-radius: 8px; font-weight: bold; }
        .btn-cancel { background: #6c757d; color: white; }
        .btn-pay { background: #28a745; color: white; }
        .empty-cart { text-align: center; color: #6c757d; padding: 40px; }
        .modal-pago { max-width: 800px; }
        .cuotas-table { margin-top: 15px; }
        .cuotas-table th, .cuotas-table td { font-size: 12px; padding: 8px; }
        .cliente-search-result { max-height: 200px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 8px; display: none; position: absolute; background: white; z-index: 1000; width: 100%; }
        .cliente-result-item { padding: 10px; border-bottom: 1px solid #dee2e6; cursor: pointer; }
        .cliente-result-item:hover { background: #f8f9fa; }
        .position-relative { position: relative; }
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; }
        ::-webkit-scrollbar-thumb { background: #c1c1c1; border-radius: 4px; }
        @media (max-width: 768px) { .products-grid { grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); } }
        .detalle-linea { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee; }
        .detalle-label { font-weight: 600; color: #555; }
        .detalle-valor { font-weight: 500; }
    </style>
</head>
<body>
    <div class="pos-container">
        <div class="products-panel">
            <div class="search-box">
                <input type="text" id="searchInput" class="form-control" placeholder="🔍 Buscar por nombre, código o código de barras..." autofocus>
            </div>
            <div class="products-grid" id="productsGrid">
                @foreach($productos as $producto)
                <div class="product-card" data-id="{{ $producto->id }}" data-nombre="{{ $producto->descripcion }}" data-precio="{{ $producto->precio_venta }}" data-foto="{{ $producto->foto_url }}" data-stock="{{ $producto->stock_total }}">
                    <div class="product-img">
                        <img src="{{ $producto->foto_url }}" alt="{{ $producto->descripcion }}" class="product-image" onerror="this.src='{{ URL::asset('build/images/default-product.png') }}'">
                    </div>
                    <div class="product-name">{{ $producto->descripcion }}</div>
                    <div class="product-price">S/ {{ number_format($producto->precio_venta, 2) }}</div>
                    <div class="product-stock">Stock: {{ $producto->stock_total }}</div>
                </div>
                @endforeach
            </div>
        </div>
        
        <div class="cart-panel">
            <div class="cart-header">
                <h4><i class="bi bi-cart3"></i> Carrito de Ventas</h4>
                <div class="company-info">{{ $empresa->razon_social ?? 'DISTRIBUIDORA BEJAR E.I.R.L.' }}<br>R.U.C. {{ $empresa->ruc ?? '20100066603' }}</div>
            </div>
            <div class="cart-items" id="cartItems">
                <div class="empty-cart"><i class="bi bi-cart4" style="font-size: 48px;"></i><p>Agregue productos al carrito</p></div>
            </div>
            <div class="cart-summary">
                <div class="summary-row"><span>OP. Gravadas</span><span id="subtotal">S/ 0.00</span></div>
                <div class="summary-row"><span>IGV (18%)</span><span id="igv">S/ 0.00</span></div>
                <div class="summary-row summary-total"><span>Total</span><span id="total">S/ 0.00</span></div>
                <div class="cart-buttons">
                    <button class="btn-cancel" id="btnCancelar"><i class="bi bi-x-circle"></i> Cancelar venta</button>
                    <button class="btn-pay" id="btnPagar"><i class="bi bi-credit-card"></i> Procesar Pago</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Confirmar Cancelar -->
    <div class="modal fade" id="modalConfirmarCancelar" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title"><i class="bi bi-exclamation-triangle"></i> Confirmar Cancelación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <i class="bi bi-cart-x" style="font-size: 48px; color: #ffc107;"></i>
                    <p class="mt-3">¿Estás seguro de cancelar la venta actual?</p>
                    <p class="text-muted">Se eliminarán todos los productos del carrito.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No, seguir vendiendo</button>
                    <button type="button" class="btn btn-warning" id="btnConfirmarCancelar">Sí, cancelar venta</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Procesar Pago -->
    <div class="modal fade" id="modalPago" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-pago modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-credit-card"></i> Procesar Pago</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formPago">
                        @csrf
                        <div class="row">
                            <div class="col-md-3 mb-3"><label class="form-label fw-bold">Documento</label><select name="tipo_comprobante" id="tipo_comprobante" class="form-control" required><option value="BOLETA">BOLETA</option><option value="FACTURA">FACTURA</option><option value="NOTA">NOTA</option></select></div>
                            <div class="col-md-3 mb-3"><label class="form-label fw-bold">Serie</label><input type="text" id="serie" name="serie" class="form-control" readonly placeholder="Ej: F001"></div>
                            <div class="col-md-3 mb-3"><label class="form-label fw-bold">Número</label><input type="text" id="numero" name="numero" class="form-control" readonly placeholder="Ej: 1"></div>
                            <div class="col-md-3 mb-3"><label class="form-label fw-bold">Documento</label><input type="text" id="serie_documento" class="form-control" readonly placeholder="F001-00000001"></div>
                            <div class="col-md-12 mb-3 position-relative"><label class="form-label fw-bold">Cliente <button type="button" class="btn btn-sm btn-primary" id="btnNuevoCliente"><i class="bi bi-plus-circle"></i> Nuevo</button></label>
                                <input type="text" id="searchCliente" class="form-control" placeholder="Buscar por nombre o documento...">
                                <div id="clienteResults" class="cliente-search-result"></div>
                                <input type="hidden" id="cliente_id" name="cliente_id">
                                <div id="clienteSeleccionado" class="mt-2 small text-muted"></div>
                            </div>
                            <div class="col-md-6 mb-3"><label class="form-label fw-bold">Tipo de Venta</label><select name="tipo_venta" id="tipo_venta" class="form-control" required><option value="CONTADO">Contado</option><option value="CREDITO">Crédito</option></select></div>
                            <div class="col-md-6 mb-3"><label class="form-label fw-bold">Forma de Pago</label><select name="forma_pago" id="forma_pago" class="form-control" required><option value="EFECTIVO">Efectivo</option><option value="YAPE">Yape</option><option value="TRANSFERENCIA">Transferencia</option><option value="TARJETA">Tarjeta</option></select></div>
                        </div>
                        <div id="seccionContado">
                            <div class="row">
                                <div class="col-md-6 mb-3"><label class="form-label fw-bold">Total a Pagar</label><div class="input-group"><span class="input-group-text">S/</span><input type="text" id="total_pagar" class="form-control" readonly></div></div>
                                <div class="col-md-6 mb-3"><label class="form-label fw-bold">Pagando</label><div class="input-group"><span class="input-group-text">S/</span><input type="number" step="0.01" name="pagado" id="pagado" class="form-control" value="0.00" required></div></div>
                                <div class="col-md-6 mb-3"><label class="form-label fw-bold">Diferencia</label><div class="input-group"><span class="input-group-text">S/</span><input type="text" id="diferencia" class="form-control" readonly></div></div>
                            </div>
                        </div>
                        <div id="seccionCredito" style="display: none;">
                            <div class="row">
                                <div class="col-md-6 mb-3"><label class="form-label fw-bold">Condiciones de Crédito</label><div class="input-group"><span class="input-group-text">S/</span><input type="text" id="total_credito" class="form-control" readonly></div></div>
                                <div class="col-md-6 mb-3"><label class="form-label fw-bold">Número de Cuotas</label><select name="numero_cuotas" id="numero_cuotas" class="form-control"><option value="1">1 Cuota</option><option value="2">2 Cuotas</option><option value="3">3 Cuotas</option><option value="4">4 Cuotas</option><option value="5">5 Cuotas</option><option value="6">6 Cuotas</option></select></div>
                                <div class="col-md-6 mb-3"><label class="form-label fw-bold">Total a Crédito</label><div class="input-group"><span class="input-group-text">S/</span><input type="text" id="total_a_credito" class="form-control" readonly></div></div>
                            </div>
                            <div class="table-responsive cuotas-table"><table class="table table-bordered"><thead><tr><th>Cuota</th><th>Fecha de Vencimiento</th><th>Monto</th><th>Estado</th></tr></thead><tbody id="cuotasBody"></tbody></table><small class="text-muted">Las cuotas se generarán automáticamente</small></div>
                        </div>
                        <div class="row"><div class="col-md-6 mb-3"><div class="form-check"><input type="checkbox" name="detraccion" id="detraccion" class="form-check-input" value="1"><label class="form-check-label fw-bold">Detracción</label></div></div></div>
                        <div class="mb-3"><label class="form-label fw-bold">Observaciones (opcional)</label><textarea name="observaciones" id="observaciones" class="form-control" rows="2"></textarea></div>
                        <input type="hidden" id="productos_json" name="productos_json">
                        <input type="hidden" id="subtotal_hidden" name="subtotal">
                        <input type="hidden" id="igv_hidden" name="igv">
                        <input type="hidden" id="total_hidden" name="total">
                    </form>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="button" class="btn btn-primary" id="btnConfirmarPago"><i class="bi bi-check-circle"></i> Confirmar Pago</button><button type="button" class="btn btn-primary" id="btnLoadingPago" style="display: none;" disabled><span class="spinner-border spinner-border-sm me-2"></span> Procesando...</button></div>
            </div>
        </div>
    </div>

    <!-- Modal Nuevo Cliente -->
    <div class="modal fade" id="modalNuevoCliente" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title"><i class="bi bi-person-plus"></i> Nuevo Cliente</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <form id="formNuevoCliente">@csrf
                    <div class="modal-body">
                        <div class="mb-3"><label class="form-label fw-bold">Tipo Documento</label><select name="tipo_documento" class="form-control" required><option value="DNI">DNI</option><option value="RUC">RUC</option></select></div>
                        <div class="mb-3"><label class="form-label fw-bold">Número Documento</label><input type="text" name="numero_documento" class="form-control" required></div>
                        <div class="mb-3"><label class="form-label fw-bold">Nombre/Razón Social</label><input type="text" name="nombre_razon_social" class="form-control" required></div>
                        <div class="mb-3"><label class="form-label fw-bold">Dirección</label><textarea name="direccion" class="form-control" rows="2"></textarea></div>
                        <div class="mb-3"><label class="form-label fw-bold">Teléfono</label><input type="text" name="telefono" class="form-control"></div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Guardar Cliente</button></div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Resultado Venta -->
    <div class="modal fade" id="modalResultado" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title text-white"><i class="bi bi-check-circle"></i> Venta Completada</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <i class="bi bi-receipt" style="font-size: 64px; color: #28a745;"></i>
                        <h4 class="mt-2">¡Venta registrada exitosamente!</h4>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header bg-light"><strong><i class="bi bi-info-circle"></i> Información de la Venta</strong></div>
                                <div class="card-body">
                                    <div class="detalle-linea"><span class="detalle-label">Documento:</span><span class="detalle-valor" id="resultado_documento">-</span></div>
                                    <div class="detalle-linea"><span class="detalle-label">Fecha/Hora:</span><span class="detalle-valor" id="resultado_fecha">-</span></div>
                                    <div class="detalle-linea"><span class="detalle-label">Vendedor:</span><span class="detalle-valor" id="resultado_vendedor">-</span></div>
                                    <div class="detalle-linea"><span class="detalle-label">Tipo de Venta:</span><span class="detalle-valor" id="resultado_tipo_venta">-</span></div>
                                    <div class="detalle-linea"><span class="detalle-label">Forma de Pago:</span><span class="detalle-valor" id="resultado_forma_pago">-</span></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header bg-light"><strong><i class="bi bi-person"></i> Información del Cliente</strong></div>
                                <div class="card-body">
                                    <div class="detalle-linea"><span class="detalle-label">Nombre:</span><span class="detalle-valor" id="resultado_cliente_nombre">-</span></div>
                                    <div class="detalle-linea"><span class="detalle-label">Documento:</span><span class="detalle-valor" id="resultado_cliente_documento">-</span></div>
                                    <div class="detalle-linea"><span class="detalle-label">Dirección:</span><span class="detalle-valor" id="resultado_cliente_direccion">-</span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card mb-3">
                        <div class="card-header bg-light"><strong><i class="bi bi-calculator"></i> Totales</strong></div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4"><div class="detalle-linea"><span class="detalle-label">Subtotal:</span><span class="detalle-valor" id="resultado_subtotal">-</span></div></div>
                                <div class="col-md-4"><div class="detalle-linea"><span class="detalle-label">IGV (18%):</span><span class="detalle-valor" id="resultado_igv">-</span></div></div>
                                <div class="col-md-4"><div class="detalle-linea"><span class="detalle-label">Total:</span><span class="detalle-valor" id="resultado_total">-</span></div></div>
                            </div>
                        </div>
                    </div>
                    <div class="card mb-3">
                        <div class="card-header bg-light"><strong><i class="bi bi-cash"></i> Pago</strong></div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4"><div class="detalle-linea"><span class="detalle-label">Pagado:</span><span class="detalle-valor" id="resultado_pagado">-</span></div></div>
                                <div class="col-md-4"><div class="detalle-linea"><span class="detalle-label">Cambio:</span><span class="detalle-valor" id="resultado_cambio">-</span></div></div>
                                <div class="col-md-4"><div class="detalle-linea"><span class="detalle-label">Detracción:</span><span class="detalle-valor" id="resultado_detraccion">-</span></div></div>
                            </div>
                        </div>
                    </div>
                    <div id="resultado_cuotas_container" style="display: none;" class="card mb-3">
                        <div class="card-header bg-light"><strong><i class="bi bi-calendar"></i> Cuotas Generadas</strong></div>
                        <div class="card-body"><div id="cuotas_lista"></div></div>
                    </div>
                    <div class="alert alert-info text-center">
                        <i class="bi bi-info-circle"></i> Los comprobantes han sido generados correctamente.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="bi bi-x-circle"></i> Cerrar</button>
                    <button type="button" class="btn btn-warning" id="btnImprimirTicket"><i class="bi bi-receipt"></i> Imprimir Ticket</button>
                    <button type="button" class="btn btn-danger" id="btnDescargarPDF"><i class="bi bi-file-pdf"></i> Descargar PDF A4</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let cart = [];
        let ultimaVentaId = null;

        // ========== SISTEMA DE SONIDOS ==========
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

        function updateCartUI() {
            const cartContainer = $('#cartItems');
            const subtotalSpan = $('#subtotal');
            const igvSpan = $('#igv');
            const totalSpan = $('#total');
            if (cart.length === 0) { cartContainer.html(`<div class="empty-cart"><i class="bi bi-cart4" style="font-size: 48px;"></i><p>Agregue productos al carrito</p></div>`); subtotalSpan.text('S/ 0.00'); igvSpan.text('S/ 0.00'); totalSpan.text('S/ 0.00'); return; }
            let html = ''; let subtotal = 0;
            cart.forEach((item, index) => { const itemTotal = item.precio * item.cantidad; subtotal += itemTotal; html += `<div class="cart-item" data-index="${index}"><div class="cart-item-info"><div class="cart-item-name">${item.nombre}</div><div class="cart-item-price">S/ ${item.precio.toFixed(2)}</div></div><div class="cart-item-qty"><button class="qty-minus" data-index="${index}">-</button><span>${item.cantidad}</span><button class="qty-plus" data-index="${index}">+</button></div><div class="cart-item-total">S/ ${itemTotal.toFixed(2)}</div><div class="cart-item-remove" data-index="${index}"><i class="bi bi-trash3"></i></div></div>`; });
            cartContainer.html(html);
            const igv = subtotal * 0.18; const total = subtotal + igv;
            subtotalSpan.text(`S/ ${subtotal.toFixed(2)}`); igvSpan.text(`S/ ${igv.toFixed(2)}`); totalSpan.text(`S/ ${total.toFixed(2)}`);
            $('.qty-minus').off('click').on('click', function() { const index = $(this).data('index'); if (cart[index].cantidad > 1) { cart[index].cantidad--; updateCartUI(); playRemoveBeep(); } else { cart.splice(index, 1); updateCartUI(); playRemoveBeep(); } });
            $('.qty-plus').off('click').on('click', function() { const index = $(this).data('index'); cart[index].cantidad++; updateCartUI(); playAddBeep(); });
            $('.cart-item-remove').off('click').on('click', function() { const index = $(this).data('index'); cart.splice(index, 1); updateCartUI(); playRemoveBeep(); });
        }

        function addToCart(producto) { const existing = cart.find(item => item.id === producto.id); if (existing) { existing.cantidad++; } else { cart.push({ id: producto.id, nombre: producto.nombre, precio: producto.precio, cantidad: 1, almacen_id: producto.almacen_id }); } updateCartUI(); playAddBeep(); }

        $('.product-card').click(function() { const $card = $(this); const stock = parseInt($card.data('stock')); const producto = { id: $card.data('id'), nombre: $card.data('nombre'), precio: parseFloat($card.data('precio')), almacen_id: 1 }; if (stock <= 0) { playErrorBeep(); alert('Producto sin stock disponible'); return; } addToCart(producto); });

        let searchTimeout;
        $('#searchInput').on('keyup', function() { clearTimeout(searchTimeout); const search = $(this).val(); searchTimeout = setTimeout(function() { if (search.length > 0) { $.ajax({ url: '/terminal/search', type: 'GET', data: { search: search }, success: function(response) { if (response.success) { let html = ''; response.data.forEach(producto => { const stockClass = producto.stock_total <= 0 ? 'disabled' : ''; html += `<div class="product-card ${stockClass}" data-id="${producto.id}" data-nombre="${producto.descripcion}" data-precio="${producto.precio_venta}" data-foto="${producto.foto}" data-stock="${producto.stock_total}"><div class="product-img"><img src="${producto.foto}" alt="${producto.descripcion}" class="product-image" onerror="this.src='{{ URL::asset('build/images/default-product.png') }}'"></div><div class="product-name">${producto.descripcion}</div><div class="product-price">S/ ${producto.precio_venta.toFixed(2)}</div><div class="product-stock">Stock: ${producto.stock_total}</div></div>`; }); $('#productsGrid').html(html); $('.product-card').off('click').on('click', function() { const $card = $(this); if ($card.hasClass('disabled')) return; const stock = parseInt($card.data('stock')); if (stock <= 0) { playErrorBeep(); alert('Producto sin stock disponible'); return; } addToCart({ id: $card.data('id'), nombre: $card.data('nombre'), precio: parseFloat($card.data('precio')), almacen_id: 1 }); }); } } }); } else { location.reload(); } }, 300); });

        // Cancelar venta con modal
        $('#btnCancelar').click(function() { if (cart.length === 0) { playErrorBeep(); alert('No hay productos en el carrito'); return; } $('#modalConfirmarCancelar').modal('show'); });
        $('#btnConfirmarCancelar').click(function() { cart = []; updateCartUI(); playCancelBeep(); $('#modalConfirmarCancelar').modal('hide'); });

        $('#btnPagar').click(function() { if (cart.length === 0) { playErrorBeep(); alert('No hay productos en el carrito'); return; } const total = parseFloat($('#total').text().replace('S/ ', '')); $('#total_pagar').val(total.toFixed(2)); $('#total_hidden').val(total); $('#subtotal_hidden').val(parseFloat($('#subtotal').text().replace('S/ ', ''))); $('#igv_hidden').val(parseFloat($('#igv').text().replace('S/ ', ''))); $('#pagado').val(total.toFixed(2)); $('#diferencia').val('0.00'); $('#seccionContado').show(); $('#seccionCredito').hide(); $('#tipo_venta').val('CONTADO'); cargarSerie(); $('#modalPago').modal('show'); });

        function cargarSerie() { $.ajax({ url: '/terminal/series', type: 'GET', data: { tipo: $('#tipo_comprobante').val() }, success: function(response) { if (response.success) { $('#serie').val(response.serie); $('#numero').val(response.numero); $('#serie_documento').val(response.documento); } else { $('#serie').val(''); $('#numero').val(''); $('#serie_documento').val(response.message || 'Sin serie configurada'); } } }); }
        $('#tipo_comprobante').change(function() { cargarSerie(); });
        $('#tipo_venta').change(function() { const total = parseFloat($('#total_pagar').val()); if ($(this).val() === 'CREDITO') { $('#seccionContado').hide(); $('#seccionCredito').show(); $('#total_credito').val(total.toFixed(2)); $('#total_a_credito').val(total.toFixed(2)); generarCuotas(); } else { $('#seccionContado').show(); $('#seccionCredito').hide(); $('#pagado').val(total.toFixed(2)); calcularDiferencia(); } });
        function calcularDiferencia() { const total = parseFloat($('#total_pagar').val()); const pagado = parseFloat($('#pagado').val()) || 0; $('#diferencia').val((pagado - total).toFixed(2)); }
        $('#pagado').on('keyup change', calcularDiferencia);
        function generarCuotas() { const total = parseFloat($('#total_a_credito').val()); const cuotas = parseInt($('#numero_cuotas').val()); const montoCuota = total / cuotas; let html = ''; for (let i = 1; i <= cuotas; i++) { const fecha = new Date(); fecha.setMonth(fecha.getMonth() + i); html += `<tr><td>Cuota ${i}</td><td>${fecha.toISOString().split('T')[0]}</td><td>S/ ${montoCuota.toFixed(2)}</td><td><span class="badge bg-warning">Pendiente</span></td></tr>`; } $('#cuotasBody').html(html); }
        $('#numero_cuotas').change(generarCuotas);

        let clienteTimeout;
        $('#searchCliente').on('keyup', function() { clearTimeout(clienteTimeout); const search = $(this).val(); if (search.length < 2) { $('#clienteResults').hide(); return; } clienteTimeout = setTimeout(function() { $.ajax({ url: '/terminal/search-clientes', type: 'GET', data: { q: search }, success: function(response) { if (response.success && response.clientes.length > 0) { let html = ''; response.clientes.forEach(cliente => { html += `<div class="cliente-result-item" data-id="${cliente.id}" data-nombre="${cliente.nombre_razon_social}" data-documento="${cliente.numero_documento}"><strong>${cliente.numero_documento}</strong> - ${cliente.nombre_razon_social}</div>`; }); $('#clienteResults').html(html).show(); $('.cliente-result-item').off('click').on('click', function() { $('#cliente_id').val($(this).data('id')); $('#searchCliente').val($(this).data('nombre')); $('#clienteSeleccionado').html(`<strong>${$(this).data('documento')}</strong> - ${$(this).data('nombre')}`); $('#clienteResults').hide(); }); } else { $('#clienteResults').html('<div class="p-3 text-center text-muted">No se encontraron clientes</div>').show(); } } }); }, 300); });
        $(document).on('click', function(e) { if (!$(e.target).closest('#searchCliente, #clienteResults').length) $('#clienteResults').hide(); });
        $('#btnNuevoCliente').click(() => $('#modalNuevoCliente').modal('show'));
        $('#formNuevoCliente').submit(function(e) { e.preventDefault(); $.ajax({ url: '/api/clientes', type: 'POST', data: $(this).serialize(), success: function(response) { if (response.success) { $('#modalNuevoCliente').modal('hide'); playSuccessBeep(); alert('Cliente registrado exitosamente'); } } }); });

        $('#btnConfirmarPago').click(function() {
            if (cart.length === 0) { playErrorBeep(); alert('No hay productos en el carrito'); return; }
            const productos = cart.map(item => ({ id: item.id, cantidad: item.cantidad, precio: item.precio, almacen_id: item.almacen_id || 1 }));
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
            productos.forEach((producto, index) => { formData.append(`productos[${index}][id]`, producto.id); formData.append(`productos[${index}][cantidad]`, producto.cantidad); formData.append(`productos[${index}][precio]`, producto.precio); formData.append(`productos[${index}][almacen_id]`, producto.almacen_id); });
            $('#btnConfirmarPago').hide(); $('#btnLoadingPago').show();
            $.ajax({ url: '/terminal/procesar-pago', type: 'POST', data: formData, processData: false, contentType: false, headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }, success: function(response) { if (response.success) {
                $('#modalPago').modal('hide');
                ultimaVentaId = response.data.venta_id;
                $('#resultado_documento').text(response.data.documento);
                $('#resultado_fecha').text(new Date().toLocaleString());
                $('#resultado_vendedor').text('{{ Auth::user()->name ?? "ADMINISTRADOR" }}');
                $('#resultado_tipo_venta').text($('#tipo_venta').val() === 'CONTADO' ? 'CONTADO' : 'CRÉDITO');
                $('#resultado_forma_pago').text($('#forma_pago').val());
                $('#resultado_cliente_nombre').text($('#searchCliente').val() || 'CLIENTES VARIOS');
                $('#resultado_cliente_documento').text($('#cliente_id').val() || '00000000');
                $('#resultado_cliente_direccion').text('-');
                const subtotal = parseFloat(response.data.subtotal) || parseFloat($('#subtotal_hidden').val()) || 0;
                const igv = parseFloat(response.data.igv) || parseFloat($('#igv_hidden').val()) || 0;
                const total = parseFloat(response.data.total) || 0;
                const pagado = parseFloat(response.data.pagado) || 0;
                const cambio = parseFloat(response.data.cambio) || 0;
                $('#resultado_subtotal').text('S/ ' + subtotal.toFixed(2));
                $('#resultado_igv').text('S/ ' + igv.toFixed(2));
                $('#resultado_total').text('S/ ' + total.toFixed(2));
                $('#resultado_pagado').text('S/ ' + pagado.toFixed(2));
                $('#resultado_cambio').text('S/ ' + cambio.toFixed(2));
                $('#resultado_detraccion').text($('#detraccion').is(':checked') ? 'Sí' : 'No');
                if (response.data.tipo_venta === 'CREDITO' && response.data.cuotas && response.data.cuotas.length > 0) { $('#resultado_cuotas_container').show(); let cuotasHtml = '<ul class="list-group">'; response.data.cuotas.forEach(cuota => { const montoCuota = parseFloat(cuota.monto) || 0; cuotasHtml += `<li class="list-group-item d-flex justify-content-between align-items-center">Cuota ${cuota.numero_cuota}<span>Vence: ${cuota.fecha_vencimiento}</span><span class="badge bg-primary rounded-pill">S/ ${montoCuota.toFixed(2)}</span></li>`; }); cuotasHtml += '</ul>'; $('#cuotas_lista').html(cuotasHtml); } else { $('#resultado_cuotas_container').hide(); }
                $('#modalResultado').modal('show'); cart = []; updateCartUI(); playSuccessBeep();
            } else { playErrorBeep(); alert(response.message); } }, error: function(xhr) { playErrorBeep(); alert(xhr.responseJSON?.message || 'Error al procesar el pago'); }, complete: function() { $('#btnConfirmarPago').show(); $('#btnLoadingPago').hide(); } });
        });

        $('#btnImprimirTicket').click(function() { if (ultimaVentaId) { window.open('/ventas/' + ultimaVentaId + '/ticket', '_blank', 'width=400,height=600'); } else { alert('No hay una venta reciente para imprimir'); } });
        $('#btnDescargarPDF').click(function() { if (ultimaVentaId) { window.open('/ventas/' + ultimaVentaId + '/pdf', '_blank'); } else { alert('No hay una venta reciente para descargar'); } });
    </script>
</body>
</html>