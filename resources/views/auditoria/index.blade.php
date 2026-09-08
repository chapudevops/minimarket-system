@extends('layouts.master')

@section('title', 'Auditoría')
@section('css')
    <link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
@endsection

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0"><i class="bi bi-clock-history"></i> Auditoría</h4>
                <p class="mb-0 text-muted small">Quién hizo qué, y cuándo</p>
            </div>
            <div class="card-body">
                <div id="alert-messages"></div>

                <div class="row g-2 mb-3">
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Usuario</label>
                        <select id="filtro_usuario" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            @foreach ($usuarios as $u)
                                <option value="{{ $u->id }}">{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold">Acción</label>
                        <select id="filtro_accion" class="form-select form-select-sm">
                            <option value="">Todas</option>
                            @foreach ($acciones as $a)
                                <option value="{{ $a }}">{{ ucfirst(strtolower($a)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Entidad</label>
                        <select id="filtro_entidad" class="form-select form-select-sm">
                            <option value="">Todas</option>
                            @foreach ($entidades as $e)
                                <option value="{{ $e }}">{{ $e }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold">Desde</label>
                        <input type="date" id="filtro_desde" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold">Hasta</label>
                        <input type="date" id="filtro_hasta" class="form-control form-control-sm">
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="auditoriaTable" class="table table-striped table-bordered" style="width:100%">
                        <thead>
                            <tr>
                                <th width="12%">Fecha</th>
                                <th width="12%">Usuario</th>
                                <th width="9%">Acción</th>
                                <th width="9%">Entidad</th>
                                <th width="26%">Descripción</th>
                                <th width="24%">Cambios</th>
                                <th width="8%">IP</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <p class="text-muted small mb-0 mt-2">
                    Se muestran los últimos 500 registros. Usá los filtros para acotar la búsqueda.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ URL::asset('build/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ URL::asset('build/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
<script src="{{ URL::asset('build/js/auditoria/config.js') }}"></script>
@endsection
