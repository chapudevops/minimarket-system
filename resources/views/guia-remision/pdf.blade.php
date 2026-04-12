<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Guía de Remisión - {{ $guia->documento }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica', Arial, sans-serif; font-size: 11px; padding: 20px; }
        .header { text-align: center; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #333; }
        .header h1 { font-size: 14px; margin-bottom: 5px; }
        .info-section { margin-bottom: 20px; padding: 10px; background: #f5f5f5; }
        .info-section table { width: 100%; }
        .info-section td { padding: 5px; }
        .section-title { background: #e9ecef; padding: 8px; margin: 15px 0 10px; font-weight: bold; }
        .table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .table th { background: #f0f0f0; text-align: center; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .footer { margin-top: 30px; text-align: center; font-size: 9px; color: #999; border-top: 1px solid #ddd; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>GUÍA DE REMISIÓN ELECTRÓNICA</h1>
        <h2>{{ $guia->documento }}</h2>
        <p>{{ $empresa->razon_social ?? 'DISTRIBUIDORA BEJAR E.I.R.L.' }}</p>
        <p>RUC: {{ $empresa->ruc ?? '20100066603' }}</p>
    </div>

    <div class="info-section">
        <table>
            <tr><td width="20%"><strong>Cliente:</strong></td><td colspan="3">{{ $guia->cliente->nombre_razon_social ?? 'CLIENTES VARIOS' }}</td></tr>
            <tr><td><strong>RUC/DNI:</strong></td><td>{{ $guia->cliente->numero_documento ?? '00000000' }}</td>
                <td width="20%"><strong>Fecha Emisión:</strong></td><td>{{ $guia->fecha_emision->format('d/m/Y H:i:s') }}</td></tr>
            <tr><td><strong>Fecha Traslado:</strong></td><td>{{ $guia->fecha_traslado->format('d/m/Y') }}</td>
                <td><strong>Motivo:</strong></td><td>{{ $guia->motivo_traslado_texto }}</td></tr>
            <tr><td><strong>Peso Bruto:</strong></td><td>{{ number_format($guia->peso_bruto_total, 3) }} KGM</td>
                <td><strong>Modalidad:</strong></td><td>{{ $guia->modalidad_traslado_texto }}</td></tr>
        </table>
    </div>

    <div class="section-title">PUNTOS DE PARTIDA Y LLEGADA</div>
    <div class="info-section">
        <table>
            <tr><td width="20%"><strong>Ubigeo Partida:</strong></td><td width="30%">{{ $guia->ubigeo_partida }}</td>
                <td width="20%"><strong>Ubigeo Llegada:</strong></td><td width="30%">{{ $guia->ubigeo_llegada }}</td></tr>
            <tr><td><strong>Dirección Partida:</strong></td><td colspan="3">{{ $guia->direccion_partida }}</td></tr>
            <tr><td><strong>Dirección Llegada:</strong></td><td colspan="3">{{ $guia->direccion_llegada }}</td></tr>
        </table>
    </div>

    <div class="section-title">DATOS DEL TRANSPORTE</div>
    <div class="info-section">
        <table>
            <tr><td width="20%"><strong>Conductor:</strong></td><td width="30%">{{ $guia->conductor->nombre ?? 'No asignado' }}</td>
                <td width="20%"><strong>Licencia:</strong></td><td width="30%">{{ $guia->conductor->licencia ?? '-' }}</td></tr>
            <tr><td><strong>Vehículo:</strong></td><td>{{ $guia->vehiculo->placa ?? 'No asignado' }}</td>
                <td><strong>Marca/Modelo:</strong></td><td>{{ $guia->vehiculo ? $guia->vehiculo->marca . ' ' . $guia->vehiculo->modelo : '-' }}</td></tr>
        </table>
    </div>

    <div class="section-title">BIENES A TRASLADAR</div>
    <table class="table">
        <thead><tr><th>#</th><th>Código</th><th>Descripción</th><th>Cantidad</th><th>Unidad</th></tr></thead>
        <tbody>
            @foreach($guia->detalles as $index => $detalle)
            <tr>
                <td class="text-center">{{ $loop->iteration }}</td>
                <td class="text-center">{{ $detalle->producto->codigo_interno ?? '-' }}</td>
                <td>{{ $detalle->producto->descripcion ?? '-' }}</td>
                <td class="text-center">{{ $detalle->cantidad }}</td>
                <td class="text-center">{{ $detalle->producto->unidad ?? 'NIU' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @if($guia->observaciones)
    <div class="section-title">OBSERVACIONES</div>
    <div class="info-section">
        <p>{{ $guia->observaciones }}</p>
    </div>
    @endif

    <div class="footer">
        <p>Documento generado el {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>
</body>
</html>