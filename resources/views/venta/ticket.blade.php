<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ticket - {{ $venta->documento }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Courier New', monospace; font-size: 10px; width: 80mm; margin: 0 auto; padding: 5px; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .header { text-align: center; margin-bottom: 10px; padding-bottom: 5px; border-bottom: 1px dashed #000; }
        .info { margin-bottom: 10px; padding-bottom: 5px; border-bottom: 1px dotted #000; }
        .info p { margin: 3px 0; }
        .table { width: 100%; margin-bottom: 10px; }
        .table td { padding: 3px 0; }
        .total { margin-top: 10px; padding-top: 5px; border-top: 1px dashed #000; }
        .footer { text-align: center; margin-top: 15px; padding-top: 5px; border-top: 1px dashed #000; font-size: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <strong>{{ $empresa->razon_social ?? 'DISTRIBUIDORA BEJAR E.I.R.L.' }}</strong><br>
        {{ $empresa->direccion ?? 'MZA. E LOTE. 2 CAS. SAN MARTIN ICA - ICA - ICA' }}<br>
        RUC: {{ $empresa->ruc ?? '20100066603' }}
    </div>

    <div class="info">
        <p><strong>Documento:</strong> {{ $venta->documento }}</p>
        <p><strong>Fecha:</strong> {{ $venta->fecha_emision->format('d/m/Y H:i:s') }}</p>
        <p><strong>Cliente:</strong> {{ $venta->cliente->nombre_razon_social ?? 'CLIENTES VARIOS' }}</p>
        <p><strong>RUC/DNI:</strong> {{ $venta->cliente->numero_documento ?? '00000000' }}</p>
    </div>

    <table class="table">
        @foreach($venta->detalles as $detalle)
        <tr>
            <td colspan="2">{{ $detalle->producto->descripcion ?? '-' }}</td>
        </tr>
        <tr>
            <td width="60%">Cant: {{ $detalle->cantidad }} x S/ {{ number_format($detalle->precio_unitario, 2) }}</td>
            <td width="40%" class="text-right">S/ {{ number_format($detalle->total, 2) }}</td>
        </tr>
        @endforeach
    </table>

    <div class="total">
        <p><strong>OP. Gravadas:</strong> <span class="text-right">S/ {{ number_format($venta->subtotal, 2) }}</span></p>
        <p><strong>IGV (18%):</strong> <span class="text-right">S/ {{ number_format($venta->igv, 2) }}</span></p>
        <p><strong>TOTAL:</strong> <span class="text-right">S/ {{ number_format($venta->total, 2) }}</span></p>
        <p><strong>Pagado:</strong> <span class="text-right">S/ {{ number_format($venta->pagado, 2) }}</span></p>
        <p><strong>Cambio:</strong> <span class="text-right">S/ {{ number_format($venta->cambio, 2) }}</span></p>
    </div>

    <div class="footer">
        <p>¡Gracias por su compra!</p>
        <p>www.minimarket.com</p>
    </div>
</body>
</html>