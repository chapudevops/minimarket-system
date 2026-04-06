@extends('layouts.master')

@section('title', 'Compras')
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
                            <i class="bi bi-cart4"></i> Gestión de Compras
                        </h4>
                        <p class="mb-0 text-muted small">Administra las compras de mercadería</p>
                    </div>
                    <div class="col text-end">
                        <a href="{{ route('compras.create') }}" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Nueva Compra
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
                                <th width="10%">RUC</th>
                                <th width="20%">Proveedor</th>
                                <th width="10%">Total</th>
                                <th width="8%">Estado</th>
                                <th width="15%">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($compras as $compra)
                            <tr>
                                <td class="text-center">{{ $loop->iteration }}</td>
                                <td><strong>{{ $compra->documento }}</strong></td>
                                <td>{{ $compra->fecha_emision->format('d/m/Y') }}</td>
                                <td>{{ $compra->proveedor->numero_documento ?? '-' }}</td>
                                <td>{{ $compra->proveedor->nombre_razon_social ?? '-' }}</td>
                                <td class="text-end">S/ {{ number_format($compra->total, 2) }}</td>
                                <td class="text-center">{!! $compra->estado_badge !!}</td>
                                <td class="text-center">
                                    <a href="{{ route('compras.show', $compra->id) }}" class="btn btn-sm btn-info" title="Ver detalles">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                      </a>
                                    <a href="{{ route('compras.pdf', $compra->id) }}" class="btn btn-sm btn-danger" title="Descargar PDF" target="_blank">
                                        <i class="bi bi-file-pdf"></i>
                                    </a>
                                    <a href="{{ route('compras.imprimir', $compra->id) }}" class="btn btn-sm btn-primary" title="Imprimir" target="_blank">
                                        <i class="bi bi-printer"></i>
                                    </a>
                                    @if($compra->estado == 'REGISTRADA')
                                        <button type="button" class="btn btn-sm btn-secondary btn-anular" 
                                                data-id="{{ $compra->id }}" 
                                                data-documento="{{ $compra->documento }}"
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
                                        <p class="mt-2">No hay compras registradas</p>
                                        <a href="{{ route('compras.create') }}" class="btn btn-primary btn-sm">
                                            <i class="bi bi-plus-circle"></i> Registrar primera compra
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $compras->links() }}
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
                    <i class="bi bi-x-circle"></i> Anular Compra
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de anular esta compra?</p>
                <p class="fw-bold" id="anular-documento"></p>
                <p class="text-warning">
                    <i class="bi bi-exclamation-triangle"></i> Al anular, se devolverá el stock al almacén.
                </p>
                <input type="hidden" id="anular_id">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-secondary" id="btnConfirmAnular">
                    <i class="bi bi-x-circle"></i> Anular
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Anular compra
    $('.btn-anular').on('click', function() {
        var id = $(this).data('id');
        var documento = $(this).data('documento');
        
        $('#anular_id').val(id);
        $('#anular-documento').text(documento);
    });
    
    $('#btnConfirmAnular').on('click', function() {
        var id = $('#anular_id').val();
        var url = '/compras/' + id + '/anular';
        
        $.ajax({
            url: url,
            type: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                window.location.href = '{{ route("compras.index") }}';
            },
            error: function(xhr) {
                alert(xhr.responseJSON?.message || 'Error al anular la compra');
            }
        });
    });
});
</script>
@endsection