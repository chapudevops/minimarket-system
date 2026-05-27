@extends('layouts.master')

@section('title', 'Combos de Productos')
@section('css')
    <link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
    <style>
        .combo-badge {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        .ahorro-badge {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
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
                            <i class="bi bi-gift"></i> Combos de Productos
                        </h4>
                        <p class="mb-0 text-muted small">Crea y administra combos de productos con precios especiales</p>
                    </div>
                    <div class="col text-end">
                        <a href="{{ route('combos.create') }}" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Nuevo Combo
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle"></i> {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle"></i> {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                
                <div id="alert-messages"></div>

                <div class="table-responsive">
                    <table id="combosTable" class="table table-striped table-bordered" style="width:100%">
                        <thead>
                            <tr>
                                <th width="3%">#</th>
                                <th width="5%">Foto</th>
                                <th width="18%">Nombre</th>
                                <th width="12%">Descripción</th>
                                <th width="10%">Precio Combo</th>
                                <th width="10%">Precio Regular</th>
                                <th width="8%">Ahorro</th>
                                <th width="6%">Dcto</th>
                                <th width="5%">Prod.</th>
                                <th width="7%">Estado</th>
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
<!--Modales-->
@include('combo.partials.modal-delete')
@include('combo.partials.modal-view')
@endsection 
@section('scripts')  
<script src="{{ URL::asset('build/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ URL::asset('build/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
<script src="{{ URL::asset('build/js/combo/config.js')}}"></script>
@endsection
