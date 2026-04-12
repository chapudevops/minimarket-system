<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cotización - {{ $cotizacion->documento }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica', Arial, sans-serif; font-size: 11px; padding: 20px; }
        .header { text-align: center; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #333; }
        .header h1 { font-size: 14px; margin-bottom: 5px; }
        .info-section { margin-bottom: 20px; padding: 10px; background: #f5f5f5; }
        .info-section table { width: 100%; }
        .info-section td { padding: 5px; }
        .table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .table th { background: #f0f0f0; text-align: center; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .total-section { width: 300px; margin-left: auto; margin-top: 20px; }
        .total-section table { width: 100%; border-collapse: collapse; }
        .total-section td { padding: 8px; border-bottom: 1px solid #ddd; }
        .total-section .total-row { font-weight: bold; background: #f0f0f0; }
        .footer { margin-top: 30px; text-align: center; font-size: 9px; color: #999; border-top: 1px solid #ddd; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>COTIZACIÓN</h1>
        <h2>{{ $cotizacion->documento }}</h2>
        <p>{{ $empresa->razon_social ?? 'DISTRIBUIDORA BEJAR E.I.R.L.' }}</p>
        <p>RUC: {{ $empresa->ruc ?? '20100066603' }}</p>
    </div>

    <div class="info-section">
        <table>
            <tr><td width="20%"><strong>Cliente:</strong></td><td colspan="3">{{ $cotizacion->cliente->nombre_razon_social ?? 'CLIENTES VARIOS' }}</td></tr>
            <tr><td><strong>RUC/DNI:</strong></td><td>{{ $cotizacion->cliente->numero_documento ?? '00000000' }}</td>
                <td width="20%"><strong>Fecha Emisión:</strong></td><td>{{ $cotizacion->fecha_emision->format('d/m/Y H:i:s') }}</td>
            </tr>
            <tr><td><strong>Dirección:</strong></td><td colspan="3">{{ $cotizacion->cliente->direccion ?? '-' }}</td></tr>
            <tr><td><strong>Fecha Validez:</strong></td><td>{{ $cotizacion->fecha_validez->format('d/m/Y') }}</td>
                <td><strong>Moneda:</strong></td><td>{{ $cotizacion->tipo_moneda_texto }}</td>
            </tr>
        </table>
    </div>

    <table class="table">
        <thead>
            <tr><th>#</th><th>Código</th><th>Descripción</th><th>Cant.</th><th>P.Unit.</th><th>Total</th></tr>
        </thead>
        <tbody>
            @foreach($cotizacion->detalles as $index => $detalle)
            <tr>
                <td class="text-center">{{ $loop->iteration }}</td>
                <td class="text-center">{{ $detalle->producto->codigo_interno ?? '-' }}</td>
                <td>{{ $detalle->producto->descripcion ?? '-' }}</td>
                <td class="text-center">{{ $detalle->cantidad }}</td>
                <td class="text-right">{{ $cotizacion->tipo_moneda == 'USD' ? '$' : 'S/' }} {{ number_format($detalle->precio_unitario, 2) }}</td>
                <td class="text-right">{{ $cotizacion->tipo_moneda == 'USD' ? '$' : 'S/' }} {{ number_format($detalle->total, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total-section">
        <table>
            <tr><td width="60%">Subtotal:</td><td class="text-right">{{ $cotizacion->tipo_moneda == 'USD' ? '$' : 'S/' }} {{ number_format($cotizacion->subtotal, 2) }}</td></tr>
            @if($cotizacion->descuento > 0)
            <tr><td>Descuento:</td><td class="text-right">{{ $cotizacion->tipo_moneda == 'USD' ? '$' : 'S/' }} {{ number_format($cotizacion->descuento, 2) }}</td></tr>
            @endif
            <tr><td>IGV (18%):</td><td class="text-right">{{ $cotizacion->tipo_moneda == 'USD' ? '$' : 'S/' }} {{ number_format($cotizacion->igv, 2) }}</td></tr>
            <tr class="total-row"><td><strong>TOTAL:</strong></td><td class="text-right"><strong>{{ $cotizacion->tipo_moneda == 'USD' ? '$' : 'S/' }} {{ number_format($cotizacion->total, 2) }}</strong></td></tr>
        </table>
    </div>

    @if($cotizacion->observaciones)
    <div class="observaciones" style="margin-top: 20px; padding: 10px; border: 1px solid #ddd;">
        <strong>Observaciones:</strong><br>
        {{ $cotizacion->observaciones }}
    </div>
    @endif

    <div class="footer">
        <p>Cotización válida hasta {{ $cotizacion->fecha_validez->format('d/m/Y') }}</p>
        <p>Documento generado el {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>
</body>
</html>