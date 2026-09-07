<aside class="sidebar-wrapper">
    <div class="sidebar-header">
        <div class="logo-icon">
            <img src="{{ $empresa && $empresa->logo ? asset('storage/empresa/' . $empresa->logo) : URL::asset('build/images/logo-icon.png') }}" 
                 class="logo-img" 
                 alt="{{ $empresa->razon_social ?? 'Minimarket' }}"
                 style="width: 45px; height: 45px; object-fit: contain; border-radius: 10px;">
        </div>
        <div class="logo-name flex-grow-1">
            <h5 class="mb-0">{{ $empresa->razon_social ?? 'Minimarket' }}</h5>
            <small class="text-muted">{{ $empresa->ruc ?? '' }}</small>
        </div>
        <div class="sidebar-close">
            <span class="material-icons-outlined">close</span>
        </div>
    </div>
    <div class="sidebar-nav" data-simplebar="true">
        <ul class="metismenu" id="sidenav">
            <li>
                <a href="/">
                    <div class="parent-icon"><i class="material-icons-outlined">dashboard</i></div>
                    <div class="menu-title">Dashboard</div>
                </a>
            </li>

            @rol('Vendedor')
            <!-- Terminal POS -->
            <li class="menu-label">Punto de Venta</li>
            <li>
                <a href="{{ route('terminal.index') }}" target="_blank">
                    <div class="parent-icon"><i class="material-icons-outlined">point_of_sale</i></div>
                    <div class="menu-title">Terminal POS</div>
                    <span class="badge bg-success ms-auto">Nuevo</span>
                </a>
            </li>
            @endrol

@rol('Vendedor')
            <!-- Cajas -->
            <li class="menu-label">Cajas</li>
            <li>
                <a href="{{ route('apertura-caja.index') }}">
                    <div class="parent-icon"><i class="material-icons-outlined">account_balance_wallet</i></div>
                    <div class="menu-title">Apertura de Caja</div>
                </a>
            </li>
            @endrol

@rol('Vendedor')
            <!-- Ventas -->
            <li class="menu-label">Ventas</li>
            <li>
                <a href="javascript:;" class="has-arrow">
                    <div class="parent-icon"><i class="material-icons-outlined">receipt_long</i></div>
                    <div class="menu-title">Ventas</div>
                </a>
                <ul>
                    <li><a href="{{ route('ventas.index') }}"><i class="material-icons-outlined">receipt</i>Listado de Ventas</a></li>
                    <li><a href="{{ route('notas-credito.index') }}"><i class="material-icons-outlined">assignment_return</i>Notas de Crédito</a></li>
                    <li><a href="{{ route('notas-debito.index') }}"><i class="material-icons-outlined">assignment_ind</i>Notas de Débito</a></li>
                    <li><a href="{{ route('notas-venta.index') }}"><i class="material-icons-outlined">shopping_cart</i>Notas de Venta</a></li>
                    <li><a href="{{ route('cotizaciones.index') }}"><i class="material-icons-outlined">description</i>Cotizaciones</a></li>
                    @rol('Almacenero')
                    <li><a href="{{ route('guias-remision.index') }}"><i class="material-icons-outlined">local_shipping</i>Guías de Remisión</a></li>
                    @endrol
                </ul>
            </li>
            @endrol

<!-- Compras -->
            @rol('Almacenero')
            <li class="menu-label">Compras</li>
            <li>
                <a href="{{ route('compras.index') }}">
                    <div class="parent-icon"><i class="material-icons-outlined">shopping_bag</i></div>
                    <div class="menu-title">Compras</div>
                </a>
            </li>
            @endrol

            <!-- Gastos -->
            @rol('Vendedor')
            <li class="menu-label">Gastos</li>
            <li>
                <a href="{{ route('gastos.index') }}">
                    <div class="parent-icon"><i class="material-icons-outlined">money_off</i></div>
                    <div class="menu-title">Gastos</div>
                </a>
            </li>
            @endrol

@rol('Almacenero')
            <!-- Inventario -->
            <li class="menu-label">Inventario</li>
            <li>
                <a href="javascript:;" class="has-arrow">
                    <div class="parent-icon"><i class="material-icons-outlined">inventory_2</i></div>
                    <div class="menu-title">Inventario</div>
                </a>
                <ul>
                    <li><a href="{{ route('productos.index') }}"><i class="material-icons-outlined">shopping_bag</i>Productos</a></li>
                    <li><a href="{{ route('combos.index') }}"><i class="material-icons-outlined">dns</i>Combos</a></li>
                    <li><a href="{{ route('almacenes.index') }}"><i class="material-icons-outlined">warehouse</i>Almacenes</a></li>
                    <li><a href="{{ route('traslados.index') }}"><i class="material-icons-outlined">swap_horiz</i>Órdenes de Traslado</a></li>
                </ul>
            </li>
            @endrol

@rol('Almacenero', 'Vendedor')
            <!-- Contactos -->
            <li class="menu-label">Contactos</li>
            <li>
                <a href="javascript:;" class="has-arrow">
                    <div class="parent-icon"><i class="material-icons-outlined">contacts</i></div>
                    <div class="menu-title">Contactos</div>
                </a>
                <ul>
                    @rol('Vendedor')
                    <li><a href="{{ route('clientes.index') }}"><i class="material-icons-outlined">people</i>Clientes</a></li>
                    @endrol
                    @rol('Almacenero')
                    <li><a href="{{ route('proveedores.index') }}"><i class="material-icons-outlined">local_shipping</i>Proveedores</a></li>
                    @endrol
                    @rol()
                    <li><a href="{{ route('usuarios.index') }}"><i class="material-icons-outlined">admin_panel_settings</i>Usuarios</a></li>
                    @endrol
                </ul>
            </li>
            @endrol

@rol()
            <!-- Configuraciones -->
            <li class="menu-label">Configuración</li>
            <li>
                <a href="javascript:;" class="has-arrow">
                    <div class="parent-icon"><i class="material-icons-outlined">settings</i></div>
                    <div class="menu-title">Configuración</div>
                </a>
                <ul>
                    <li><a href="{{ route('empresa.index') }}"><i class="material-icons-outlined">business</i>Empresa</a></li>
                    <li><a href="{{ route('series.index') }}"><i class="material-icons-outlined">numbers</i>Series</a></li>
                    <li><a href="{{ route('cajas.index') }}"><i class="material-icons-outlined">point_of_sale</i>Cajas</a></li>
                </ul>
            </li>
            @endrol

        </ul>
    </div>
    <div class="sidebar-bottom gap-4">
        <div class="dark-mode">
            <a href="javascript:;" class="footer-icon dark-mode-icon" id="darkModeToggle">
                <i class="material-icons-outlined" id="darkModeIcon">dark_mode</i>
            </a>
        </div>
        <div class="version ms-auto">
            <small class="text-muted">
                <i class="material-icons-outlined fs-6">info</i> Versión 1.0.0
            </small>
        </div>
    </div>
</aside>
