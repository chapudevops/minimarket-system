@extends('layouts.master')

@section('title', 'Gestión de Gastos')
@section('css')
    <link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
@endsection 

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h4 class="mb-0">
                            <i class="bi bi-receipt"></i> Gestión de Gastos
                        </h4>
                        <p class="mb-0 text-muted small">Administra los gastos del negocio</p>
                    </div>
                    <div class="col text-end">
                        <button type="button" class="btn btn-primary" id="btnNuevoGasto">
                            <i class="bi bi-plus-circle"></i> Nuevo Gasto
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div id="alert-messages"></div>

                <div class="table-responsive">
                    <table id="gastosTable" class="table table-striped table-bordered" style="width:100%">
                        <thead>
                            <tr>
                                <th width="5%">#</th>
                                <th width="10%">Fecha</th>
                                <th width="15%">Cuenta</th>
                                <th width="25%">Descripción</th>
                                <th width="10%">Total S/</th>
                                <th width="15%">Usuario</th>
                                <th width="10%">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Los datos se cargarán vía AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modales -->
@include('gasto.partials.modal-create')
@include('gasto.partials.modal-delete')

@endsection 

@section('scripts')  
<script src="{{ URL::asset('build/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ URL::asset('build/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
<script src="{{ URL::asset('build/js/gasto/config.js')}}"></script>
@endsection