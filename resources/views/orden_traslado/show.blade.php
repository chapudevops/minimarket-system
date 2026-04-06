@extends('layouts.master')

@section('title', 'Detalle de Orden de Traslado')
@section('css')
    <style>
        .info-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .info-label {
            font-weight: 600;
            color: #6c757d;
            font-size: 0.8rem;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .info-value {
            font-size: 1rem;
            color: #2c3e50;
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
                            <i class="bi bi-file-text"></i> Orden de Traslado
                        </h4>
                        <p class="mb-0 text-muted small">Detalle de la orden de traslado</p>
                    </div>
                    <div class="col text-end">
                        <a href="{{ route('traslados.index') }}" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="info-card">
                            <div class="info-label">Documento</div>
                            <div class="info-value"><strong>{{ $orden->documento }}</strong></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-card">
                            <div class="info-label">Fecha de Emisión</div>
                            <div class="info-value">{{ $orden->fecha_emision->format('d/m/Y') }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-card">
                            <div class="info-label">Fecha de Vencimiento</div>
                            <div class="info-value">{{ $orden->fecha_vencimiento->format('d/m/Y') }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-card">
                            <div class="info-label">Estado</div>
                            <div class="info-value">{!! $orden->estado_badge !!}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-card">
                            <div class="info-label">Almacén Despacho (Origen)</div>
                            <div class="info-value">{{ $orden->almacenOrigen->descripcion ?? '-' }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-card">
                            <div class="info-label">Almacén Destino (Receptor)</div>
                            <div class="info-value">{{ $orden->almacenDestino->descripcion ?? '-' }}</div>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="info-card">
                            <div class="info-label">Observaciones</div>
                            <div class="info-value">{{ $orden->observaciones ?? 'Sin observaciones' }}</div>
                        </div>
                    </div>
                </div>

                <h6 class="fw-bold mt-3 mb-3">
                    <i class="bi bi-box-seam"></i> Productos a Trasladar
                </h6>

                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Código</th>
                                <th>Descripción</th>
                                <th>Unidad</th>
                                <th>Cantidad</th>
                                <th>Precio Unitario</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($orden->detalles as $index => $detalle)
                            <tr>
                                <td class="text-center">{{ $loop->iteration }}</td>
                                <td>{{ $detalle->producto->codigo_interno }}</td>
                                <td>{{ $detalle->producto->descripcion }}</td>
                                <td>{{ $detalle->producto->unidad }}</td>
                                <td class="text-center">{{ $detalle->cantidad }}</td>
                                <td class="text-end">S/ {{ number_format($detalle->precio_unitario, 2) }}</td>
                                <td class="text-end">S/ {{ number_format($detalle->subtotal, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="6" class="text-end fw-bold">TOTAL:</td>
                                <td class="text-end fw-bold">S/ {{ number_format($orden->detalles->sum('subtotal'), 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <hr>

                <div class="row">
                    <div class="col-md-4">
                        <div class="info-card">
                            <div class="info-label">Creado por</div>
                            <div class="info-value">{{ $orden->creador->name ?? '-' }}</div>
                            <div class="info-value small text-muted">{{ $orden->created_at ? $orden->created_at->format('d/m/Y H:i') : '-' }}</div>
                        </div>
                    </div>
                    @if($orden->aprobador)
                    <div class="col-md-4">
                        <div class="info-card">
                            <div class="info-label">Aprobado por</div>
                            <div class="info-value">{{ $orden->aprobador->name ?? '-' }}</div>
                            <div class="info-value small text-muted">{{ $orden->fecha_aprobacion ? $orden->fecha_aprobacion->format('d/m/Y H:i') : '-' }}</div>
                        </div>
                    </div>
                    @endif
                    @if($orden->anulador)
                    <div class="col-md-4">
                        <div class="info-card">
                            <div class="info-label">Anulado por</div>
                            <div class="info-value">{{ $orden->anulador->name ?? '-' }}</div>
                            <div class="info-value small text-muted">{{ $orden->fecha_anulacion ? $orden->fecha_anulacion->format('d/m/Y H:i') : '-' }}</div>
                            <div class="info-value small text-danger">Motivo: {{ $orden->motivo_anulacion }}</div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection