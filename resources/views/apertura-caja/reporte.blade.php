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
            padding: 15px;
            background: white;
        }
        
        .report-container {
            max-width: 100%;
            margin: 0 auto;
            background: white;
        }
        
        /* Header */
        .header {
            background: #1e3c72;
            color: white;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .header h1 {
            font-size: 22px;
            margin-bottom: 8px;
            font-weight: bold;
        }
        
        .header p {
            font-size: 11px;
            opacity: 0.8;
        }
        
        /* Info Cards - Usando tablas para compatibilidad */
        .info-cards {
            width: 100%;
            margin-bottom: 20px;
            border-collapse: collapse;
        }
        
        .info-cards td {
            padding: 10px;
            background: #f8f9fa;
            border: 1px solid #e0e0e0;
        }
        
        .info-label {
            font-size: 10px;
            text-transform: uppercase;
            color: #6c757d;
            font-weight: bold;
        }
        
        .info-value {
            font-size: 14px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .badge-success {
            background: #28a745;
            color: white;
            padding: 4px 10px;
            border-radius: 4px;
            display: inline-block;
        }
        
        .badge-danger {
            background: #dc3545;
            color: white;
            padding: 4px 10px;
            border-radius: 4px;
            display: inline-block;
        }
        
        /* Section Titles */
        .section-title {
            background: #1e3c72;
            color: white;
            padding: 10px 15px;
            margin: 20px 0 15px 0;
            font-size: 14px;
            font-weight: bold;
        }
        
        /* Tables */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 10px;
        }
        
        .data-table th {
            background: #f0f0f0;
            padding: 10px 8px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #ddd;
        }
        
        .data-table td {
            padding: 8px;
            border: 1px solid #ddd;
        }
        
        .data-table tr:hover {
            background: #f9f9f9;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-left {
            text-align: left;
        }
        
        .text-success {
            color: #28a745;
            font-weight: bold;
        }
        
        .text-danger {
            color: #dc3545;
            font-weight: bold;
        }
        
        .text-primary {
            color: #1e3c72;
            font-weight: bold;
        }
        
        /* Summary Section */
        .summary-section {
            background: #f0f8ff;
            margin: 20px 0;
            padding: 15px;
            border: 1px solid #1e3c72;
        }
        
        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .summary-table td {
            padding: 12px;
            text-align: center;
            border: 1px solid #1e3c72;
            background: white;
        }
        
        .summary-label {
            font-size: 10px;
            text-transform: uppercase;
            color: #6c757d;
        }
        
        .summary-amount {
            font-size: 18px;
            font-weight: bold;
        }
        
        .summary-total {
            background: #1e3c72;
            color: white;
        }
        
        .summary-total .summary-amount {
            color: white;
            font-size: 20px;
        }
        
        /* Footer */
        .footer {
            background: #f8f9fa;
            padding: 15px;
            text-align: center;
            border-top: 1px solid #ddd;
            margin-top: 20px;
            font-size: 9px;
            color: #6c757d;
        }
        
        /* Utilidades */
        .mt-2 { margin-top: 10px; }
        .mb-2 { margin-bottom: 10px; }
        .pt-2 { padding-top: 10px; }
        .pb-2 { padding-bottom: 10px; }
    </style>
</head>
<body>
    <div class="report-container">
        <!-- Header -->
        <div class="header">
            <h1> REPORTE DE CAJA</h1>
            <p>Generado el {{ now()->format('d/m/Y H:i:s') }}</p>
        </div>
        
        <!-- Información General - Tabla de 2 columnas -->
        <table class="info-cards" style="width: 100%;">
            <tr>
                <td style="width: 25%; background: #f8f9fa;">
                    <div class="info-label"> Responsable</div>
                    <div class="info-value">{{ $apertura->responsable->name ?? '-' }}</div>
                </td>
                <td style="width: 25%; background: #f8f9fa;">
                    <div class="info-label"> Fecha Apertura</div>
                    <div class="info-value">{{ $apertura->fecha_apertura->format('d/m/Y H:i:s') }}</div>
                </td>
                <td style="width: 25%; background: #f8f9fa;">
                    <div class="info-label"> Monto Inicial</div>
                    <div class="info-value">S/ {{ number_format($apertura->monto_inicial, 2) }}</div>
                </td>
                <td style="width: 25%; background: #f8f9fa;">
                    <div class="info-label"> Estado</div>
                    <div class="info-value">
                        @if($apertura->estado == 'ABIERTA')
                            <span class="badge-success">Abierta</span>
                        @else
                            <span class="badge-danger">Cerrada</span>
                        @endif
                    </div>
                </td>
            </tr>
            @if($apertura->fecha_cierre)
            <tr>
                <td colspan="4" style="background: #f8f9fa;">
                    <div class="info-label"> Fecha Cierre</div>
                    <div class="info-value">{{ $apertura->fecha_cierre->format('d/m/Y H:i:s') }}</div>
                </td>
            </tr>
            @endif
        </table>
        
        <!-- Tabla de Ventas -->
        <div class="section-title">
             VENTAS REALIZADAS
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th width="5%">#</th>
                    <th width="12%">Fecha</th>
                    <th width="10%">Hora</th>
                    <th width="35%">Cliente</th>
                    <th width="18%">Documento</th>
                    <th width="20%">Monto S/</th>
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
                    <td class="text-right"><strong>S/ {{ number_format($venta->total, 2) }}</strong></td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center">No hay ventas registradas en este período</td>
                </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr style="background: #f0f0f0;">
                    <td colspan="5" class="text-right"><strong>TOTAL VENTAS:</strong></td>
                    <td class="text-right text-primary"><strong>S/ {{ number_format($totalVentas, 2) }}</strong></td>
                </tr>
            </tfoot>
        </table>
        
        <!-- Tabla de Gastos -->
        <div class="section-title">
             GASTOS DEL PERÍODO
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th width="10%">#</th>
                    <th width="20%">Fecha</th>
                    <th width="55%">Motivo</th>
                    <th width="15%">Monto S/</th>
                </tr>
            </thead>
            <tbody>
                @forelse($gastos as $index => $gasto)
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td class="text-center">{{ $gasto->fecha_emision->format('d/m/Y') }}</td>
                    <td>{{ $gasto->motivo }}</td>
                    <td class="text-right text-danger">S/ {{ number_format($gasto->monto, 2) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center">No hay gastos registrados en este período</td>
                </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr style="background: #f0f0f0;">
                    <td colspan="3" class="text-right"><strong>TOTAL GASTOS:</strong></td>
                    <td class="text-right text-danger"><strong>S/ {{ number_format($totalGastos, 2) }}</strong></td>
                </tr>
            </tfoot>
        </table>
        
        <!-- Resumen Financiero -->
        <div class="summary-section">
            <table class="summary-table">
                <tr>
                    <td style="background: #e8f5e9;">
                        <div class="summary-label"> MONTO INICIAL</div>
                        <div class="summary-amount text-success">S/ {{ number_format($apertura->monto_inicial, 2) }}</div>
                    </td>
                    <td style="background: #e3f2fd;">
                        <div class="summary-label"> TOTAL VENTAS</div>
                        <div class="summary-amount text-primary">S/ {{ number_format($totalVentas, 2) }}</div>
                    </td>
                    <td style="background: #ffebee;">
                        <div class="summary-label"> TOTAL GASTOS</div>
                        <div class="summary-amount text-danger">S/ {{ number_format($totalGastos, 2) }}</div>
                    </td>
                    <td class="summary-total">
                        <div class="summary-label"> TOTAL GENERAL</div>
                        <div class="summary-amount">S/ {{ number_format($total, 2) }}</div>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p>Reporte generado automáticamente por el sistema de gestión | {{ now()->format('d/m/Y H:i:s') }}</p>
            <p>© {{ date('Y') }} InfinityDev - Todos los derechos reservados | Versión 1.0.0</p>
        </div>
    </div>
</body>
</html>