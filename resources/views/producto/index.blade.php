@extends('layouts.master')

@section('title', 'Productos')
@section('css')
    <link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
    <style>
        .table-almacenes-stock {
            width: 100%;
            margin-top: 10px;
        }
        .table-almacenes-stock th, .table-almacenes-stock td {
            padding: 8px;
            border-bottom: 1px solid #dee2e6;
        }
        .stock-input {
            width: 100px;
            text-align: center;
        }
        .add-almacen-btn {
            cursor: pointer;
            color: #0d6efd;
            text-decoration: underline;
        }
        .remove-almacen-row {
            cursor: pointer;
            color: #dc3545;
        }
        .nav-tabs .nav-link {
            color: #6c757d;
            font-weight: 500;
        }
        .nav-tabs .nav-link.active {
            color: #0d6efd;
            border-bottom: 2px solid #0d6efd;
        }
        .tab-pane {
            padding-top: 20px;
        }
    </style>
@endsection 

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h4 class="mb-0">
                            <i class="bi bi-box-seam"></i> Listado de Productos
                        </h4>
                        <p class="mb-0 text-muted small">Administra los productos de tu minimarket</p>
                    </div>
                    <div class="col text-end">
                        <button type="button" class="btn btn-primary" id="btnNuevoProducto">
                            <i class="bi bi-plus-circle"></i> Nuevo Producto
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div id="alert-messages"></div>

                <div class="table-responsive">
                    <table id="productosTable" class="table table-striped table-bordered" style="width:100%">
                        <thead>
                            <tr>
                                <th width="3%">#</th>
                                <th width="10%">Código Interno</th>
                                <th width="25%">Descripción</th>
                                <th width="8%">Unidad</th>
                                <th width="10%">Precio Venta</th>
                                <th width="8%">Stock Total</th>
                                <th width="8%">Estado</th>
                                <th width="10%">Fecha Creación</th>
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
<!--Modales-->
@include('producto.partials.modal-create')
@include('producto.partials.modal-delete')
@include('producto.partials.modal-view')
@endsection 
@section('scripts')  
<script src="{{ URL::asset('build/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ URL::asset('build/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
<script src="{{ URL::asset('build/js/producto/config.js')}}"></script>
@endsection