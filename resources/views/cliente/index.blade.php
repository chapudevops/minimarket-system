@extends('layouts.master')

@section('title', 'Clientes')
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
                            <i class="bi bi-people"></i> Listado de Clientes
                        </h4>
                        <p class="mb-0 text-muted small">Administra los clientes de tu minimarket</p>
                    </div>
                    <div class="col text-end">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCreate">
                            <i class="bi bi-plus-circle"></i> Nuevo Cliente
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <!-- Preloader -->
                <div id="preloader-table" class="text-center py-5" style="display: none;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="mt-2">Cargando datos...</p>
                </div>

                <!-- Alertas -->
                <div id="alert-messages"></div>

                <div class="table-responsive">
                    <table id="clientesTable" class="table table-striped table-bordered" style="width:100%">
                        <thead>
                            <tr>
                                <th width="3%">#</th>
                                <th width="8%">Tipo Doc.</th>
                                <th width="10%">N° Documento</th>
                                <th width="20%">Nombre/Razón Social</th>
                                <th width="10%">Teléfono</th>
                                <th width="10%">Departamento</th>
                                <th width="8%">Estado</th>
                                <th width="10%">Fecha Creación</th>
                                <th width="8%">Acciones</th>
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
@include('cliente.partials.modal-create')
@include('cliente.partials.modal-edit')
@include('cliente.partials.modal-delete')

@endsection 

@section('scripts')  
<script src="{{ URL::asset('build/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ URL::asset('build/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
<script src="{{ URL::asset('build/js/cliente/config.js') }}"></script>
@endsection