@extends('layouts.master')

@section('title', 'Editar Usuario')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h4 class="mb-0">
                            <i class="bi bi-pencil-square"></i> Editar Usuario
                        </h4>
                        <p class="mb-0 text-muted small">Edita la información del usuario</p>
                    </div>
                    <div class="col text-end">
                        <a href="{{ route('usuarios.index') }}" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle"></i> {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <form action="{{ route('usuarios.update', $usuario->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Nombres <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $usuario->name) }}" required>
                            @error('name')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Correo Electrónico <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $usuario->email) }}" required>
                            @error('email')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Nueva Contraseña</label>
                            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror">
                            <small class="text-muted">Dejar en blanco para mantener la contraseña actual</small>
                            @error('password')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Confirmar Contraseña</label>
                            <input type="password" name="password_confirmation" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Asignar Caja</label>
                            <select name="caja_id" class="form-control @error('caja_id') is-invalid @enderror">
                                <option value="">Seleccionar caja</option>
                                @foreach($cajas as $caja)
                                    <option value="{{ $caja->id }}" {{ old('caja_id', $usuario->caja_id) == $caja->id ? 'selected' : '' }}>
                                        {{ $caja->nombre }}
                                    </option>
                                @endforeach
                            </select>
                            @error('caja_id')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Asignar Almacén</label>
                            <select name="almacen_id" class="form-control @error('almacen_id') is-invalid @enderror">
                                <option value="">Seleccionar almacén</option>
                                @foreach($almacenes as $almacen)
                                    <option value="{{ $almacen->id }}" {{ old('almacen_id', $usuario->almacen_id) == $almacen->id ? 'selected' : '' }}>
                                        {{ $almacen->descripcion }}
                                    </option>
                                @endforeach
                            </select>
                            @error('almacen_id')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">Roles <span class="text-danger">*</span></label>
                            <div class="row">
                                @foreach($roles as $role)
                                    <div class="col-md-3">
                                        <div class="form-check">
                                            <input type="checkbox" name="roles[]" class="form-check-input" value="{{ $role->id }}" id="role_{{ $role->id }}" {{ in_array($role->id, old('roles', $usuario->roles->pluck('id')->toArray())) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="role_{{ $role->id }}">
                                                <strong>{{ ucfirst($role->nombre) }}</strong>
                                                <br>
                                                <small class="text-muted">{{ $role->descripcion }}</small>
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            @error('roles')
                                <span class="text-danger small">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-12 mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="estado" class="form-check-input" id="estado" value="1" {{ old('estado', $usuario->estado) ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold" for="estado">Activo</label>
                            </div>
                        </div>
                    </div>
                    <div class="text-end">
                        <a href="{{ route('usuarios.index') }}" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Actualizar Usuario
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection