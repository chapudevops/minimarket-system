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
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: #f0f2f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            height: 100vh;
            overflow: hidden;
        }
        
        .pos-container {
            display: flex;
            height: 100vh;
        }
        
        .products-panel {
            flex: 2;
            padding: 20px;
            overflow-y: auto;
            background: #f8f9fa;
        }
        
        .search-box {
            margin-bottom: 20px;
        }
        
        .search-box input {
            width: 100%;
            padding: 12px 20px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .search-box input:focus {
            outline: none;
            border-color: #28a745;
            box-shadow: 0 0 0 3px rgba(40,167,69,0.1);
        }
        
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .product-card {
            background: white;
            border-radius: 12px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s;
            border: 1px solid #e9ecef;
            text-align: center;
        }
        
        .product-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-color: #28a745;
        }
        
        .product-card.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            background: #f8f9fa;
        }
        
        .product-name {
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 8px;
            color: #2c3e50;
        }
        
        .product-price {
            font-size: 18px;
            font-weight: bold;
            color: #28a745;
        }
        
        .product-stock {
            font-size: 11px;
            color: #6c757d;
            margin-top: 5px;
        }
        
        .cart-panel {
            flex: 1;
            background: white;
            border-left: 1px solid #e9ecef;
            display: flex;
            flex-direction: column;
            box-shadow: -2px 0 10px rgba(0,0,0,0.05);
        }
        
        .cart-header {
            padding: 20px;
            background: #2c3e50;
            color: white;
        }
        
        .cart-header h4 {
            margin: 0;
            font-size: 18px;
        }
        
        .company-info {
            font-size: 12px;
            color: #ecf0f1;
            margin-top: 5px;
        }
        
        .cart-items {
            flex: 1;
            overflow-y: auto;
            padding: 15px;
        }
        
        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid #e9ecef;
            margin-bottom: 10px;
        }
        
        .cart-item-info {
            flex: 2;
        }
        
        .cart-item-name {
            font-weight: 600;
            font-size: 14px;
        }
        
        .cart-item-price {
            font-size: 12px;
            color: #6c757d;
        }
        
        .cart-item-qty {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .cart-item-qty button {
            width: 25px;
            height: 25px;
            border: none;
            border-radius: 5px;
            background: #f8f9fa;
            font-weight: bold;
        }
        
        .cart-item-qty span {
            width: 30px;
            text-align: center;
        }
        
        .cart-item-total {
            font-weight: bold;
            color: #28a745;
            min-width: 70px;
            text-align: right;
        }
        
        .cart-item-remove {
            color: #dc3545;
            cursor: pointer;
            margin-left: 10px;
        }
        
        .cart-summary {
            padding: 20px;
            border-top: 2px solid #e9ecef;
            background: #f8f9fa;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .summary-total {
            font-size: 20px;
            font-weight: bold;
            color: #28a745;
            border-top: 1px solid #dee2e6;
            padding-top: 10px;
            margin-top: 10px;
        }
        
        .cart-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .cart-buttons button {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-weight: bold;
        }
        
        .btn-cancel {
            background: #6c757d;
            color: white;
        }
        
        .btn-pay {
            background: #28a745;
            color: white;
        }
        
        .empty-cart {
            text-align: center;
            color: #6c757d;
            padding: 40px;
        }
        
        /* Modal Pago Styles */
        .modal-pago {
            max-width: 800px;
        }
        
        .cuotas-table {
            margin-top: 15px;
        }
        
        .cuotas-table th, .cuotas-table td {
            font-size: 12px;
            padding: 8px;
        }
        
        .cliente-search-result {
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            display: none;
            position: absolute;
            background: white;
            z-index: 1000;
            width: 100%;
        }
        
        .cliente-result-item {
            padding: 10px;
            border-bottom: 1px solid #dee2e6;
            cursor: pointer;
        }
        
        .cliente-result-item:hover {
            background: #f8f9fa;
        }
        
        .position-relative {
            position: relative;
        }
        
        ::-webkit-scrollbar {
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }
        
        @media (max-width: 768px) {
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }
        }
    </style>
