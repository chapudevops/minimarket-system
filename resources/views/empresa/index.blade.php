@extends('layouts.master')

@section('title', 'Configuración de la Empresa')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h4 class="mb-0">
                            <i class="bi bi-building"></i> Configuración de la Empresa
                        </h4>
                        <p class="mb-0 text-muted small">Administra los datos de tu empresa para la facturación electrónica</p>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <!-- Alertas dinámicas con AJAX -->
                <!-- <div id="alert-messages"></div> -->

                <form id="empresaForm" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" id="empresaId" name="id" value="{{ $empresa->id }}">
                    
                    <!-- Datos de la Empresa -->
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="bi bi-info-circle"></i> Datos de la Empresa</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">RUC <span class="text-danger">*</span></label>
                                    <input type="text" name="ruc" id="ruc" class="form-control" 
                                           value="{{ $empresa->ruc }}" maxlength="11" required>
                                    <div class="invalid-feedback" id="error-ruc"></div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Razón Social <span class="text-danger">*</span></label>
                                    <input type="text" name="razon_social" id="razon_social" class="form-control" 
                                           value="{{ $empresa->razon_social }}" required>
                                    <div class="invalid-feedback" id="error-razon_social"></div>
                                </div>
                                
                                <div class="col-12 mb-3">
                                    <label class="form-label fw-bold">Dirección <span class="text-danger">*</span></label>
                                    <textarea name="direccion" id="direccion" class="form-control" rows="2" required>{{ $empresa->direccion }}</textarea>
                                    <div class="invalid-feedback" id="error-direccion"></div>
                                </div>
                                
                                <div class="col-md-3 mb-3">
                                    <label class="form-label fw-bold">País</label>
                                    <input type="text" name="pais" id="pais" class="form-control" value="{{ $empresa->pais }}">
                                </div>
                                
                                <div class="col-md-3 mb-3">
                                    <label class="form-label fw-bold">Departamento</label>
                                    <input type="text" name="departamento" id="departamento" class="form-control" value="{{ $empresa->departamento }}">
                                </div>
                                
                                <div class="col-md-3 mb-3">
                                    <label class="form-label fw-bold">Provincia</label>
                                    <input type="text" name="provincia" id="provincia" class="form-control" value="{{ $empresa->provincia }}">
                                </div>
                                
                                <div class="col-md-3 mb-3">
                                    <label class="form-label fw-bold">Distrito</label>
                                    <input type="text" name="distrito" id="distrito" class="form-control" value="{{ $empresa->distrito }}">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">URL API</label>
                                    <input type="url" name="url_api" id="url_api" class="form-control" value="{{ $empresa->url_api }}">
                                    <small class="text-muted">URL para facturación electrónica</small>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Contacto Contabilidad</label>
                                    <input type="email" name="email_contabilidad" id="email_contabilidad" class="form-control" 
                                           value="{{ $empresa->email_contabilidad }}">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Cuenta Detracciones -->
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="bi bi-bank"></i> Cuenta Banco de la Nación (Detracciones)</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label class="form-label fw-bold">Número de Cuenta</label>
                                    <input type="text" name="cuenta_bancaria_detracciones" id="cuenta_bancaria_detracciones" class="form-control" 
                                           value="{{ $empresa->cuenta_bancaria_detracciones }}">
                                    <small class="text-muted">Necesario para que se imprima/envíe en el XML de las facturas sujetas a detracción</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Logo de la Empresa -->
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="bi bi-image"></i> Logo de la Empresa</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <div id="logo-preview">
                                        @if($empresa->logo)
                                            <div class="mb-3" id="logo-actual">
                                                <label class="form-label fw-bold">Logo actual</label>
                                                <div>
                                                    <img src="{{ asset('storage/empresa/' . $empresa->logo) }}" 
                                                         alt="Logo" 
                                                         id="logo-img"
                                                         width="150" 
                                                         height="150" 
                                                         class="border rounded p-2"
                                                         style="object-fit: contain;">
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                    
                                    <label class="form-label fw-bold">Cambiar logo</label>
                                    <input type="file" name="logo" id="logo" class="form-control" accept="image/*">
                                    <small class="text-muted">JPG, PNG, GIF - Máx. 2MB</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Usuario SUNAT -->
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="bi bi-cloud-sun"></i> Usuario SUNAT</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label class="form-label fw-bold">Nombre Comercial</label>
                                    <input type="text" name="nombre_comercial" id="nombre_comercial" class="form-control" 
                                           value="{{ $empresa->nombre_comercial }}">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Usuario Secundario</label>
                                    <input type="text" name="usuario_secundario" id="usuario_secundario" class="form-control" 
                                           value="{{ $empresa->usuario_secundario }}">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Clave</label>
                                    <input type="text" name="clave" id="clave" class="form-control" 
                                           value="{{ $empresa->clave }}">
                                </div>
                                
                                <div class="col-md-12 mb-3">
                                    <label class="form-label fw-bold">Clave Certificado</label>
                                    <input type="text" name="clave_certificado" id="clave_certificado" class="form-control" 
                                           value="{{ $empresa->clave_certificado }}">
                                </div>
                                
                                <div class="col-md-12 mb-3">
                                    <label class="form-label fw-bold">Certificado (.pfx)</label>
                                    <div id="certificado-info">
                                        @if($empresa->certificado_pfx)
                                            <div class="mb-2">
                                                <span class="badge bg-success" id="certificado-badge">
                                                    <i class="bi bi-file-check"></i> Certificado cargado: {{ $empresa->certificado_pfx }}
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                    <input type="file" name="certificado_pfx" id="certificado_pfx" class="form-control" accept=".pfx">
                                    <small class="text-muted">Archivo PFX - Máx. 5MB</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Credenciales API REST -->
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="bi bi-api"></i> Credenciales API REST (Guías de Remisión)</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Client ID</label>
                                    <input type="text" name="client_id" id="client_id" class="form-control" 
                                           value="{{ $empresa->client_id }}">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Client Secret</label>
                                    <input type="text" name="client_secret" id="client_secret" class="form-control" 
                                           value="{{ $empresa->client_secret }}">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Servidor Sunat</label>
                                    <select name="servidor_sunat" id="servidor_sunat" class="form-control">
                                        <option value="beta" {{ $empresa->servidor_sunat == 'beta' ? 'selected' : '' }}>
                                            Beta (Pruebas)
                                        </option>
                                        <option value="produccion" {{ $empresa->servidor_sunat == 'produccion' ? 'selected' : '' }}>
                                            Producción
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Botón Guardar -->
                    <div class="text-end">
                        <button type="button" class="btn btn-primary btn-lg" id="btnGuardar">
                            <i class="bi bi-save"></i> Guardar Cambios
                        </button>
                        <button type="button" class="btn btn-primary btn-lg" id="btnLoading" style="display: none;" disabled>
                            <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                            Guardando...
                        </button>
                    </div>
                    
                </form>
            </div>
        </div>
    </div>
</div>


<script src="{{ URL::asset('build/js/empresa/config.js') }}"></script>
@endsection