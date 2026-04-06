@extends('layouts.master')

@section('title', 'Arqueo de Cajas')
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
                            <i class="bi bi-cash-stack"></i> Arqueo de Cajas
                        </h4>
                        <p class="mb-0 text-muted small">Administra la apertura y cierre de cajas</p>
                    </div>
                    <div class="col text-end">
                        <button type="button" class="btn btn-primary" id="btnAbrirCaja">
                            <i class="bi bi-plus-circle"></i> Abrir Caja
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div id="alert-messages"></div>

                <div class="table-responsive">
                    <table id="cajasTable" class="table table-striped table-bordered" style="width:100%">
                        <thead>
                            <tr>
                                <th width="5%">#</th>
                                <th width="10%">Fecha</th>
                                <th width="20%">Responsable</th>
                                <th width="15%">Monto apertura</th>
                                <th width="10%">Estado</th>
                                <th width="20%">Acciones</th>
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
@include('apertura-caja.partials.modal-abrir')
@include('apertura-caja.partials.modal-cerrar')

@endsection 

@section('scripts')  
<script src="{{ URL::asset('build/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ URL::asset('build/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
<script src="{{ URL::asset('build/js/apertura-caja/config.js')}}"></script>
@endsection