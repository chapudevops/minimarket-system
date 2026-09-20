@extends('layouts.auth')

@section('title', 'Iniciar Sesión')

@section('content')

<div class="min-vh-100 d-flex align-items-center justify-content-center py-5" style="background: linear-gradient(135deg, #002254 0%, #043a82 50%, #0b51ad 100%);">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-xl-10 col-xxl-8">
        <div class="card border-0 shadow-lg overflow-hidden" style="border-radius: 2rem;">
          <div class="row g-0">
            <!-- Lado izquierdo - Formulario -->
            <div class="col-lg-6 order-lg-1 order-2">
              <div class="p-4 p-md-5">
                <!-- Logo -->
                <div class="text-center mb-4">
                  <img src="{{ \App\Marca::logo() }}" alt="{{ \App\Marca::nombre() }}"
                       class="mb-3" style="max-height: 90px; width: auto;">
                  <h3 class="fw-bold mb-2">¡Bienvenido de vuelta!</h3>
                  <p class="text-muted">Ingresa tus credenciales para continuar</p>
                </div>

                <!-- Alerta de error general -->
                @if(session('login_error') || $errors->has('email'))
                <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
                  <i class="bi bi-exclamation-triangle-fill me-2"></i>
                  <div>
                    <strong>Error al iniciar sesión</strong><br>
                    {{ $errors->first('email') }}
                  </div>
                  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                @endif

                @if(session('status'))
                <div class="alert alert-success alert-dismissible fade show d-flex align-items-center" role="alert">
                  <i class="bi bi-check-circle-fill me-2"></i>
                  {{ session('status') }}
                  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                @endif

                <!-- Formulario -->
                <form method="POST" action="{{ route('login') }}" id="loginForm">
                  @csrf
                  
                  <div class="mb-3">
                    <label for="inputEmailAddress" class="form-label fw-semibold">Correo Electrónico</label>
                    <div class="input-group">
                      <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                      <input type="email" 
                             class="form-control form-control-lg @error('email') is-invalid @enderror" 
                             id="inputEmailAddress" 
                             name="email" 
                             value="{{ old('email') }}" 
                             placeholder="nombre@ejemplo.com"
                             autocomplete="email"
                             autofocus>
                    </div>
                    @error('email')
                        <span class="invalid-feedback d-block" role="alert">
                          <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                  </div>

                  <div class="mb-3">
                    <label for="inputChoosePassword" class="form-label fw-semibold">Contraseña</label>
                    <div class="input-group">
                      <span class="input-group-text"><i class="bi bi-lock"></i></span>
                      <input type="password" 
                             class="form-control form-control-lg @error('password') is-invalid @enderror" 
                             id="inputChoosePassword" 
                             name="password" 
                             placeholder="Ingresa tu contraseña"
                             autocomplete="current-password">
                      <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                        <i class="bi bi-eye-slash"></i>
                      </button>
                    </div>
                    @error('password')
                        <span class="invalid-feedback d-block" role="alert">
                          <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                  </div>

                  <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" id="remember" name="remember" {{ old('remember') ? 'checked' : '' }}>
                      <label class="form-check-label" for="remember">Recordarme</label>
                    </div>
                    <a href="{{ route('password.request') }}" class="text-decoration-none small">¿Olvidaste tu contraseña?</a>
                  </div>

                  <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg fw-semibold py-2" id="btnLogin">
                      <span id="btnLoginText">Iniciar Sesión</span>
                      <span id="btnLoginSpinner" class="d-none">
                        <span class="spinner-border spinner-border-sm me-2"></span>
                        Ingresando...
                      </span>
                    </button>
                  </div>
                </form>
              </div>
            </div>

            <!-- Lado derecho - Hero Image -->
            <div class="col-lg-6 order-lg-2 order-1 d-none d-lg-flex align-items-center justify-content-center" style="background: linear-gradient(135deg, #002254 0%, #0b51ad 100%);">
              <div class="text-center p-5">
                <div class="bg-white rounded-4 d-inline-flex p-3 mb-4">
                  <img src="{{ \App\Marca::logo() }}" alt="{{ \App\Marca::nombre() }}"
                       style="max-height: 120px; width: auto;">
                </div>
                <h4 class="text-white fw-bold mb-3">{{ \App\Marca::nombre() }}</h4>
                <p class="text-white-50 mb-0">Controla tus ventas, inventario y más desde un solo lugar.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.getElementById('loginForm')?.addEventListener('submit', function(e) {
    var btn = document.getElementById('btnLogin');
    var btnText = document.getElementById('btnLoginText');
    var btnSpinner = document.getElementById('btnLoginSpinner');
    btn.disabled = true;
    btnText.classList.add('d-none');
    btnSpinner.classList.remove('d-none');
});

document.getElementById('togglePassword')?.addEventListener('click', function() {
    var input = document.getElementById('inputChoosePassword');
    var icon = this.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    } else {
        input.type = 'password';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    }
});
</script>

@endsection