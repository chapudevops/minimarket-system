<footer class="page-footer">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-md-6 text-center text-md-start">
                <p class="mb-0 py-2">
                    &copy; {{ date('Y') }} 
                    <strong class="text-primary">{{ $empresa->razon_social ?? 'Minimarket System' }}</strong>
                    @if($empresa->ruc)
                    <span class="text-muted d-none d-md-inline">| {{ $empresa->ruc }}</span>
                    @endif
                </p>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <p class="mb-0 py-2">
                    <span class="text-muted">Versión 1.0.0</span>
                    <span class="text-muted d-none d-md-inline ms-2">| Powered by Laravel</span>
                </p>
            </div>
        </div>
    </div>
</footer>
