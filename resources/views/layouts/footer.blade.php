<footer class="page-footer">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-md-6 text-center text-md-start">
                <p class="mb-0 py-2">
                    &copy; {{ date('Y') }} 
                    {{-- Marca::nombre() ya resuelve el caso de una instalacion
                         nueva sin fila en empresa, que antes tiraba 500 en
                         todas las paginas. --}}
                    <strong class="text-primary">{{ \App\Marca::nombre() }}</strong>
                    @if(\App\Marca::ruc())
                    <span class="text-muted d-none d-md-inline">| {{ \App\Marca::ruc() }}</span>
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
