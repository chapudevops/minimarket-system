<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Terminal POS - Minimarket</title>
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
        
        /* Panel izquierdo - Productos */
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
        
        .product-card.disabled:hover {
            transform: none;
            box-shadow: none;
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
        
        /* Panel derecho - Carrito */
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
        
        /* Scrollbar */
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
                <div class="product-card" data-id="{{ $producto->id }}" data-nombre="{{ $producto->descripcion }}" data-precio="{{ $producto->precio_venta }}" data-stock="{{ $producto->stock }}">
                    <div class="product-name">{{ $producto->descripcion }}</div>
                    <div class="product-price">S/ {{ number_format($producto->precio_venta, 2) }}</div>
                    <div class="product-stock">Stock: {{ $producto->stock }}</div>
                </div>
                @endforeach
            </div>
        </div>
        
        <!-- Panel derecho - Carrito -->
        <div class="cart-panel">
            <div class="cart-header">
                <h4><i class="bi bi-cart3"></i> Carrito de Ventas</h4>
                <div class="company-info">
                    DISTRIBUIDORA BEJAR E.I.R.L.<br>
                    R.U.C. 20100066603
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

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        let cart = [];
        
        // Actualizar carrito en UI
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
            
            // Eventos de los botones del carrito
            $('.qty-minus').click(function() {
                const index = $(this).data('index');
                if (cart[index].cantidad > 1) {
                    cart[index].cantidad--;
                } else {
                    cart.splice(index, 1);
                }
                updateCartUI();
            });
            
            $('.qty-plus').click(function() {
                const index = $(this).data('index');
                cart[index].cantidad++;
                updateCartUI();
            });
            
            $('.cart-item-remove').click(function() {
                const index = $(this).data('index');
                cart.splice(index, 1);
                updateCartUI();
            });
        }
        
        // Agregar producto al carrito
        function addToCart(producto) {
            const existing = cart.find(item => item.id === producto.id);
            if (existing) {
                existing.cantidad++;
            } else {
                cart.push({
                    id: producto.id,
                    nombre: producto.nombre,
                    precio: producto.precio,
                    cantidad: 1
                });
            }
            updateCartUI();
        }
        
        // Eventos de productos
        $('.product-card').click(function() {
            const $card = $(this);
            const stock = parseInt($card.data('stock'));
            const producto = {
                id: $card.data('id'),
                nombre: $card.data('nombre'),
                precio: parseFloat($card.data('precio'))
            };
            
            if (stock <= 0) {
                alert('Producto sin stock disponible');
                return;
            }
            
            addToCart(producto);
        });
        
        // Búsqueda en tiempo real
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
                                    const stockClass = producto.stock <= 0 ? 'disabled' : '';
                                    html += `
                                        <div class="product-card ${stockClass}" data-id="${producto.id}" data-nombre="${producto.descripcion}" data-precio="${producto.precio_venta}" data-stock="${producto.stock}">
                                            <div class="product-name">${producto.descripcion}</div>
                                            <div class="product-price">S/ ${producto.precio_venta.toFixed(2)}</div>
                                            <div class="product-stock">Stock: ${producto.stock}</div>
                                        </div>
                                    `;
                                });
                                $('#productsGrid').html(html);
                                
                                // Reasignar eventos
                                $('.product-card').click(function() {
                                    const $card = $(this);
                                    if ($card.hasClass('disabled')) return;
                                    const producto = {
                                        id: $card.data('id'),
                                        nombre: $card.data('nombre'),
                                        precio: parseFloat($card.data('precio'))
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
            if (confirm('¿Estás seguro de cancelar la venta? Se eliminarán todos los productos del carrito.')) {
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
            
            const subtotal = parseFloat($('#subtotal').text().replace('S/ ', ''));
            const igv = parseFloat($('#igv').text().replace('S/ ', ''));
            const total = parseFloat($('#total').text().replace('S/ ', ''));
            
            alert(`💰 Total a pagar: S/ ${total.toFixed(2)}\n\nIGV: S/ ${igv.toFixed(2)}\nSubtotal: S/ ${subtotal.toFixed(2)}\n\nEsta función procesará el pago.`);
            // Aquí puedes agregar la lógica para procesar el pago
        });
    </script>
</body>
</html>