</head>
<body>
    <div class="pos-container">
        <!-- Panel izquierdo - Productos -->
        <div class="products-panel">
            <div class="search-box">
                <input type="text" id="searchInput" class="form-control" placeholder="🔍 Buscar por nombre, código o código de barras..." autofocus>
            </div>
            <div class="products-grid" id="productsGrid">
                @foreach($productos as $producto)
                <div class="product-card" data-id="{{ $producto->id }}" data-nombre="{{ $producto->descripcion }}" data-precio="{{ $producto->precio_venta }}">
                    <div class="product-name">{{ $producto->descripcion }}</div>
                    <div class="product-price">S/ {{ number_format($producto->precio_venta, 2) }}</div>
                    <div class="product-stock">Stock: {{ $producto->stock_total }}</div>
                </div>
                @endforeach
            </div>
        </div>
        
        <!-- Panel derecho - Carrito -->
        <div class="cart-panel">
            <div class="cart-header">
                <h4><i class="bi bi-cart3"></i> Carrito de Ventas</h4>
                <div class="company-info">
                    {{ $empresa->razon_social ?? 'DISTRIBUIDORA BEJAR E.I.R.L.' }}<br>
                    R.U.C. {{ $empresa->ruc ?? '20100066603' }}
                </div>
            </div>
            <div class="cart-items" id="cartItems">
                <div class="empty-cart">
                    <i class="bi bi-cart4" style="font-size: 48px;"></i>
                    <p>Agregue productos al carrito</p>
                </div>
            </div>
            <div class="cart-summary">
                <div class="summary-row">
                    <span>OP. Gravadas</span>
                    <span id="subtotal">S/ 0.00</span>
                </div>
                <div class="summary-row">
                    <span>IGV (18%)</span>
                    <span id="igv">S/ 0.00</span>
                </div>
                <div class="summary-row summary-total">
                    <span>Total</span>
                    <span id="total">S/ 0.00</span>
                </div>
                <div class="cart-buttons">
                    <button class="btn-cancel" id="btnCancelar"><i class="bi bi-x-circle"></i> Cancelar venta</button>
                    <button class="btn-pay" id="btnPagar"><i class="bi bi-credit-card"></i> Procesar Pago</button>
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
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-bold">Documento</label>
                                <select name="tipo_comprobante" id="tipo_comprobante" class="form-control" required>
                                    <option value="BOLETA">BOLETA</option>
                                    <option value="FACTURA">FACTURA</option>
                                    <option value="NOTA">NOTA</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-bold">Serie</label>
                                <input type="text" id="serie" name="serie" class="form-control" readonly placeholder="Ej: F001">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-bold">Número</label>
                                <input type="text" id="numero" name="numero" class="form-control" readonly placeholder="Ej: 1">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-bold">Documento</label>
                                <input type="text" id="serie_documento" class="form-control" readonly placeholder="F001-00000001">
                            </div>
                            <div class="col-md-12 mb-3 position-relative">
                                <label class="form-label fw-bold">Cliente <button type="button" class="btn btn-sm btn-primary" id="btnNuevoCliente"><i class="bi bi-plus-circle"></i> Nuevo</button></label>
                                <input type="text" id="searchCliente" class="form-control" placeholder="Buscar por nombre o documento...">
                                <div id="clienteResults" class="cliente-search-result"></div>
                                <input type="hidden" id="cliente_id" name="cliente_id">
                                <div id="clienteSeleccionado" class="mt-2 small text-muted"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Tipo de Venta</label>
                                <select name="tipo_venta" id="tipo_venta" class="form-control" required>
                                    <option value="CONTADO">Contado</option>
                                    <option value="CREDITO">Crédito</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Forma de Pago</label>
                                <select name="forma_pago" id="forma_pago" class="form-control" required>
                                    <option value="EFECTIVO">Efectivo</option>
                                    <option value="YAPE">Yape</option>
                                    <option value="TRANSFERENCIA">Transferencia</option>
                                    <option value="TARJETA">Tarjeta</option>
                                </select>
                            </div>
                        </div>

                        <!-- Sección Contado -->
                        <div id="seccionContado">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Total a Pagar</label>
                                    <div class="input-group">
                                        <span class="input-group-text">S/</span>
                                        <input type="text" id="total_pagar" class="form-control" readonly>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Pagando</label>
                                    <div class="input-group">
                                        <span class="input-group-text">S/</span>
                                        <input type="number" step="0.01" name="pagado" id="pagado" class="form-control" value="0.00" required>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Diferencia</label>
                                    <div class="input-group">
                                        <span class="input-group-text">S/</span>
                                        <input type="text" id="diferencia" class="form-control" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sección Crédito -->
                        <div id="seccionCredito" style="display: none;">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Condiciones de Crédito</label>
                                    <div class="input-group">
                                        <span class="input-group-text">S/</span>
                                        <input type="text" id="total_credito" class="form-control" readonly>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Número de Cuotas</label>
                                    <select name="numero_cuotas" id="numero_cuotas" class="form-control">
                                        <option value="1">1 Cuota</option>
                                        <option value="2">2 Cuotas</option>
                                        <option value="3">3 Cuotas</option>
                                        <option value="4">4 Cuotas</option>
                                        <option value="5">5 Cuotas</option>
                                        <option value="6">6 Cuotas</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Total a Crédito</label>
                                    <div class="input-group">
                                        <span class="input-group-text">S/</span>
                                        <input type="text" id="total_a_credito" class="form-control" readonly>
                                    </div>
                                </div>
                            </div>
                            <div class="table-responsive cuotas-table">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Cuota</th>
                                            <th>Fecha de Vencimiento</th>
                                            <th>Monto</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody id="cuotasBody">
                                    </tbody>
                                </table>
                                <small class="text-muted">Las cuotas se generarán automáticamente</small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="form-check">
                                    <input type="checkbox" name="detraccion" id="detraccion" class="form-check-input" value="1">
                                    <label class="form-check-label fw-bold">Detracción</label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Observaciones (opcional)</label>
                            <textarea name="observaciones" id="observaciones" class="form-control" rows="2"></textarea>
                        </div>

                        <input type="hidden" id="productos_json" name="productos_json">
                        <input type="hidden" id="subtotal_hidden" name="subtotal">
                        <input type="hidden" id="igv_hidden" name="igv">
                        <input type="hidden" id="total_hidden" name="total">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btnConfirmarPago">
                        <i class="bi bi-check-circle"></i> Confirmar Pago
                    </button>
                    <button type="button" class="btn btn-primary" id="btnLoadingPago" style="display: none;" disabled>
                        <span class="spinner-border spinner-border-sm me-2"></span> Procesando...
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Nuevo Cliente -->
    <div class="modal fade" id="modalNuevoCliente" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-person-plus"></i> Nuevo Cliente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formNuevoCliente">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Tipo Documento</label>
                            <select name="tipo_documento" class="form-control" required>
                                <option value="DNI">DNI</option>
                                <option value="RUC">RUC</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Número Documento</label>
                            <input type="text" name="numero_documento" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nombre/Razón Social</label>
                            <input type="text" name="nombre_razon_social" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Dirección</label>
                            <textarea name="direccion" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Teléfono</label>
                            <input type="text" name="telefono" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar Cliente</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Resultado Venta -->
    <div class="modal fade" id="modalResultado" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="bi bi-check-circle"></i> Venta Completada</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <i class="bi bi-receipt" style="font-size: 64px; color: #28a745;"></i>
                    <h4 class="mt-3">¡Venta registrada exitosamente!</h4>
                    <p class="mb-1"><strong>Documento:</strong> <span id="resultado_documento"></span></p>
                    <p class="mb-1"><strong>Total:</strong> S/ <span id="resultado_total"></span></p>
                    <p class="mb-1"><strong>Pagado:</strong> S/ <span id="resultado_pagado"></span></p>
                    <p class="mb-3"><strong>Cambio:</strong> S/ <span id="resultado_cambio"></span></p>
                    <div id="resultado_cuotas" style="display: none;">
                        <hr>
                        <h6>Cuotas generadas:</h6>
                        <div id="cuotas_lista"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-secondary" id="btnImprimirTicket">Imprimir Ticket</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let cart = [];
        let productosConAlmacen = [];

        // Obtener almacenes del usuario
        function cargarAlmacenes() {
            return new Promise((resolve) => {
                $.ajax({
                    url: '/api/usuario/almacenes',
                    type: 'GET',
                    success: function(response) {
                        resolve(response.almacenes);
                    }
                });
            });
        }

        // Actualizar carrito
        function updateCartUI() {
            const cartContainer = $('#cartItems');
            const subtotalSpan = $('#subtotal');
            const igvSpan = $('#igv');
            const totalSpan = $('#total');
            
            if (cart.length === 0) {
                cartContainer.html(`
                    <div class="empty-cart">
                        <i class="bi bi-cart4" style="font-size: 48px;"></i>
                        <p>Agregue productos al carrito</p>
                    </div>
                `);
                subtotalSpan.text('S/ 0.00');
                igvSpan.text('S/ 0.00');
                totalSpan.text('S/ 0.00');
                return;
            }
            
            let html = '';
            let subtotal = 0;
            
            cart.forEach((item, index) => {
                const itemTotal = item.precio * item.cantidad;
                subtotal += itemTotal;
                html += `
                    <div class="cart-item" data-index="${index}">
                        <div class="cart-item-info">
                            <div class="cart-item-name">${item.nombre}</div>
                            <div class="cart-item-price">S/ ${item.precio.toFixed(2)}</div>
                        </div>
                        <div class="cart-item-qty">
                            <button class="qty-minus" data-index="${index}">-</button>
                            <span>${item.cantidad}</span>
                            <button class="qty-plus" data-index="${index}">+</button>
                        </div>
                        <div class="cart-item-total">S/ ${itemTotal.toFixed(2)}</div>
                        <div class="cart-item-remove" data-index="${index}">
                            <i class="bi bi-trash3"></i>
                        </div>
                    </div>
                `;
            });
            
            cartContainer.html(html);
            
            const igv = subtotal * 0.18;
            const total = subtotal + igv;
            
            subtotalSpan.text(`S/ ${subtotal.toFixed(2)}`);
            igvSpan.text(`S/ ${igv.toFixed(2)}`);
            totalSpan.text(`S/ ${total.toFixed(2)}`);
            
            $('.qty-minus').off('click').on('click', function() {
                const index = $(this).data('index');
                if (cart[index].cantidad > 1) {
                    cart[index].cantidad--;
                } else {
                    cart.splice(index, 1);
                }
                updateCartUI();
            });
            
            $('.qty-plus').off('click').on('click', function() {
                const index = $(this).data('index');
                cart[index].cantidad++;
                updateCartUI();
            });
            
            $('.cart-item-remove').off('click').on('click', function() {
                const index = $(this).data('index');
                cart.splice(index, 1);
                updateCartUI();
            });
        }

        function addToCart(producto) {
            const existing = cart.find(item => item.id === producto.id);
            if (existing) {
                existing.cantidad++;
            } else {
                cart.push({
                    id: producto.id,
                    nombre: producto.nombre,
                    precio: producto.precio,
                    cantidad: 1,
                    almacen_id: producto.almacen_id
                });
            }
            updateCartUI();
        }

        // Eventos productos
        $('.product-card').click(function() {
            const $card = $(this);
            const producto = {
                id: $card.data('id'),
                nombre: $card.data('nombre'),
                precio: parseFloat($card.data('precio')),
                almacen_id: 1
            };
            addToCart(producto);
        });

        // Búsqueda
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
                                    html += `
                                        <div class="product-card" data-id="${producto.id}" data-nombre="${producto.descripcion}" data-precio="${producto.precio_venta}">
                                            <div class="product-name">${producto.descripcion}</div>
                                            <div class="product-price">S/ ${producto.precio_venta.toFixed(2)}</div>
                                            <div class="product-stock">Stock: ${producto.stock_total}</div>
                                        </div>
                                    `;
                                });
                                $('#productsGrid').html(html);
                                
                                $('.product-card').off('click').on('click', function() {
                                    const $card = $(this);
                                    const producto = {
                                        id: $card.data('id'),
                                        nombre: $card.data('nombre'),
                                        precio: parseFloat($card.data('precio')),
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
            if (confirm('¿Estás seguro de cancelar la venta?')) {
                cart = [];
                updateCartUI();
            }
        });

        // Procesar pago
        $('#btnPagar').click(function() {
            if (cart.length === 0) {
                alert('No hay productos en el carrito');
                return;
            }
            
            const total = parseFloat($('#total').text().replace('S/ ', ''));
            $('#total_pagar').val(total.toFixed(2));
            $('#total_hidden').val(total);
            
            const subtotal = parseFloat($('#subtotal').text().replace('S/ ', ''));
            const igv = parseFloat($('#igv').text().replace('S/ ', ''));
            $('#subtotal_hidden').val(subtotal);
            $('#igv_hidden').val(igv);
            
            $('#pagado').val(total.toFixed(2));
            $('#diferencia').val('0.00');
            
            $('#seccionContado').show();
            $('#seccionCredito').hide();
            $('#tipo_venta').val('CONTADO');
            
            cargarSerie();
            $('#modalPago').modal('show');
        });

        function cargarSerie() {
            const tipo = $('#tipo_comprobante').val();
            $.ajax({
                url: '/terminal/series',
                type: 'GET',
                data: { tipo: tipo },
                success: function(response) {
                    if (response.success) {
                        // Separar serie y número en campos distintos
                        $('#serie').val(response.serie);
                        $('#numero').val(response.numero);
                        $('#serie_documento').val(response.documento);
                    } else {
                        $('#serie').val('');
                        $('#numero').val('');
                        $('#serie_documento').val(response.message || 'Sin serie configurada');
                        console.error(response.message);
                    }
                },
                error: function(xhr) {
                    console.error('Error al cargar serie:', xhr);
                    $('#serie').val('');
                    $('#numero').val('');
                    $('#serie_documento').val('Error al cargar serie');
                }
            });
        }

        $('#tipo_comprobante').change(function() {
            cargarSerie();
        });

        $('#tipo_venta').change(function() {
            const total = parseFloat($('#total_pagar').val());
            if ($(this).val() === 'CREDITO') {
                $('#seccionContado').hide();
                $('#seccionCredito').show();
                $('#total_credito').val(total.toFixed(2));
                $('#total_a_credito').val(total.toFixed(2));
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
            const diferencia = pagado - total;
            $('#diferencia').val(diferencia.toFixed(2));
        }

        $('#pagado').on('keyup change', function() {
            calcularDiferencia();
        });

        function generarCuotas() {
            const total = parseFloat($('#total_a_credito').val());
            const cuotas = parseInt($('#numero_cuotas').val());
            const montoCuota = total / cuotas;
            const fechaVencimiento = new Date();
            
            let html = '';
            for (let i = 1; i <= cuotas; i++) {
                fechaVencimiento.setMonth(fechaVencimiento.getMonth() + 1);
                const fechaStr = fechaVencimiento.toISOString().split('T')[0];
                html += `
                    <tr>
                        <td>Cuota ${i}</td>
                        <td>${fechaStr}</td>
                        <td>S/ ${montoCuota.toFixed(2)}</td>
                        <td><span class="badge bg-warning">Pendiente</span></td>
                    </tr>
                `;
            }
            $('#cuotasBody').html(html);
        }

        $('#numero_cuotas').change(function() {
            generarCuotas();
        });

        // Búsqueda de clientes
        let clienteTimeout;
        $('#searchCliente').on('keyup', function() {
            clearTimeout(clienteTimeout);
            const search = $(this).val();
            
            if (search.length < 2) {
                $('#clienteResults').hide();
                return;
            }
            
            clienteTimeout = setTimeout(function() {
                $.ajax({
                    url: '/terminal/search-clientes',
                    type: 'GET',
                    data: { q: search },
                    success: function(response) {
                        if (response.success && response.clientes.length > 0) {
                            let html = '';
                            response.clientes.forEach(cliente => {
                                html += `
                                    <div class="cliente-result-item" data-id="${cliente.id}" data-nombre="${cliente.nombre_razon_social}" data-documento="${cliente.numero_documento}">
                                        <strong>${cliente.numero_documento}</strong> - ${cliente.nombre_razon_social}
                                    </div>
                                `;
                            });
                            $('#clienteResults').html(html).show();
                            
                            $('.cliente-result-item').off('click').on('click', function() {
                                const id = $(this).data('id');
                                const nombre = $(this).data('nombre');
                                const documento = $(this).data('documento');
                                $('#cliente_id').val(id);
                                $('#searchCliente').val(nombre);
                                $('#clienteSeleccionado').html(`<strong>${documento}</strong> - ${nombre}`);
                                $('#clienteResults').hide();
                            });
                        } else {
                            $('#clienteResults').html('<div class="p-3 text-center text-muted">No se encontraron clientes</div>').show();
                        }
                    }
                });
            }, 300);
        });

        $(document).on('click', function(e) {
            if (!$(e.target).closest('#searchCliente, #clienteResults').length) {
                $('#clienteResults').hide();
            }
        });

        $('#btnNuevoCliente').click(function() {
            $('#modalNuevoCliente').modal('show');
        });

        $('#formNuevoCliente').submit(function(e) {
            e.preventDefault();
            $.ajax({
                url: '/api/clientes',
                type: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    if (response.success) {
                        $('#modalNuevoCliente').modal('hide');
                        alert('Cliente registrado exitosamente');
                    }
                }
            });
        });

       // Confirmar pago
$('#btnConfirmarPago').click(function() {
    if (cart.length === 0) {
        alert('No hay productos en el carrito');
        return;
    }
    
    // Construir el array de productos correctamente
    const productos = cart.map(item => ({
        id: item.id,
        cantidad: item.cantidad,
        precio: item.precio,
        almacen_id: item.almacen_id || 1
    }));
    
    console.log('Productos a enviar:', productos); // Debug
    
    // Crear FormData y agregar todos los campos manualmente
    const formData = new FormData();
    
    // Agregar campos del formulario
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
    
    // Agregar productos como JSON string
    formData.append('productos_json', JSON.stringify(productos));
    
    // También agregar productos individualmente para el backend
    productos.forEach((producto, index) => {
        formData.append(`productos[${index}][id]`, producto.id);
        formData.append(`productos[${index}][cantidad]`, producto.cantidad);
        formData.append(`productos[${index}][precio]`, producto.precio);
        formData.append(`productos[${index}][almacen_id]`, producto.almacen_id);
    });
    
    console.log('FormData entries:');
    for (let pair of formData.entries()) {
        console.log(pair[0] + ': ' + pair[1]);
    }
    
    $('#btnConfirmarPago').hide();
    $('#btnLoadingPago').show();
    
    $.ajax({
        url: '/terminal/procesar-pago',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            console.log('Respuesta:', response);
            if (response.success) {
                $('#modalPago').modal('hide');
                $('#resultado_documento').text(response.data.documento);
                $('#resultado_total').text(response.data.total.toFixed(2));
                $('#resultado_pagado').text(response.data.pagado.toFixed(2));
                $('#resultado_cambio').text(response.data.cambio.toFixed(2));
                
                if (response.data.tipo_venta === 'CREDITO' && response.data.cuotas) {
                    $('#resultado_cuotas').show();
                    let cuotasHtml = '<ul>';
                    response.data.cuotas.forEach(cuota => {
                        cuotasHtml += `<li>Cuota ${cuota.numero_cuota}: S/ ${cuota.monto.toFixed(2)} - Vence: ${cuota.fecha_vencimiento}</li>`;
                    });
                    cuotasHtml += '</ul>';
                    $('#cuotas_lista').html(cuotasHtml);
                } else {
                    $('#resultado_cuotas').hide();
                }
                
                $('#modalResultado').modal('show');
                cart = [];
                updateCartUI();
            } else {
                alert(response.message);
            }
        },
        error: function(xhr) {
            console.error('Error:', xhr);
            let errorMsg = xhr.responseJSON?.message || 'Error al procesar el pago';
            alert(errorMsg);
        },
        complete: function() {
            $('#btnConfirmarPago').show();
            $('#btnLoadingPago').hide();
        }
    });
});
        $('#btnImprimirTicket').click(function() {
            alert('Funcionalidad de impresión en desarrollo');
        });
    </script>
</body>
</html>