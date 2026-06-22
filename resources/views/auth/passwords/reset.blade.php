


@extends('layouts.auth')

@section('title', 'Restablecer Contraseña')

@section('content')

<div class="min-vh-100 d-flex align-items-center justify-content-center py-5" style="background: linear-gradient(135deg, #1e3a5f 0%, #2d6a9f 50%, #667eea 100%);">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-xl-10 col-xxl-8">
        <div class="card border-0 shadow-lg overflow-hidden" style="border-radius: 2rem;">
          <div class="row g-0">
            <div class="col-lg-6 d-flex">
              <div class="p-4 p-md-5 w-100">
                <div class="text-center mb-4">
                  <img src="{{ URL::asset('build/images/logo1.png') }}" class="mb-3" width="120" alt="">
                  <h4 class="fw-bold">Crear Nueva Contraseña</h4>
                  <p class="text-muted">Ingresa tu nueva contraseña para acceder al sistema</p>
                </div>

                @if(session('status'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                  {{ session('status') }}
                  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                @endif

                <form method="POST" action="{{ route('password.update') }}" id="resetForm">
                  @csrf
                  <input type="hidden" name="token" value="{{ $token }}">

                  <div class="mb-3">
                    <label class="form-label fw-semibold">Correo Electrónico</label>
                    <div class="input-group">
                      <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                      <input type="email" class="form-control form-control-lg @error('email') is-invalid @enderror" name="email" value="{{ $email ?? old('email') }}" placeholder="nombre@ejemplo.com" readonly>
                    </div>
                    @error('email')
                      <span class="invalid-feedback d-block" role="alert">
                        <strong>{{ $message }}</strong>
                      </span>
                    @enderror
                  </div>

                  <div class="mb-3">
                    <label class="form-label fw-semibold">Nueva Contraseña</label>
                    <div class="input-group">
                      <span class="input-group-text"><i class="bi bi-lock"></i></span>
                      <input type="password" class="form-control form-control-lg @error('password') is-invalid @enderror" name="password" id="newPassword" placeholder="Mínimo 8 caracteres">
                      <button class="btn btn-outline-secondary" type="button" id="toggleNewPassword">
                        <i class="bi bi-eye-slash"></i>
                      </button>
                    </div>
                    <div class="mt-2" id="passwordStrength">
                      <div class="progress" style="height: 4px;">
                        <div class="progress-bar" id="strengthBar" role="progressbar" style="width: 0%;"></div>
                      </div>
                      <small class="text-muted" id="strengthText">Ingresa una contraseña segura</small>
                    </div>
                    @error('password')
                      <span class="invalid-feedback d-block" role="alert">
                        <strong>{{ $message }}</strong>
                      </span>
                    @enderror
                  </div>

                  <div class="mb-3">
                    <label class="form-label fw-semibold">Confirmar Contraseña</label>
                    <div class="input-group">
                      <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                      <input type="password" class="form-control form-control-lg" name="password_confirmation" id="confirmPassword" placeholder="Repite la contraseña">
                      <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                        <i class="bi bi-eye-slash"></i>
                      </button>
                    </div>
                    <small class="text-muted" id="matchText"></small>
                  </div>

                  <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg fw-semibold py-2" id="btnReset">
                      <span id="btnResetText">Restablecer Contraseña</span>
                      <span id="btnResetSpinner" class="d-none">
                        <span class="spinner-border spinner-border-sm me-2"></span>
                        Procesando...
                      </span>
                    </button>
                    <a href="{{ route('login') }}" class="btn btn-outline-secondary py-2">
                      <i class="bi bi-arrow-left me-2"></i>Volver al Inicio de Sesión
                    </a>
                  </div>
                </form>
              </div>
            </div>
            <div class="col-lg-6 d-none d-lg-flex align-items-center justify-content-center" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
              <div class="text-center p-5">
                <img src="{{ URL::asset('build/images/auth/reset-password1.png') }}" class="img-fluid mb-4" style="max-width: 80%;" alt="">
                <h4 class="text-white fw-bold mb-3">Contraseña segura</h4>
                <p class="text-white-50 mb-0">Usa una combinación de letras, números y símbolos para proteger tu cuenta.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
// Password strength meter
document.getElementById('newPassword')?.addEventListener('input', function() {
    var val = this.value;
    var bar = document.getElementById('strengthBar');
    var text = document.getElementById('strengthText');
    var strength = 0;

    if (val.length >= 8) strength += 25;
    if (val.match(/[a-z]/) && val.match(/[A-Z]/)) strength += 25;
    if (val.match(/\d/)) strength += 25;
    if (val.match(/[^a-zA-Z0-9]/)) strength += 25;

    bar.style.width = strength + '%';
    if (strength < 25) {
        bar.className = 'progress-bar bg-danger';
        text.textContent = 'Muy débil';
        text.className = 'text-danger';
    } else if (strength < 50) {
        bar.className = 'progress-bar bg-warning';
        text.textContent = 'Débil';
        text.className = 'text-warning';
    } else if (strength < 75) {
        bar.className = 'progress-bar bg-info';
        text.textContent = 'Buena';
        text.className = 'text-info';
    } else {
        bar.className = 'progress-bar bg-success';
        text.textContent = 'Fuerte';
        text.className = 'text-success';
    }
});

// Confirm password match
document.getElementById('confirmPassword')?.addEventListener('input', function() {
    var newPwd = document.getElementById('newPassword').value;
    var matchText = document.getElementById('matchText');
    if (this.value === '') {
        matchText.textContent = '';
    } else if (this.value === newPwd) {
        matchText.textContent = '✓ Las contraseñas coinciden';
        matchText.className = 'text-success';
    } else {
        matchText.textContent = '✗ Las contraseñas no coinciden';
        matchText.className = 'text-danger';
    }
});

// Submit loading
document.getElementById('resetForm')?.addEventListener('submit', function() {
    var btn = document.getElementById('btnReset');
    var text = document.getElementById('btnResetText');
    var spinner = document.getElementById('btnResetSpinner');
    btn.disabled = true;
    text.classList.add('d-none');
    spinner.classList.remove('d-none');
});

// Toggle password visibility
['toggleNewPassword', 'toggleConfirmPassword'].forEach(function(id) {
    document.getElementById(id)?.addEventListener('click', function() {
        var targetId = id === 'toggleNewPassword' ? 'newPassword' : 'confirmPassword';
        var input = document.getElementById(targetId);
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
});
</script>

@endsection
