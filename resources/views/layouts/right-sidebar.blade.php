<button class="btn btn-primary position-fixed bottom-0 end-0 m-3 d-flex align-items-center gap-2 rounded-pill shadow" type="button" data-bs-toggle="offcanvas" data-bs-target="#staticBackdrop">
    <i class="material-icons-outlined">tune</i> Tema
</button>

<div class="offcanvas offcanvas-end" data-bs-scroll="true" tabindex="-1" id="staticBackdrop">
    <div class="offcanvas-header border-bottom h-70 justify-content-between">
        <div class="">
            <h5 class="mb-0">Personalizar Tema</h5>
            <p class="mb-0">Elige el modo de visualización</p>
        </div>
        <a href="javascript:;" class="primaery-menu-close" data-bs-dismiss="offcanvas">
            <i class="material-icons-outlined">close</i>
        </a>
    </div>
    <div class="offcanvas-body">
        <div>
            <p class="fw-bold">Modo de Tema</p>
            <div class="row g-3">
                <div class="col-6">
                    <input type="radio" class="btn-check" name="theme-options" id="LightTheme" value="light">
                    <label class="btn btn-outline-secondary d-flex flex-column gap-1 align-items-center justify-content-center p-4 w-100 rounded-4" for="LightTheme">
                        <span class="material-icons-outlined fs-2">light_mode</span>
                        <span>Claro</span>
                    </label>
                </div>
                <div class="col-6">
                    <input type="radio" class="btn-check" name="theme-options" id="DarkTheme" value="dark">
                    <label class="btn btn-outline-secondary d-flex flex-column gap-1 align-items-center justify-content-center p-4 w-100 rounded-4" for="DarkTheme">
                        <span class="material-icons-outlined fs-2">dark_mode</span>
                        <span>Oscuro</span>
                    </label>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var html = document.documentElement;
    var currentTheme = localStorage.getItem('theme') || 'light';

    document.querySelectorAll('input[name="theme-options"]').forEach(function(input) {
        if (input.value === currentTheme) input.checked = true;

        input.addEventListener('change', function() {
            if (this.checked) {
                html.setAttribute('data-bs-theme', this.value);
                localStorage.setItem('theme', this.value);
                var icon = document.getElementById('darkModeIcon');
                if (icon) icon.textContent = this.value === 'dark' ? 'light_mode' : 'dark_mode';
            }
        });
    });
});
</script>
