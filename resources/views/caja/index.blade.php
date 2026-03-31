@extends('layouts.master')

@section('title', 'Cajas')
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
                            <i class="bi bi-cash-stack"></i> Listado de Cajas
                        </h4>
                        <p class="mb-0 text-muted small">Administra las cajas de tu minimarket</p>
                    </div>
                    <div class="col text-end">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCreate">
                            <i class="bi bi-plus-circle"></i> Nueva Caja
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <!-- Preloader -->
                @include('caja.partials.preloader')
                
                <!-- Alertas -->
                <div id="alert-messages"></div>

                <!-- Tabla de Cajas -->
                @include('caja.partials.table')
            </div>
        </div>
    </div>
</div>

<!-- Modales -->
@include('caja.partials.modal-create')
@include('caja.partials.modal-edit')
@include('caja.partials.modal-delete')

@endsection 

@section('scripts')  
<script src="{{ URL::asset('build/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ URL::asset('build/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
<script src="{{ URL::asset('build/js/caja/config.js') }}"></script>

@endsection