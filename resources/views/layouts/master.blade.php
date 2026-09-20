<!doctype html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- Logo y nombre salen de App\Marca, que los resuelve desde la empresa
         configurada. Antes cada vista hacia su propia consulta y apuntaba a
         una ruta distinta. --}}
    <link rel="icon" href="{{ \App\Marca::favicon() }}" type="image/png">
    <link rel="shortcut icon" href="{{ \App\Marca::favicon() }}" type="image/png">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <title>@yield('title') | {{ \App\Marca::nombre() }}</title>

    @yield('css')

    @include('layouts.head-css')

    <style>
        #preloader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #002254 0%, #0b51ad 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 999999;
        }

        #preloader.hidden {
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.4s ease, visibility 0.4s ease;
        }

        .preloader-container {
            text-align: center;
            padding: 2rem;
            animation: fadeInUp 0.5s ease;
        }
        .preloader-logo {
            width: 120px;
            height: 120px;
            background: rgba(255,255,255,0.15);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255,255,255,0.2);
        }
        .preloader-logo img {
            width: 70px;
            height: 70px;
            object-fit: contain;
            filter: brightness(0) invert(1);
        }
        .preloader-title {
            color: #fff;
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }
        .preloader-sub {
            color: rgba(255,255,255,0.7);
            font-size: 0.875rem;
            margin-bottom: 1.5rem;
        }
        .preloader-spinner {
            width: 48px;
            height: 48px;
            border: 3px solid rgba(255,255,255,0.2);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin: 0 auto;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .main-content {
            opacity: 0;
            transition: opacity 0.4s ease;
        }
        body.ready .main-content {
            opacity: 1;
        }


        /* ========== ESTILOS PARA EL FOOTER FIJO AL FINAL ========== */
        /* min-height y no height: con height fijo el body mide exactamente una
           pantalla, el contenido mas largo se desborda y el footer queda a
           100vh, es decir encima del contenido. Con min-height el body crece
           con lo que tenga adentro. */
        html {
            height: 100%;
        }

        body {
            min-height: 100%;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
        }

        .main-wrapper {
            flex: 1 0 auto;
        }

        .main-content {
            flex: 1;
        }

        /* main.css deja el footer en position:absolute, lo que lo saca del
           flujo flex y hace que quede flotando sobre el contenido cuando la
           pagina es mas corta o mas larga que la ventana. Como item flex normal
           queda siempre despues del contenido, y al final si la pagina no llena
           la pantalla. El margen izquierdo replica el del main-wrapper para
           esquivar el sidebar, que es fixed. */
        footer.page-footer {
            position: static;
            flex-shrink: 0;
            height: auto;
            width: auto;
            margin-left: 260px;
            background: #f8f9fa;
            padding: 0.75rem 0;
            border-top: 1px solid #e9ecef;
            transition: margin-left ease-out 0.3s;
        }

        /* Sidebar colapsado */
        body.toggled footer.page-footer {
            margin-left: 70px;
        }

        @media (max-width: 1199px) {
            footer.page-footer {
                margin-left: 0;
            }
            body.toggled footer.page-footer {
                margin-left: 0;
            }
        }

        .page-wrapper {
            min-height: auto;
        }

        /* ========== ESTILOS GLOBALES PARA TABLAS CRUD ========== */
        .table-crud thead {
            background: linear-gradient(135deg, #1e293b, #334155);
            color: #fff;
        }
        .table-crud thead th {
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 12px 10px;
            border-bottom: none;
        }
        .table-crud tbody tr {
            transition: background-color 0.15s ease;
        }
        .table-crud tbody tr:hover {
            background-color: rgba(13, 110, 253, 0.05);
        }
        .table-crud tbody td {
            padding: 10px;
            vertical-align: middle;
        }
        .table-crud {
            border-collapse: separate;
            border-spacing: 0;
            border-radius: 12px;
            overflow: hidden;
        }
        .table-crud .btn-sm {
            border-radius: 8px;
            padding: 4px 10px;
            font-size: 0.75rem;
        }
        .table-crud .badge {
            font-weight: 500;
            padding: 5px 10px;
            border-radius: 6px;
        }

        /* ========== DATA TABLES PERSONALIZACIÓN ========== */
        .dataTables_wrapper .dataTables_length select {
            border-radius: 8px;
            padding: 4px 8px;
        }
        .dataTables_wrapper .dataTables_filter input {
            border-radius: 20px;
            padding: 6px 16px;
            border: 1px solid #dee2e6;
        }
        .dataTables_wrapper .dataTables_filter input:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 3px rgba(13,110,253,0.15);
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            border-radius: 8px !important;
            margin: 0 2px;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: linear-gradient(135deg, #002254 0%, #0b51ad 100%) !important;
            border-color: transparent !important;
            color: #fff !important;
        }

        /* ========== CARDS CON EFECTO ========== */
        .card {
            transition: box-shadow 0.2s ease;
        }

        /* ========== SIDEBAR MEJORAS ========== */
        .sidebar-wrapper .metismenu .menu-label {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 16px 16px 6px;
            opacity: 0.7;
        }
        .sidebar-wrapper .metismenu .has-arrow::after {
            right: 16px;
        }
        .sidebar-wrapper .sidebar-bottom {
            border-top: 1px solid rgba(0,0,0,0.06);
        }

        /* ========== TRANSICIÓN DE PÁGINA ========== */
        .main-content {
            animation: pageFadeIn 0.3s ease;
        }
        @keyframes pageFadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>

<body>

<div id="preloader">
    <div class="preloader-container">
        <div class="preloader-logo">
            <img src="{{ \App\Marca::logo() }}" alt="{{ \App\Marca::nombre() }}">
        </div>
        <h5 class="preloader-title">{{ \App\Marca::nombre() }}</h5>
        <p class="preloader-sub">Cargando...</p>
        <div class="preloader-spinner"></div>
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

{{-- Helpers compartidos por los listados; debe cargar antes del config.js de cada modulo --}}
<script src="{{ URL::asset('build/js/common/crud.js') }}"></script>

@yield('scripts')

<script>
    function hidePreloader() {
        var p = document.getElementById('preloader');
        if (p) {
            p.classList.add('hidden');
            document.body.classList.add('ready');
        }
    }

    document.addEventListener("DOMContentLoaded", function() {
        // Mostrar preloader inmediatamente
        var p = document.getElementById('preloader');
        if (p) p.style.display = 'flex';

        // Ocultar después de 1.2s (mínimo para que se vea la animación)
        setTimeout(hidePreloader, 1200);

        // Aplicar clase table-crud a tablas
        document.querySelectorAll('.table-responsive table.table').forEach(function(t) {
            t.classList.add('table-crud');
        });

        // Dark Mode Toggle
        var toggleBtn = document.getElementById('darkModeToggle');
        var icon = document.getElementById('darkModeIcon');
        var html = document.documentElement;

        if (localStorage.getItem('theme') === 'dark') {
            html.setAttribute('data-bs-theme', 'dark');
            if (icon) icon.textContent = 'light_mode';
        }

        toggleBtn?.addEventListener('click', function() {
            var isDark = html.getAttribute('data-bs-theme') === 'dark';
            var newTheme = isDark ? 'light' : 'dark';
            html.setAttribute('data-bs-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            if (icon) icon.textContent = isDark ? 'dark_mode' : 'light_mode';
        });
    });

    // Fallback de seguridad: ocultar preloader después de 5s máximo
    setTimeout(hidePreloader, 5000);
</script>

</body>
</html>