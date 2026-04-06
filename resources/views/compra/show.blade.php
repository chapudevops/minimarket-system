@extends('layouts.master')

@section('title', 'Detalle de Compra')
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
                            <i class="bi bi-file-text"></i> Detalle de Compra
                        </h4>
                        <p class="mb-0 text-muted small">Información detallada de la compra</p>
                    </div>
                    <div class="text-end mt-3">
                        <a href="{{ route('compras.pdf', $compra->id) }}" class="btn btn-danger" target="_blank">
                            <i class="bi bi-file-pdf"></i> Descargar PDF
                        </a>
                        <a href="{{ route('compras.imprimir', $compra->id) }}" class="btn btn-primary" target="_blank">
                            <i class="bi bi-printer"></i> Imprimir
                        </a>
                        <a href="{{ route('compras.index') }}" class="btn btn-secondary">
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
                            <div class="info-value"><strong>{{ $compra->documento }}</strong></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-card">
                            <div class="info-label">Fecha de Emisión</div>
                            <div class="info-value">{{ $compra->fecha_emision->format('d/m/Y') }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-card">
                            <div class="info-label">Fecha de Vencimiento</div>
                            <div class="info-value">{{ $compra->fecha_vencimiento->format('d/m/Y') }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-card">
                            <div class="info-label">Estado</div>
                            <div class="info-value">{!! $compra->estado_badge !!}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-card">
                            <div class="info-label">Proveedor</div>
                            <div class="info-value">
                                <strong>{{ $compra->proveedor->numero_documento }}</strong><br>
                                {{ $compra->proveedor->nombre_razon_social }}
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-card">
                            <div class="info-label">Almacén</div>
                            <div class="info-value">{{ $compra->almacen->descripcion }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-card">
                            <div class="info-label">Tipo de Pago</div>
                            <div class="info-value">{{ $compra->tipo_pago }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-card">
                            <div class="info-label">Tipo de Cambio</div>
                            <div class="info-value">{{ number_format($compra->tipo_cambio, 4) }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-card">
                            <div class="info-label">Registrado por</div>
                            <div class="info-value">{{ $compra->usuario->name ?? '-' }}</div>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="info-card">
                            <div class="info-label">Observaciones</div>
                            <div class="info-value">{{ $compra->observaciones ?? 'Sin observaciones' }}</div>
                        </div>
                    </div>
                </div>

                <h6 class="fw-bold mt-3 mb-3">
                    <i class="bi bi-box-seam"></i> Productos
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
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($compra->detalles as $index => $detalle)
                            <tr>
                                <td class="text-center">{{ $loop->iteration }}</td>
                                <td>{{ $detalle->producto->codigo_interno }}</td>
                                <td>{{ $detalle->producto->descripcion }}</td>
                                <td>{{ $detalle->producto->unidad }}</td>
                                <td class="text-center">{{ $detalle->cantidad }}</td>
                                <td class="text-end">S/ {{ number_format($detalle->precio_unitario, 2) }}</td>
                                <td class="text-end">S/ {{ number_format($detalle->total, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="6" class="text-end fw-bold">OP. Gravadas:</td>
                                <td class="text-end fw-bold">S/ {{ number_format($compra->subtotal, 2) }}</td>
                            </tr>
                            <tr>
                                <td colspan="6" class="text-end fw-bold">IGV (18%):</td>
                                <td class="text-end fw-bold">S/ {{ number_format($compra->igv, 2) }}</td>
                            </tr>
                            <tr class="table-primary">
                                <td colspan="6" class="text-end fw-bold">TOTAL:</td>
                                <td class="text-end fw-bold">S/ {{ number_format($compra->total, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection