@extends('layouts.auth')

@section('title', 'Iniciar Sesión')

@section('content')

<div class="min-vh-100 d-flex align-items-center justify-content-center py-5" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
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
                  <img src="{{ URL::asset('build/images/infinitydevlogo.png') }}" class="mb-3" width="120" alt="Logo">
                  <h3 class="fw-bold mb-2">¡Bienvenido de vuelta!</h3>
                  <p class="text-muted">Ingresa tus credenciales para continuar</p>
                </div>

                <!-- Botones sociales -->
                <div class="row g-2 mb-4">
                  <div class="col">
                    <button class="btn btn-outline-secondary w-100 py-2 rounded-pill">
                      <img src="{{ URL::asset('build/images/apps/05.png') }}" width="18" class="me-2" alt=""> Google
                    </button>
                  </div>
                  <div class="col">
                    <button class="btn btn-outline-secondary w-100 py-2 rounded-pill">
                      <img src="{{ URL::asset('build/images/apps/17.png') }}" width="18" class="me-2" alt=""> Facebook
                    </button>
                  </div>
                  <div class="col">
                    <button class="btn btn-outline-secondary w-100 py-2 rounded-pill">
                      <img src="{{ URL::asset('build/images/apps/18.png') }}" width="18" class="me-2" alt=""> LinkedIn
                    </button>
                  </div>
                </div>

                <!-- Separador -->
                <div class="d-flex align-items-center my-4">
                  <hr class="flex-grow-1">
                  <span class="mx-3 text-muted small fw-bold">O</span>
                  <hr class="flex-grow-1">
                </div>

                <!-- Formulario -->
                <form method="POST" action="{{ route('login') }}">
                  @csrf
                  
                  <div class="mb-3">
                    <label for="inputEmailAddress" class="form-label fw-semibold">Correo Electrónico</label>
                    <input type="email" 
                           class="form-control form-control-lg @error('email') is-invalid @enderror" 
                           id="inputEmailAddress" 
                           name="email" 
                           value="{{ old('email') }}" 
                           placeholder="nombre@ejemplo.com">
                    @error('email')
                        <span class="invalid-feedback" role="alert">
                          <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                  </div>

                  <div class="mb-3">
                    <label for="inputChoosePassword" class="form-label fw-semibold">Contraseña</label>
                    <div class="input-group">
                      <input type="password" 
                             class="form-control form-control-lg @error('password') is-invalid @enderror" 
                             id="inputChoosePassword" 
                             name="password" 
                             placeholder="Ingresa tu contraseña">
                      <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                        <i class="bi bi-eye-slash"></i>
                      </button>
                    </div>
                    @error('password')
                        <span class="invalid-feedback" role="alert">
                          <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                  </div>

                  <div class="row mb-4">
                    <div class="col-6">
                      <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="remember" name="remember" {{ old('remember') ? 'checked' : '' }}>
                        <label class="form-check-label" for="remember">Recordarme</label>
                      </div>
                    </div>
                    <div class="col-6 text-end">
                      <a href="{{ route('password.request') }}" class="text-decoration-none">¿Olvidaste tu contraseña?</a>
                    </div>
                  </div>

                  <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg fw-semibold py-2">
                      Iniciar Sesión
                    </button>
                  </div>
                </form>

                <!-- Registro -->
                <div class="text-center mt-4">
                  <p class="small text-muted mb-0">¿No tienes una cuenta? 
                    <a href="{{ route('register') }}" class="text-decoration-none fw-semibold">Regístrate aquí</a>
                  </p>
                </div>
              </div>
            </div>

            <!-- Lado derecho - Hero Image -->
            <div class="col-lg-6 order-lg-2 order-1 d-none d-lg-flex align-items-center justify-content-center" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
              <div class="text-center p-5">
                <img src="{{ URL::asset('build/images/auth/loginoriginal.png') }}" class="img-fluid mb-4" style="max-width: 80%;" alt="Ilustración">
                <h4 class="text-white fw-bold mb-3">¡Transforma tu experiencia!</h4>
                <p class="text-white-50 mb-0">Accede a todas las herramientas y recursos que necesitas para potenciar tu negocio.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="{{ URL::asset('build/js/login/ver.js') }}"></script>

@endsection