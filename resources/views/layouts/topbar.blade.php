<header class="top-header">
    <nav class="navbar navbar-expand align-items-center gap-4">
      <div class="btn-toggle">
        <a href="javascript:;"><i class="material-icons-outlined">menu</i></a>
      </div>

      <!-- Atajos rápidos -->
      <ul class="navbar-nav gap-1 nav-right-links align-items-center">
        <li class="nav-item">
          <a href="{{ route('terminal.index') }}" target="_blank" class="nav-link shortcut-icon" title="Nueva Venta (POS)">
            <i class="material-icons-outlined">point_of_sale</i>
          </a>
        </li>
        <li class="nav-item">
          <a href="{{ route('compras.create') }}" class="nav-link shortcut-icon" title="Nueva Compra">
            <i class="material-icons-outlined">add_shopping_cart</i>
          </a>
        </li>
        <li class="nav-item">
          <a href="{{ route('productos.index') }}" class="nav-link shortcut-icon" title="Productos">
            <i class="material-icons-outlined">inventory_2</i>
          </a>
        </li>
        <li class="nav-item dropdown position-static d-none d-lg-block">
          <a class="nav-link dropdown-toggle dropdown-toggle-nocaret" data-bs-auto-close="outside"
            data-bs-toggle="dropdown" href="javascript:;" title="Acceso Rápido">
            <i class="material-icons-outlined">apps</i>
          </a>
          <div class="dropdown-menu dropdown-menu-end dropdown-apps shadow-lg p-3">
            <div class="border rounded-4 overflow-hidden">
              <div class="row row-cols-3 g-0 border-bottom">
                <div class="col border-end">
                  <a href="{{ route('ventas.index') }}" class="app-wrapper d-flex flex-column gap-2 text-center text-decoration-none">
                    <div class="app-icon"><i class="material-icons-outlined fs-2 text-primary">receipt</i></div>
                    <div class="app-name"><p class="mb-0">Ventas</p></div>
                  </a>
                </div>
                <div class="col border-end">
                  <a href="{{ route('clientes.index') }}" class="app-wrapper d-flex flex-column gap-2 text-center text-decoration-none">
                    <div class="app-icon"><i class="material-icons-outlined fs-2 text-success">people</i></div>
                    <div class="app-name"><p class="mb-0">Clientes</p></div>
                  </a>
                </div>
                <div class="col">
                  <a href="{{ route('proveedores.index') }}" class="app-wrapper d-flex flex-column gap-2 text-center text-decoration-none">
                    <div class="app-icon"><i class="material-icons-outlined fs-2 text-warning">local_shipping</i></div>
                    <div class="app-name"><p class="mb-0">Proveedores</p></div>
                  </a>
                </div>
              </div>
              <div class="row row-cols-3 g-0 border-bottom">
                <div class="col border-end">
                  <a href="{{ route('productos.index') }}" class="app-wrapper d-flex flex-column gap-2 text-center text-decoration-none">
                    <div class="app-icon"><i class="material-icons-outlined fs-2 text-info">inventory_2</i></div>
                    <div class="app-name"><p class="mb-0">Productos</p></div>
                  </a>
                </div>
                <div class="col border-end">
                  <a href="{{ route('compras.index') }}" class="app-wrapper d-flex flex-column gap-2 text-center text-decoration-none">
                    <div class="app-icon"><i class="material-icons-outlined fs-2 text-danger">shopping_bag</i></div>
                    <div class="app-name"><p class="mb-0">Compras</p></div>
                  </a>
                </div>
                <div class="col">
                  <a href="{{ route('empresa.index') }}" class="app-wrapper d-flex flex-column gap-2 text-center text-decoration-none">
                    <div class="app-icon"><i class="material-icons-outlined fs-2 text-secondary">settings</i></div>
                    <div class="app-name"><p class="mb-0">Empresa</p></div>
                  </a>
                </div>
              </div>
              <div class="row row-cols-3 g-0">
                <div class="col border-end">
                  <a href="{{ route('apertura-caja.index') }}" class="app-wrapper d-flex flex-column gap-2 text-center text-decoration-none">
                    <div class="app-icon"><i class="material-icons-outlined fs-2 text-success">account_balance_wallet</i></div>
                    <div class="app-name"><p class="mb-0">Caja</p></div>
                  </a>
                </div>
                <div class="col border-end">
                  <a href="{{ route('usuarios.index') }}" class="app-wrapper d-flex flex-column gap-2 text-center text-decoration-none">
                    <div class="app-icon"><i class="material-icons-outlined fs-2 text-primary">admin_panel_settings</i></div>
                    <div class="app-name"><p class="mb-0">Usuarios</p></div>
                  </a>
                </div>
                <div class="col">
                  <a href="{{ route('cotizaciones.index') }}" class="app-wrapper d-flex flex-column gap-2 text-center text-decoration-none">
                    <div class="app-icon"><i class="material-icons-outlined fs-2 text-warning">description</i></div>
                    <div class="app-name"><p class="mb-0">Cotizaciones</p></div>
                  </a>
                </div>
              </div>
            </div>
          </div>
        </li>

        <!-- Campanita de Notificaciones -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle dropdown-toggle-nocaret position-relative" data-bs-auto-close="outside"
            data-bs-toggle="dropdown" href="javascript:;" title="Notificaciones">
            <i class="material-icons-outlined">notifications</i>
            <span class="badge-notify" id="notificaciones-count">0</span>
          </a>
          <div class="dropdown-menu dropdown-notify dropdown-menu-end shadow" style="width: 380px;">
            <div class="px-3 py-2 d-flex align-items-center justify-content-between border-bottom">
              <h5 class="notiy-title mb-0">Notificaciones</h5>
              <button class="btn btn-sm btn-link text-decoration-none" id="marcarTodasLeidas" style="display:none;">Marcar todas como leídas</button>
            </div>
            <div class="notify-list" id="lista-notificaciones" style="max-height: 350px; overflow-y: auto;">
              <div class="text-center py-4" id="notificaciones-vacio">
                <i class="material-icons-outlined fs-1 text-muted">notifications_none</i>
                <p class="mb-0 text-muted mt-2">No hay notificaciones</p>
              </div>
              <div id="notificaciones-items" style="display:none;"></div>
            </div>
            <div class="dropdown-footer text-center border-top py-2">
              <a href="{{ route('home') }}" class="text-decoration-none">Ir al Dashboard</a>
            </div>
          </div>
        </li>

        <!-- Usuario Autenticado -->
        <li class="nav-item dropdown">
          <a href="javascript:;" class="dropdown-toggle dropdown-toggle-nocaret" data-bs-toggle="dropdown">
            <div class="d-flex align-items-center gap-2">
              <div class="user-avatar rounded-circle p-1 border d-flex align-items-center justify-content-center bg-primary text-white fw-bold" 
                   style="width: 40px; height: 40px; font-size: 16px;">
                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
              </div>
              <div class="d-none d-md-block text-start">
                <small class="fw-bold d-block">{{ Auth::user()->name }}</small>
                <small class="text-muted">{{ Auth::user()->email }}</small>
              </div>
            </div>
          </a>
          <div class="dropdown-menu dropdown-user dropdown-menu-end shadow">
            <a class="dropdown-item gap-2 py-2" href="javascript:;">
              <div class="text-center">
                <div class="rounded-circle p-1 shadow mb-3 mx-auto d-flex align-items-center justify-content-center bg-primary text-white fw-bold"
                     style="width: 80px; height: 80px; font-size: 32px;">
                  {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                </div>
                <h5 class="user-name mb-0 fw-bold">{{ Auth::user()->name }}</h5>
                <p class="mb-0 text-muted small">{{ Auth::user()->email }}</p>
              </div>
            </a>
            <hr class="dropdown-divider">
            <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="javascript:;">
              <i class="material-icons-outlined">settings</i> Configuración
            </a>
            <hr class="dropdown-divider">
            <form method="POST" action="{{ route('logout') }}">
              @csrf
              <button type="submit" class="dropdown-item d-flex align-items-center gap-2 py-2 text-danger">
                <i class="material-icons-outlined">power_settings_new</i> Cerrar Sesión
              </button>
            </form>
          </div>
        </li>
      </ul>
    </nav>
  </header>

<script>
$(document).ready(function() {
    function cargarNotificaciones() {
        $.ajax({
            url: '{{ route("home") }}',
            type: 'GET',
            data: { _notificaciones: 1 },
            success: function(response) {
                var count = 0;
                $('#notificaciones-items').empty().hide();
                $('#notificaciones-vacio').show();
                
                if (response.alertas && response.alertas.length > 0) {
                    count = response.alertas.length;
                    $('#notificaciones-vacio').hide();
                    $.each(response.alertas, function(i, alerta) {
                        $('#notificaciones-items').append(
                            '<a href="' + alerta.ruta + '" class="notify-item d-flex align-items-center gap-3 px-3 py-2 text-decoration-none text-dark border-bottom">' +
                            '  <div class="wh-36 rounded-circle bg-' + alerta.color + ' bg-opacity-10 d-flex align-items-center justify-content-center">' +
                            '    <span class="material-icons-outlined fs-6 text-' + alerta.color + '">' + alerta.icono + '</span>' +
                            '  </div>' +
                            '  <div class="flex-grow-1">' +
                            '    <p class="mb-0 small">' + alerta.mensaje + '</p>' +
                            '  </div>' +
                            '</a>'
                        );
                    });
                    $('#notificaciones-items').show();
                    $('#marcarTodasLeidas').show();
                }
                $('#notificaciones-count').text(count).toggle(count > 0);
            }
        });
    }

    cargarNotificaciones();
    setInterval(cargarNotificaciones, 60000);
});
</script>
