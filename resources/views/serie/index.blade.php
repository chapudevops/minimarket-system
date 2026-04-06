@extends('layouts.master')

@section('title', 'Series')
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
                            <i class="bi bi-hash"></i> Gestión de Series
                        </h4>
                        <p class="mb-0 text-muted small">Administra las series para tus comprobantes</p>
                    </div>
                    <div class="col text-end">
                        <button type="button" class="btn btn-primary" id="btnNuevaSerie">
                            <i class="bi bi-plus-circle"></i> Nueva Serie
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div id="alert-messages"></div>

                <div class="table-responsive">
                    <table id="seriesTable" class="table table-striped table-bordered" style="width:100%">
                        <thead>
                            <tr>
                                <th width="5%">#</th>
                                <th width="15%">Serie</th>
                                <th width="20%">Correlativo</th>
                                <th width="25%">Comprobante</th>
                                <th width="20%">Caja</th>
                                <th width="15%">Acciones</th>
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
@include('serie.partials.modal-create')
@include('serie.partials.modal-edit')
@include('serie.partials.modal-delete')

@endsection 

@section('scripts')  
<script src="{{ URL::asset('build/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ URL::asset('build/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
<script src="{{ URL::asset('build/js/serie/config.js')}}"></script>
@endsection