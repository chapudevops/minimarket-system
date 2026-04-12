<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Caja - {{ $apertura->id }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11px;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #333;
        }
        .header h1 {
            font-size: 16px;
            margin-bottom: 5px;
        }
        .info-section {
            margin-bottom: 20px;
            padding: 10px;
            background: #f5f5f5;
        }
        .info-section table {
            width: 100%;
        }
        .info-section td {
            padding: 5px;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .table th, .table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .table th {
            background: #f0f0f0;
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .total-section {
            width: 300px;
            margin-left: auto;
            margin-top: 20px;
        }
        .total-section table {
            width: 100%;
            border-collapse: collapse;
        }
        .total-section td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }
        .total-section .total-row {
            font-weight: bold;
            background: #f0f0f0;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 9px;
            color: #999;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>REPORTE DE CAJA</h1>
        <p>{{ $apertura->responsable->name ?? 'Usuario' }} - {{ $apertura->fecha_apertura->format('d/m/Y') }}</p>
    </div>

    <div class="info-section">
        <table>
            <tr>
                <td width="25%"><strong>Responsable:</strong></td>
                <td width="25%">{{ $apertura->responsable->name ?? '-' }}</td>
                <td width="25%"><strong>Fecha Apertura:</strong></td>
                <td width="25%">{{ $apertura->fecha_apertura->format('d/m/Y H:i:s') }}</td>
            </tr>
            <tr>
                <td><strong>Monto Inicial:</strong></td>
                <td>S/ {{ number_format($apertura->monto_inicial, 2) }}</td>
                <td><strong>Fecha Cierre:</strong></td>
                <td>{{ $apertura->fecha_cierre ? $apertura->fecha_cierre->format('d/m/Y H:i:s') : 'En curso' }}</td>
            </tr>
            <tr>
                <td><strong>Estado:</strong></td>
                <td colspan="3">{{ $apertura->estado == 'ABIERTA' ? 'Abierta' : 'Cerrada' }}</td>
            </tr>
        </table>
    </div>

    <h3>Ventas realizadas</h3>
    <table class="table">
        <thead>
            <tr>
                <th>#</th>
                <th>Fecha</th>
                <th>Hora</th>
                <th>Cliente</th>
                <th>Documento</th>
                <th>Monto S/</th>
            </tr>
        </thead>
        <tbody>
            @forelse($ventas as $index => $venta)
            <tr>
                <td class="text-center">{{ $loop->iteration }}</td>
                <td class="text-center">{{ $venta->fecha_emision->format('d/m/Y') }}</td>
                <td class="text-center">{{ $venta->fecha_emision->format('H:i:s') }}</td>
                <td>{{ $venta->cliente->nombre_razon_social ?? 'CLIENTES VARIOS' }}</td>
                <td class="text-center">{{ $venta->documento }}</td>
                <td class="text-right">S/ {{ number_format($venta->total, 2) }}</td>
            </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center">No hay ventas registradas</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" class="text-right"><strong>Total Ventas:</strong></td>
                <td class="text-right"><strong>S/ {{ number_format($totalVentas, 2) }}</strong></td>
            </tr>
        </tfoot>
    </table>

    <h3>Gastos</h3>
    <table class="table">
        <thead>
            <tr>
                <th>#</th>
                <th>Fecha</th>
                <th>Motivo</th>
                <th>Monto S/</th>
            </tr>
        </thead>
        <tbody>
            @forelse($gastos as $index => $gasto)
            <tr>
                <td class="text-center">{{ $loop->iteration }}</td>
                <td class="text-center">{{ $gasto->fecha_emision->format('d/m/Y') }}</td>
                <td>{{ $gasto->motivo }}</td>
                <td class="text-right">S/ {{ number_format($gasto->monto, 2) }}</td>
            </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center">No hay gastos registrados</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="text-right"><strong>Total Gastos:</strong></td>
                <td class="text-right"><strong>S/ {{ number_format($totalGastos, 2) }}</strong></td>
            </tr>
        </tfoot>
    </table>

    <div class="total-section">
        <table>
            <tr>
                <td><strong>Monto Inicial:</strong></td>
                <td class="text-right">S/ {{ number_format($apertura->monto_inicial, 2) }}</td>
            </tr>
            <tr>
                <td><strong>Total Ventas:</strong></td>
                <td class="text-right">S/ {{ number_format($totalVentas, 2) }}</td>
            </tr>
            <tr>
                <td><strong>Total Gastos:</strong></td>
                <td class="text-right">S/ {{ number_format($totalGastos, 2) }}</td>
            </tr>
            <tr class="total-row">
                <td><strong>TOTAL:</strong></td>
                <td class="text-right"><strong>S/ {{ number_format($total, 2) }}</strong></td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <p>Reporte generado el {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>
</body>
</html>