<!doctype html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!--favicon-->
    <link rel="icon" href="{{ URL::asset('build/images/infinitydevlogo.png') }}" type="image/png">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <title>@yield('title') | Minimarket-system</title>

    @yield('css')

    @include('layouts.head-css')

    <style>
        /* Estilos del Preloader - Esto debe cargar inmediatamente */
        #preloader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 999999;
            transition: opacity 0.5s ease, visibility 0.5s ease;
        }

        .preloader-container {
            text-align: center;
            padding: 2rem;
            animation: fadeInUp 0.5s ease;
        }

        /* Animación del mercado */
        .market-animation {
            position: relative;
            width: 200px;
            height: 150px;
            margin: 0 auto;
        }

        .shopping-cart {
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            animation: cartShake 0.5s ease-in-out infinite;
        }

        .cart-main {
            font-size: 70px;
            color: #28a745;
            filter: drop-shadow(0 4px 6px rgba(0,0,0,0.1));
        }

        .cart-badge {
            position: absolute;
            top: -10px;
            right: -15px;
            font-size: 20px;
            animation: bounce 1s ease infinite;
        }

        .floating-products {
            position: absolute;
            width: 100%;
            height: 100%;
        }

        .product {
            position: absolute;
            font-size: 24px;
            animation: floatToCart 2s ease-in-out infinite;
            opacity: 0;
        }

        .product-1 { top: 10%; left: 10%; animation-delay: 0s; }
        .product-2 { top: 20%; right: 10%; animation-delay: 0.4s; }
        .product-3 { bottom: 30%; left: 15%; animation-delay: 0.8s; }
        .product-4 { top: 40%; right: 15%; animation-delay: 1.2s; }
        .product-5 { bottom: 10%; left: 30%; animation-delay: 1.6s; }
        .product-6 { top: 60%; right: 25%; animation-delay: 2s; }

        /* Puntos de carga */
        .loading-dots span {
            font-size: 24px;
            font-weight: bold;
            color: #28a745;
            animation: dotPulse 1.4s ease-in-out infinite;
            opacity: 0;
            display: inline-block;
        }

        .loading-dots span:nth-child(1) { animation-delay: 0s; }
        .loading-dots span:nth-child(2) { animation-delay: 0.2s; }
        .loading-dots span:nth-child(3) { animation-delay: 0.4s; }

        /* Animaciones */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes cartShake {
            0%, 100% {
                transform: translateX(-50%) rotate(0deg);
            }
            25% {
                transform: translateX(-52%) rotate(-2deg);
            }
            75% {
                transform: translateX(-48%) rotate(2deg);
            }
        }

        @keyframes bounce {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-5px);
            }
        }

        @keyframes floatToCart {
            0% {
                transform: translate(0, 0) rotate(0deg);
                opacity: 1;
            }
            50% {
                transform: translate(20px, -30px) rotate(180deg);
                opacity: 1;
            }
            100% {
                transform: translate(80px, -60px) rotate(360deg);
                opacity: 0;
            }
        }

        @keyframes dotPulse {
            0%, 100% {
                opacity: 0.2;
                transform: scale(0.8);
            }
            50% {
                opacity: 1;
                transform: scale(1.2);
            }
        }

        /* Ocultar todo el contenido inicialmente */
        .topbar, 
        .sidebar, 
        .main-wrapper, 
        .overlay, 
        footer,
        .right-sidebar,
        .cart-offcanvas {
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.5s ease, visibility 0.5s ease;
        }

        /* Cuando termina la carga, mostrar todo */
        body.loaded #preloader {
            opacity: 0;
            visibility: hidden;
        }

        body.loaded .topbar,
        body.loaded .sidebar,
        body.loaded .main-wrapper,
        body.loaded .overlay,
        body.loaded footer,
        body.loaded .right-sidebar,
        body.loaded .cart-offcanvas {
            opacity: 1;
            visibility: visible;
        }

        body.loaded .main-content {
            opacity: 1;
        }

        .main-content {
            opacity: 0;
            transition: opacity 0.5s ease;
        }
    </style>
</head>

<body>

@php
    $empresa = \App\Models\Empresa::first();
@endphp

<!-- Preloader de Minimarket - Esto aparece inmediatamente -->
<div id="preloader">
    <div class="preloader-container">
        <!-- Animación de productos volando al carrito -->
        <div class="market-animation">
            <div class="shopping-cart">
                <i class="bi bi-cart3 cart-main"></i>
                <div class="cart-badge">🛒</div>
            </div>
            <div class="floating-products">
                <div class="product product-1">🥬</div>
                <div class="product product-2">🥫</div>
                <div class="product product-3">🥛</div>
                <div class="product product-4">🍎</div>
                <div class="product product-5">🥖</div>
                <div class="product product-6">🧀</div>
            </div>
        </div>
        
        <!-- Logo del Minimarket -->
        <img src="{{ URL::asset('build/images/infinitydevlogo.png') }}" width="100" class="mt-3" alt="Minimarket">
        <h5 class="mt-2 fw-bold" style="color: #28a745;">Minimarket</h5>
        <p class="small text-muted mb-0">Cargando productos frescos...</p>
        <div class="loading-dots mt-2">
            <span>.</span><span>.</span><span>.</span>
        </div>
    </div>
</div>

@include('layouts.topbar')
@include('layouts.sidebar')

<!--start main wrapper-->
<main class="main-wrapper">
    <div class="main-content">

        @yield('content')

    </div>
</main>
<!--end main wrapper-->

<!--start overlay-->
    <div class="overlay btn-toggle"></div>
<!--end overlay-->

  @include('layouts.footer')

  @include('layouts.cart')

  @include('layouts.right-sidebar')

  @include('layouts.vendor-scripts')

  @yield('scripts')

  <script>
    // Forzar que el preloader se muestre inmediatamente
    document.addEventListener("DOMContentLoaded", function() {
        // Asegurar que el preloader sea visible
        const preloader = document.getElementById('preloader');
        if (preloader) {
            preloader.style.display = 'flex';
        }
    });
    
    // Esperar a que todo el contenido esté completamente cargado
    window.addEventListener("load", function() {
        // Pequeño delay para que se vea la animación completa
        setTimeout(function() {
            document.body.classList.add("loaded");
        }, 1500); // Ajusta este tiempo según necesites
    });
  </script>

  <style>
    /* Asegurar que el footer se mantenga abajo */
    html, body {
        height: 100%;
        margin: 0;
        padding: 0;
    }
    
    body {
        display: flex;
        flex-direction: column;
        min-height: 100vh;
    }
    
    .main-wrapper {
        flex: 1 0 auto;
    }
    
    footer.page-footer {
        flex-shrink: 0;
        background: #f8f9fa;
        padding: 1rem 0;
        margin-top: 2rem;
        border-top: 1px solid #e9ecef;
    }
    
    /* Ajustes para el contenido */
    .page-wrapper {
        min-height: calc(100vh - 200px);
    }
</style>


</body>
  
</html>