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
      
        <!--navigation-->
        <ul class="metismenu" id="sidenav">
          <li>
            <a href="javascript:;" class="has-arrow">
              <div class="parent-icon"><i class="material-icons-outlined">home</i>
              </div>
              <div class="menu-title">Dashboard</div>
            </a>
            <ul>
              <li><a href="/"><i class="material-icons-outlined">arrow_right</i>Principal</a>
              </li>
              <!-- <li><a href="index2"><i class="material-icons-outlined">arrow_right</i>Alternate</a> -->
              </li>
            </ul>
          </li>
          <!-- <li>
            <a href="javascript:;" class="has-arrow">
              <div class="parent-icon"><i class="material-icons-outlined">widgets</i>
              </div>
              <div class="menu-title">Widgets</div>
            </a>
            <ul>
              <li><a href="widgets-data"><i class="material-icons-outlined">arrow_right</i>Data</a>
              </li>
              <li><a href="widgets-advance"><i class="material-icons-outlined">arrow_right</i>Advance</a>
              </li>
            </ul>
          </li>
          <li class="menu-label">UI Elements</li>
          <li>
            <a href="javascript:;" class="has-arrow">
              <div class="parent-icon"><i class="material-icons-outlined">inventory_2</i>
              </div>
              <div class="menu-title">Cards</div>
            </a>
            <ul>
              <li><a href="component-cards-basic"><i class="material-icons-outlined">arrow_right</i>Basic</a>
              </li>       
              <li><a href="component-cards-advance"><i class="material-icons-outlined">arrow_right</i>Advance</a>
              </li>
              <li><a href="component-cards-contact"><i class="material-icons-outlined">arrow_right</i>Contacts</a>
              </li>
            </ul>
          </li>
          
          <li>
            <a href="javascript:;" class="has-arrow">
              <div class="parent-icon"><i class="material-icons-outlined">shopping_bag</i>
              </div>
              <div class="menu-title">eCommerce</div>
            </a>
            <ul>
              <li><a href="ecommerce-add-product"><i class="material-icons-outlined">arrow_right</i>Add Product</a>
              </li>
              <li><a href="ecommerce-products"><i class="material-icons-outlined">arrow_right</i>Products</a>
              </li>
              <li><a href="ecommerce-customers"><i class="material-icons-outlined">arrow_right</i>Customers</a>
              </li>
              <li><a href="ecommerce-customer-details"><i class="material-icons-outlined">arrow_right</i>Customer Details</a>
              </li>
              <li><a href="ecommerce-orders"><i class="material-icons-outlined">arrow_right</i>Orders</a>
              </li>
              <li><a href="ecommerce-order-details"><i class="material-icons-outlined">arrow_right</i>Order Details</a>
              </li>
            </ul>     
          </li>
          <li>
            <a class="has-arrow" href="javascript:;">
              <div class="parent-icon"><i class="material-icons-outlined">card_giftcard</i>
              </div>
              <div class="menu-title">Components</div>
            </a>
            <ul>
              <li><a href="component-alerts"><i class="material-icons-outlined">arrow_right</i>Alerts</a>
              </li>
              <li><a href="component-accordions"><i class="material-icons-outlined">arrow_right</i>Accordions</a>
              </li>
              <li><a href="component-badges"><i class="material-icons-outlined">arrow_right</i>Badges</a>
              </li>
              <li><a href="component-buttons"><i class="material-icons-outlined">arrow_right</i>Buttons</a>
              </li>
              <li><a href="component-carousels"><i class="material-icons-outlined">arrow_right</i>Carousels</a>
              </li>
              <li><a href="component-media-object"><i class="material-icons-outlined">arrow_right</i>Media
                  Objects</a>
              </li>
              <li><a href="component-modals"><i class="material-icons-outlined">arrow_right</i>Modals</a>
              </li>
              <li><a href="component-navs-tabs"><i class="material-icons-outlined">arrow_right</i>Navs & Tabs</a>
              </li>
              <li><a href="component-navbar"><i class="material-icons-outlined">arrow_right</i>Navbar</a>
              </li>
              <li><a href="component-paginations"><i class="material-icons-outlined">arrow_right</i>Pagination</a>
              </li>
              <li><a href="component-popovers-tooltips"><i class="material-icons-outlined">arrow_right</i>Popovers
                  & Tooltips</a>    
              </li>
              <li><a href="component-progress-bars"><i class="material-icons-outlined">arrow_right</i>Progress</a>
              </li>
              <li><a href="component-spinners"><i class="material-icons-outlined">arrow_right</i>Spinners</a>
              </li>
              <li><a href="component-notifications"><i
                    class="material-icons-outlined">arrow_right</i>Notifications</a>
              </li>
              <li><a href="component-avtars-chips"><i class="material-icons-outlined">arrow_right</i>Avatrs &
                  Chips</a>
              </li>
              <li><a href="component-typography"><i class="material-icons-outlined">arrow_right</i>Typography</a>
               </li>
               <li><a href="component-text-utilities"><i class="material-icons-outlined">arrow_right</i>Utilities</a>
               </li>
            </ul>
          </li>
          <li>
            <a class="has-arrow" href="javascript:;">
              <div class="parent-icon"><i class="material-icons-outlined">view_agenda</i>
              </div>
              <div class="menu-title">Icons</div>
            </a>
            <ul>
              <li><a href="icons-line-icons"><i class="material-icons-outlined">arrow_right</i>Line Icons</a>
              </li>
              <li><a href="icons-boxicons"><i class="material-icons-outlined">arrow_right</i>Boxicons</a>
              </li>
              <li><a href="icons-feather-icons"><i class="material-icons-outlined">arrow_right</i>Feather
                  Icons</a>
              </li>
            </ul>
          </li>
          <li class="menu-label">Forms & Tables</li>
          <li>
            <a class="has-arrow" href="javascript:;">
              <div class="parent-icon"><i class="material-icons-outlined">toc</i>
              </div>
              <div class="menu-title">Forms</div>
            </a>
            <ul>
              <li><a href="form-elements"><i class="material-icons-outlined">arrow_right</i>Form Elements</a>
              </li>
              <li><a href="form-input-group"><i class="material-icons-outlined">arrow_right</i>Input Groups</a>
              </li>
              <li><a href="form-radios-and-checkboxes"><i class="material-icons-outlined">arrow_right</i>Radios &
                  Checkboxes</a>
              </li>
              <li><a href="form-layouts"><i class="material-icons-outlined">arrow_right</i>Forms Layouts</a>
              </li>
              <li><a href="form-validations"><i class="material-icons-outlined">arrow_right</i>Form Validation</a>
              </li>
              <li><a href="form-wizard"><i class="material-icons-outlined">arrow_right</i>Form Wizard</a>
              </li>
              <li><a href="form-file-upload"><i class="material-icons-outlined">arrow_right</i>File Upload</a>
              </li>
              <li><a href="form-date-time-pickes"><i class="material-icons-outlined">arrow_right</i>Date
                  Pickers</a>
              </li>
              <li><a href="form-select2"><i class="material-icons-outlined">arrow_right</i>Select2</a>
              </li>
              <li><a href="form-repeater"><i class="material-icons-outlined">arrow_right</i>Form Repeater</a>
              </li>
            </ul>
          </li>
          <li>
            <a class="has-arrow" href="javascript:;">
              <div class="parent-icon"><i class="material-icons-outlined">api</i>
              </div>
              <div class="menu-title">Tables</div>
            </a>
            <ul>
              <li><a href="table-basic-table"><i class="material-icons-outlined">arrow_right</i>Basic Table</a>
              </li>
              <li><a href="table-datatable"><i class="material-icons-outlined">arrow_right</i>Data Table</a>
              </li>
            </ul>
          </li>
          <li>
            <a class="has-arrow" href="javascript:;">
              <div class="parent-icon"><i class="material-icons-outlined">apps</i>
              </div>
              <div class="menu-title">Apps</div>
            </a>
            <ul>
              <li><a href="app-fullcalender"><i class="material-icons-outlined">arrow_right</i>Calendar</a>
              </li>
              <li><a href="app-to-do"><i class="material-icons-outlined">arrow_right</i>To do</a>
              </li>
              <li><a href="app-invoice"><i class="material-icons-outlined">arrow_right</i>Invoice</a>
              </li>
            </ul>
          </li>
          <li class="menu-label">Pages</li>
          <li>
            <a class="has-arrow" href="javascript:;">
              <div class="parent-icon"><i class="material-icons-outlined">lock</i>
              </div>
              <div class="menu-title">Authentication</div>
            </a>
            <ul>
              <li><a class="has-arrow" href="javascript:;"><i class="material-icons-outlined">arrow_right</i>Basic</a>
                <ul>
                  <li><a href="auth-basic-login" target="_blank"><i class="material-icons-outlined">arrow_right</i>Login</a></li>
                  <li><a href="auth-basic-register" target="_blank"><i class="material-icons-outlined">arrow_right</i>Register</a></li>
                  <li><a href="auth-basic-forgot-password" target="_blank"><i class="material-icons-outlined">arrow_right</i>Forgot Password</a></li>
                  <li><a href="auth-basic-reset-password" target="_blank"><i class="material-icons-outlined">arrow_right</i>Reset Password</a></li>
                </ul>
              </li>
              <li><a class="has-arrow" href="javascript:;"><i class="material-icons-outlined">arrow_right</i>Cover</a>
                <ul>
                  <li><a href="auth-cover-login" target="_blank"><i class="material-icons-outlined">arrow_right</i>Login</a></li>
                  <li><a href="auth-cover-register" target="_blank"><i class="material-icons-outlined">arrow_right</i>Register</a></li>
                  <li><a href="auth-cover-forgot-password" target="_blank"><i class="material-icons-outlined">arrow_right</i>Forgot Password</a></li>
                  <li><a href="auth-cover-reset-password" target="_blank"><i class="material-icons-outlined">arrow_right</i>Reset Password</a></li>
                </ul>
              </li>
              <li><a class="has-arrow" href="javascript:;"><i class="material-icons-outlined">arrow_right</i>Boxed</a>
                  <ul>
                    <li><a href="auth-boxed-login" target="_blank"><i class="material-icons-outlined">arrow_right</i>Login</a></li>
                    <li><a href="auth-boxed-register" target="_blank"><i class="material-icons-outlined">arrow_right</i>Register</a></li>
                    <li><a href="auth-boxed-forgot-password" target="_blank"><i class="material-icons-outlined">arrow_right</i>Forgot Password</a></li>
                    <li><a href="auth-boxed-reset-password" target="_blank"><i class="material-icons-outlined">arrow_right</i>Reset Password</a></li>
                  </ul>
              </li>
            </ul>
          </li>
          <li>
            <a href="user-profile">
              <div class="parent-icon"><i class="material-icons-outlined">person</i>
              </div>
              <div class="menu-title">User Profile</div>
            </a>
          </li>
          <li>
            <a href="timeline">
              <div class="parent-icon"><i class="material-icons-outlined">join_right</i>
              </div>
              <div class="menu-title">Timeline</div>
            </a>
          </li>
          <li>
            <a class="has-arrow" href="javascript:;">
              <div class="parent-icon"><i class="material-icons-outlined">report_problem</i>
              </div>
              <div class="menu-title">Pages</div>
            </a>
            <ul>
              <li><a href="pages-error-404" target="_blank"><i class="material-icons-outlined">arrow_right</i>404
                  Error</a>
              </li>
              <li><a href="pages-error-505" target="_blank"><i class="material-icons-outlined">arrow_right</i>505
                  Error</a>
              </li>
              <li><a href="pages-coming-soon" target="_blank"><i
                    class="material-icons-outlined">arrow_right</i>Coming Soon</a>
              </li>
              <li><a href="pages-starter-page" target="_blank"><i
                    class="material-icons-outlined">arrow_right</i>Blank Page</a> 
              </li>
            </ul>
          </li>
          <li>
            <a href="faq">
              <div class="parent-icon"><i class="material-icons-outlined">help_outline</i>
              </div>
              <div class="menu-title">FAQ</div>
            </a>
          </li>
          <li>
            <a href="pricing-table">
              <div class="parent-icon"><i class="material-icons-outlined">sports_football</i>
              </div>
              <div class="menu-title">Pricing</div>
            </a>
          </li>
          <li class="menu-label">Charts & Maps</li>
          <li>
            <a class="has-arrow" href="javascript:;">
              <div class="parent-icon"><i class="material-icons-outlined">fitbit</i>
              </div>
              <div class="menu-title">Charts</div>
            </a>
            <ul>
              <li><a href="charts-apex-chart"><i class="material-icons-outlined">arrow_right</i>Apex</a>
              </li>
              <li><a href="charts-chartjs"><i class="material-icons-outlined">arrow_right</i>Chartjs</a>
              </li>   
            </ul>
          </li>
          <li>
            <a class="has-arrow" href="javascript:;">
              <div class="parent-icon"><i class="material-icons-outlined">sports_football</i>
              </div>
              <div class="menu-title">Maps</div>
            </a>
            <ul>
              <li><a href="map-google-maps"><i class="material-icons-outlined">arrow_right</i>Google Maps</a>
              </li>
              <li><a href="map-vector-maps"><i class="material-icons-outlined">arrow_right</i>Vector Maps</a>
              </li>
            </ul>
          </li>
          <li class="menu-label">Others</li>
          <li>
            <a class="has-arrow" href="javascript:;">
              <div class="parent-icon"><i class="material-icons-outlined">face_5</i>
              </div>
              <div class="menu-title">Menu Levels</div>
            </a>
            <ul>
              <li><a class="has-arrow" href="javascript:;"><i class="material-icons-outlined">arrow_right</i>Level
                  One</a>
                <ul>
                  <li><a class="has-arrow" href="javascript:;"><i class="material-icons-outlined">arrow_right</i>Level
                      Two</a>
                    <ul>
                      <li><a href="javascript:;"><i class="material-icons-outlined">arrow_right</i>Level Three</a>
                      </li>
                    </ul>
                  </li>
                </ul>
              </li>
            </ul>
          </li>
          <li>
            <a href="javascrpt:;">
              <div class="parent-icon"><i class="material-icons-outlined">description</i>
              </div>
              <div class="menu-title">Documentation</div>
            </a>
          </li>
          <li>
            <a href="javascrpt:;">
              <div class="parent-icon"><i class="material-icons-outlined">support</i>
              </div>
              <div class="menu-title">Support</div>
            </a>
          </li> -->

          <!-- Cajas -->
          <li class="menu-label">Cajas</li>
          <li>
              <a href="{{ route('apertura-caja.index') }}">
                  <div class="parent-icon">
                      <i class="material-icons-outlined">point_of_sale</i>
                  </div>
                  <div class="menu-title">Apertura de Caja</div>
              </a>
          </li>

          <!-- Ventas -->
          <li class="menu-label">Ventas</li>
          <li>
              <a href="javascript:;" class="has-arrow">
                  <div class="parent-icon">
                      <i class="material-icons-outlined">point_of_sale</i>
                  </div>
                  <div class="menu-title">Ventas</div>
              </a>
              <ul>
                  <li>
                      <a href="{{ route('ventas.index') }}">
                          <i class="material-icons-outlined">receipt</i>
                          Listado de Ventas
                      </a>
                  </li>
                  <li>
                      <a href="{{ route('notas-credito.index') }}">
                          <i class="material-icons-outlined">assignment</i>
                          Notas de Crédito
                      </a>
                  </li>
                  <li>
                      <a href="{{ route('notas-debito.index') }}">
                          <i class="material-icons-outlined">assignment_ind</i>
                          Notas de Débito
                      </a>
                  </li>
                  <li>
                      <a href="{{ route('notas-venta.index') }}">
                          <i class="material-icons-outlined">shopping_cart</i>
                          Notas de Venta
                      </a>
                  </li>
                  <li>
                      <a href="{{ route('cotizaciones.index') }}">
                          <i class="material-icons-outlined">description</i>
                          Cotizaciones
                      </a>
                  </li>
                  <li>
                      <a href="{{ route('guias-remision.index') }}">
                          <i class="material-icons-outlined">local_shipping</i>
                          Guías de Remisión
                      </a>
                  </li>
              </ul>
          </li>

          <!-- Compras -->
          <li class="menu-label">Compras</li>
          <li>
              <a href="{{ route('compras.index') }}">
                  <div class="parent-icon">
                      <i class="material-icons-outlined">shopping_cart</i>
                  </div>
                  <div class="menu-title">Compras</div>
              </a>
          </li>
          <li>
            <a href="{{ route('gastos.index') }}">
                <i class="material-icons-outlined">money_off</i>
                Gastos
            </a>
        </li>



          <!-- Terminal POS -->
        <li class="menu-label">Ventas</li>
        <li>
            <a href="{{ route('terminal.index') }}" target="_blank">
                <div class="parent-icon">
                    <i class="material-icons-outlined">point_of_sale</i>
                </div>
                <div class="menu-title">Terminal POS</div>
            </a>
        </li>

          <!-- Inventario -->
          <li class="menu-label">Inventario</li>
          <li>
              <a href="javascript:;" class="has-arrow">
                  <div class="parent-icon">
                      <i class="material-icons-outlined">inventory_2</i>
                  </div>
                  <div class="menu-title">Inventario</div>
              </a>
              <ul>
                  <li>
                      <a href="{{ route('productos.index') }}">
                          <i class="material-icons-outlined">shopping_bag</i>
                          Productos
                      </a>
                  </li>
                  <li>
                      <a href="{{ route('almacenes.index') }}">
                          <i class="material-icons-outlined">warehouse</i>
                          Almacenes
                      </a>
                  </li>
                  <li>
                      <a href="{{ route('traslados.index') }}">
                          <i class="material-icons-outlined">swap_horiz</i>
                          Órdenes de Traslado
                      </a>
                  </li>
              </ul>
          </li>

          <!-- Contactos -->
          <li class="menu-label">Contactos</li>
          <li>
              <a href="javascript:;" class="has-arrow">
                  <div class="parent-icon">
                      <i class="material-icons-outlined">contacts</i>
                  </div>
                  <div class="menu-title">Contactos</div>
              </a>
              <ul>
                  <li>
                      <a href="{{ route('clientes.index') }}">
                          <i class="material-icons-outlined">people</i>
                          Clientes
                      </a>
                  </li>
                  <li>
                      <a href="{{ route('proveedores.index') }}">
                          <i class="material-icons-outlined">local_shipping</i>
                          Proveedores
                      </a>
                  </li>
                  <li>
                      <a href="{{ route('usuarios.index') }}">
                          <i class="material-icons-outlined">admin_panel_settings</i>
                          Usuarios
                      </a>
                  </li>
                  <li>
                      <a href="#">
                          <i class="material-icons-outlined">security</i>
                          Roles
                      </a>
                  </li>
              </ul>
          </li>

          <!-- Configuraciones -->
          <li class="menu-label">Configuraciones</li>
          <li>
              <a href="javascript:;" class="has-arrow">
                  <div class="parent-icon">
                      <i class="material-icons-outlined">settings</i>
                  </div>
                  <div class="menu-title">Configuraciones</div>
              </a>
              <ul>
                  <li>
                      <a href="{{ route('empresa.index') }}">
                          <i class="material-icons-outlined">business</i>
                          Empresa
                      </a>
                  </li>
                  <li>
                      <a href="{{ route('series.index') }}">
                          <i class="material-icons-outlined">numbers</i>
                          Series
                      </a>
                  </li>
                  <li>
                      <a href="{{ route('cajas.index') }}">
                          <i class="material-icons-outlined">point_of_sale</i>
                          Cajas
                      </a>
                  </li>
              </ul>
          </li>


         </ul>
        <!--end navigation-->
    </div>
    <div class="sidebar-bottom gap-4">
    <div class="dark-mode">
        <a href="javascript:;" class="footer-icon dark-mode-icon">
            <i class="material-icons-outlined">dark_mode</i>
        </a>
    </div>
    <div class="version ms-auto">
        <small class="text-muted">
            <i class="material-icons-outlined fs-6">info</i> Versión 1.0.0
        </small>
    </div>
</div>
</aside>