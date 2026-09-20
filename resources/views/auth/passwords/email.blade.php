

@extends('layouts.auth')

@section('title', 'Recuperar Contraseña')

@section('content')

<div class="min-vh-100 d-flex align-items-center justify-content-center py-5" style="background: linear-gradient(135deg, #002254 0%, #043a82 50%, #0b51ad 100%);">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-xl-10 col-xxl-8">
        <div class="card border-0 shadow-lg overflow-hidden" style="border-radius: 2rem;">
          <div class="row g-0">
            <div class="col-lg-6 d-flex">
              <div class="p-4 p-md-5 w-100">
                <div class="text-center mb-4">
                  <img src="{{ \App\Marca::logo() }}" class="mb-3" width="120" alt="">
                  <h4 class="fw-bold">¿Olvidaste tu contraseña?</h4>
                  <p class="text-muted">Ingresa tu correo electrónico y te enviaremos un enlace para restablecerla</p>
                </div>

                @if (session('status'))
                <div class="alert alert-success alert-dismissible fade show d-flex align-items-center" role="alert">
                  <i class="bi bi-check-circle-fill me-2"></i>
                  {{ session('status') }}
                  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                @endif

                <form method="POST" action="{{ route('password.email') }}" id="forgotForm">
                  @csrf
                  <div class="mb-3">
                    <label class="form-label fw-semibold">Correo Electrónico</label>
                    <div class="input-group">
                      <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                      <input type="email" class="form-control form-control-lg @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" placeholder="nombre@ejemplo.com">
                    </div>
                    @error('email')
                      <span class="invalid-feedback d-block" role="alert">
                        <strong>{{ $message }}</strong>
                      </span>
                    @enderror
                  </div>
                  <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg fw-semibold py-2" id="btnSend">
                      <span id="btnSendText">Enviar Enlace</span>
                      <span id="btnSendSpinner" class="d-none">
                        <span class="spinner-border spinner-border-sm me-2"></span>
                        Enviando...
                      </span>
                    </button>
                    <a href="{{ route('login') }}" class="btn btn-outline-secondary py-2">
                      <i class="bi bi-arrow-left me-2"></i>Volver al Inicio de Sesión
                    </a>
                  </div>
                </form>
              </div>
            </div>
            <div class="col-lg-6 d-none d-lg-flex align-items-center justify-content-center" style="background: linear-gradient(135deg, #002254 0%, #0b51ad 100%);">
              <div class="text-center p-5">
                <img src="{{ \App\Marca::logo() }}" class="img-fluid mb-4" style="max-width: 80%;" alt="">
                <h4 class="text-white fw-bold mb-3">Recupera tu acceso</h4>
                <p class="text-white-50 mb-0">Te enviaremos un enlace seguro para que puedas crear una nueva contraseña.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.getElementById('forgotForm')?.addEventListener('submit', function() {
    var btn = document.getElementById('btnSend');
    var text = document.getElementById('btnSendText');
    var spinner = document.getElementById('btnSendSpinner');
    btn.disabled = true;
    text.classList.add('d-none');
    spinner.classList.remove('d-none');
});
</script>

@endsection

