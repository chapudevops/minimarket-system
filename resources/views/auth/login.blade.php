@extends('layouts.auth')

@section('title', 'Iniciar Sesión')

@section('content')


<div class="mx-3 mx-lg-0">

  <div class="card my-5 col-xl-9 col-xxl-8 mx-auto rounded-4 overflow-hidden p-4">
    <div class="row g-4">
      <div class="col-lg-6 d-flex">
        <div class="card-body">
          <img src="{{ URL::asset('build/images/logo1.png') }}" class="mb-4" width="145" alt="">
          <h4 class="fw-bold">Comienza Ahora</h4>
          <p class="mb-0">Ingresa tus credenciales para acceder a tu cuenta</p>
          <div class="row gy-2 gx-0 my-4">
            <div class="col-12 col-lg-12">
              <button class="btn btn-filter py-2 px-4 font-text1 fw-bold d-flex align-items-center justify-content-center w-100">
                <span class="auth-social-login"><img src="{{ URL::asset('build/images/apps/05.png') }}" width="20" class="me-2" alt="">Google</span>
              </button>
            </div>
            <div class="col-12 col-lg-12">
              <button class="btn btn-filter py-2 px-4 font-text1 fw-bold d-flex align-items-center justify-content-center w-100">
                <span class="auth-social-login"><img src="{{ URL::asset('build/images/apps/17.png') }}" width="20" class="me-2" alt="">Facebook</span>
              </button>
            </div>
            <div class="col-12 col-lg-12">
              <button class="btn btn-filter py-2 px-4 font-text1 fw-bold d-flex align-items-center justify-content-center w-100">
                <span class="auth-social-login"><img src="{{ URL::asset('build/images/apps/18.png') }}" width="20" class="me-2" alt="">LinkedIn</span>
              </button>
            </div>
          </div>

          <div class="separator">
            <div class="line"></div>
            <p class="mb-0 fw-bold">O</p>
            <div class="line"></div>
          </div>
          <div class="form-body mt-4">
            <form class="row g-3" method="POST" action="{{ route('login') }}">
            @csrf
              <div class="col-12">
                <label for="inputEmailAddress" class="form-label">Correo Electrónico</label>
                <input type="email" class="form-control @error('email') is-invalid @enderror" id="inputEmailAddress" name="email" value="{{ old('email') }}" placeholder="Ingresa tu correo electrónico">
                @error('email')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
              </div>
              <div class="col-12">
                <label for="inputChoosePassword" class="form-label">Contraseña</label>
                <div class="input-group" id="show_hide_password">
                  <input type="password" class="form-control border-end-0 @error('password') is-invalid @enderror" id="inputChoosePassword" name="password" placeholder="Ingresa tu contraseña">

                  <a href="javascript:;" class="input-group-text bg-transparent"><i
                      class="bi bi-eye-slash-fill"></i></a>
                    @error('password')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" id="flexSwitchCheckChecked" name="remember" {{ old('remember') ? 'checked' : '' }}>
                  <label class="form-check-label" for="flexSwitchCheckChecked">Recordarme</label>
                </div>
              </div>
              <div class="col-md-6 text-end"> <a href="{{ route('password.request') }}">¿Olvidaste tu contraseña?</a>
              </div>
              <div class="col-12">
                <div class="d-grid">
                  <button type="submit" class="btn btn-primary">Iniciar Sesión</button>
                </div>
              </div>
              <div class="col-12">
                <div class="text-start">
                  <p class="mb-0">¿Aún no tienes una cuenta? <a href="{{ route('register') }}">Regístrate aquí</a>
                  </p>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
      <div class="col-lg-6 d-lg-flex d-none">
        <div class="p-3 rounded-4 w-100 d-flex align-items-center justify-content-center bg-light">
          <img src="{{ URL::asset('build/images/auth/loginoriginal.png') }}" class="img-fluid" alt="">
        </div>
      </div>

    </div><!--end row-->
  </div>

</div>

@endsection