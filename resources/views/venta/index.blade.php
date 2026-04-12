@extends('layouts.master')

@section('title', 'Gestión de Boletas y Facturas')
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
                            <i class="bi bi-receipt"></i> Gestión de Boletas y Facturas
                        </h4>
                        <p class="mb-0 text-muted small">Administra los comprobantes de venta</p>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div id="alert-messages"></div>

                <div class="table-responsive">
                    <table id="ventasTable" class="table table-striped table-bordered" style="width:100%">
                        <thead>
                            <tr>
                                <th width="8%">Documento</th>
                                <th width="12%">Fecha</th>
                                <th width="8%">RUC/DNI</th>
                                <th width="20%">Cliente</th>
                                <th width="8%">Total</th>
                                <th width="8%">XML</th>
                                <th width="8%">CDR</th>
                                <th width="8%">SUNAT</th>
                                <th width="8%">Comprobante</th>
                                <th width="12%">Acciones</th>
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

<!-- Modal Ver Detalles -->
<div class="modal fade" id="modalView" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title text-white">
                    <i class="bi bi-info-circle"></i> Detalle de Venta
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="ventaDetails">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="mt-2">Cargando detalles...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-danger btn-anular" id="btnAnularVenta" style="display: none;">
                    <i class="bi bi-x-circle"></i> Anular Venta
                </button>
            </div>
        </div>
    </div>
</div>

@endsection 

@section('scripts')  
<script src="{{ URL::asset('build/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ URL::asset('build/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
<script src="{{ URL::asset('build/js/venta/config.js')}}"></script>
@endsection