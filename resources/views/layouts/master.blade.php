<!doctype html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!--favicon - Logo de la empresa -->
    @php
        $empresa = \App\Models\Empresa::first();
        $favicon = $empresa && $empresa->logo 
            ? asset('storage/empresa/' . $empresa->logo) 
            : URL::asset('build/images/infinitydevlogo.png');
    @endphp
    <!--favicon-->
    <link rel="icon" href="{{ $favicon }}" type="image/png">
    <link rel="shortcut icon" href="{{ $favicon }}" type="image/png">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <title>@yield('title') | Minimarket-system</title>

    @yield('css')

    @include('layouts.head-css')

    <style>
        #preloader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
        }

        body {
            display: flex;
            flex-direction: column;
        }

        .main-wrapper {
            flex: 1 0 auto;
        }

        .main-content {
            flex: 1;
        }

        footer.page-footer {
            flex-shrink: 0;
            background: #f8f9fa;
            padding: 1rem 0;
            border-top: 1px solid #e9ecef;
            width: 100%;
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
            background: linear-gradient(135deg, #667eea, #764ba2) !important;
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

@php
    $empresa = \App\Models\Empresa::first();
@endphp

<div id="preloader">
    <div class="preloader-container">
        <div class="preloader-logo">
            <img src="{{ URL::asset('build/images/infinitydevlogo.png') }}" alt="Minimarket">
        </div>
        <h5 class="preloader-title">{{ $empresa->razon_social ?? 'Minimarket System' }}</h5>
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