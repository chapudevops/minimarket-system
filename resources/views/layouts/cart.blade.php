<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasCart">
    <div class="offcanvas-header border-bottom h-70 justify-content-between">
      <h5 class="mb-0">Carrito de Compras</h5>
      <a href="javascript:;" class="primaery-menu-close" data-bs-dismiss="offcanvas">
        <i class="material-icons-outlined">close</i>
      </a>
    </div>
    <div class="offcanvas-body p-0 d-flex flex-column align-items-center justify-content-center">
      <div class="text-center px-4">
        <i class="material-icons-outlined fs-1 text-muted">shopping_cart</i>
        <h5 class="mt-3">Carrito vacío</h5>
        <p class="text-muted">Los productos que agregues desde la tienda virtual aparecerán aquí.</p>
        <a href="{{ route('store.index') }}" class="btn btn-primary rounded-pill mt-2">
          <i class="bi bi-cart me-1"></i> Ir a la Tienda
        </a>
      </div>
    </div>
    <div class="offcanvas-footer h-70 p-3 border-top">
      <div class="d-grid">
        <a href="{{ route('store.cart') }}" class="btn btn-dark rounded-pill">Ver Carrito</a>
      </div>
    </div>
</div>
