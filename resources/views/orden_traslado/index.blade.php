@extends('layouts.master')

@section('title', 'Órdenes de Traslado')
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
                            <i class="bi bi-arrow-left-right"></i> Gestión de Órdenes de Traslados
                        </h4>
                        <p class="mb-0 text-muted small">Administra los traslados de productos entre almacenes</p>
                    </div>
                    <div class="col text-end">
                        <a href="{{ route('traslados.create') }}" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Nueva Orden de Traslado
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

                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-striped">
                        <thead class="table-light">
                            <tr>
                                <th width="5%">#</th>
                                <th width="12%">Documento</th>
                                <th width="10%">Fecha</th>
                                <th width="15%">Almacén Despacho</th>
                                <th width="15%">Almacén Receptor</th>
                                <th width="10%">Estado</th>
                                <th width="8%">Creado por</th>
                                <th width="15%">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($ordenes as $orden)
                            <tr>
                                <td class="text-center">{{ $loop->iteration }}</td>
                                <td><strong>{{ $orden->documento }}</strong></td>
                                <td>{{ $orden->fecha_emision->format('d/m/Y') }}</td>
                                <td>{{ $orden->almacenOrigen->descripcion ?? '-' }}</td>
                                <td>{{ $orden->almacenDestino->descripcion ?? '-' }}</td>
                                <td>{!! $orden->estado_badge !!}</td>
                                <td>{{ $orden->creador->name ?? '-' }}</td>
                                <td class="text-center">
                                    <a href="{{ route('traslados.show', $orden->id) }}" class="btn btn-sm btn-info" title="Ver detalles">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @if($orden->estado == 'PENDIENTE')
                                        <button type="button" class="btn btn-sm btn-success btn-aprobar" 
                                                data-id="{{ $orden->id }}" 
                                                data-documento="{{ $orden->documento }}"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#modalAprobar">
                                            <i class="bi bi-check-circle"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-secondary btn-anular" 
                                                data-id="{{ $orden->id }}" 
                                                data-documento="{{ $orden->documento }}"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#modalAnular">
                                            <i class="bi bi-x-circle"></i>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center">
                                    <div class="py-5">
                                        <i class="bi bi-inbox display-1 text-muted"></i>
                                        <p class="mt-2">No hay órdenes de traslado registradas</p>
                                        <a href="{{ route('traslados.create') }}" class="btn btn-primary btn-sm">
                                            <i class="bi bi-plus-circle"></i> Crear primera orden
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $ordenes->links() }}
            </div>
        </div>
    </div>
</div>

<!-- Modal Aprobar -->
<div class="modal fade" id="modalAprobar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title text-white">
                    <i class="bi bi-check-circle"></i> Aprobar Orden de Traslado
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de aprobar esta orden de traslado?</p>
                <p class="fw-bold" id="aprobar-documento"></p>
                <p class="text-warning">
                    <i class="bi bi-exclamation-triangle"></i> Al aprobar, se descontará el stock del almacén de origen y se aumentará al almacén de destino.
                </p>
                <input type="hidden" id="aprobar_id">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="btnConfirmAprobar">
                    <i class="bi bi-check-circle"></i> Aprobar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Anular -->
<div class="modal fade" id="modalAnular" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-secondary text-white">
                <h5 class="modal-title text-white">
                    <i class="bi bi-x-circle"></i> Anular Orden de Traslado
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formAnular">
                @csrf
                <input type="hidden" id="anular_id" name="id">
                <div class="modal-body">
                    <p>¿Estás seguro de anular esta orden de traslado?</p>
                    <p class="fw-bold" id="anular-documento"></p>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Motivo de anulación <span class="text-danger">*</span></label>
                        <textarea name="motivo_anulacion" id="motivo_anulacion" class="form-control" rows="3" required placeholder="Ingrese el motivo de la anulación..."></textarea>
                        <div class="invalid-feedback">Debe especificar un motivo de anulación</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-secondary" id="btnConfirmAnular">
                        <i class="bi bi-x-circle"></i> Anular
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection 

@section('scripts')
<script>
$(document).ready(function() {
    // Aprobar orden
    $('.btn-aprobar').on('click', function() {
        var id = $(this).data('id');
        var documento = $(this).data('documento');
        
        $('#aprobar_id').val(id);
        $('#aprobar-documento').text(documento);
    });
    
    $('#btnConfirmAprobar').on('click', function() {
        var id = $('#aprobar_id').val();
        var url = '/traslados/' + id + '/aprobar';
        
        $.ajax({
            url: url,
            type: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                window.location.href = '{{ route("traslados.index") }}';
            },
            error: function(xhr) {
                alert(xhr.responseJSON?.message || 'Error al aprobar la orden');
            }
        });
    });
    
    // Anular orden
    $('.btn-anular').on('click', function() {
        var id = $(this).data('id');
        var documento = $(this).data('documento');
        
        $('#anular_id').val(id);
        $('#anular-documento').text(documento);
        $('#motivo_anulacion').val('');
        $('.is-invalid').removeClass('is-invalid');
    });
    
    $('#formAnular').on('submit', function(e) {
        e.preventDefault();
        
        var id = $('#anular_id').val();
        var motivo = $('#motivo_anulacion').val();
        var url = '/traslados/' + id + '/anular';
        
        if (!motivo.trim()) {
            $('#motivo_anulacion').addClass('is-invalid');
            return;
        }
        
        $.ajax({
            url: url,
            type: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                motivo_anulacion: motivo
            },
            success: function(response) {
                window.location.href = '{{ route("traslados.index") }}';
            },
            error: function(xhr) {
                alert(xhr.responseJSON?.message || 'Error al anular la orden');
            }
        });
    });
});
</script>
@endsection