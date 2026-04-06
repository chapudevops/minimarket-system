<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante de Compra - {{ $compra->documento }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11px;
            line-height: 1.3;
            color: #000;
            padding: 20px;
        }
        
        .container {
            max-width: 100%;
            margin: 0 auto;
        }
        
        /* Encabezado */
        .header {
            text-align: center;
            margin-bottom: 15px;
        }
        
        .header h1 {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 3px;
        }
        
        .header p {
            font-size: 9px;
            margin: 1px 0;
        }
        
        /* Información del cliente */
        .cliente-info {
            margin-bottom: 15px;
        }
        
        .cliente-info p {
            margin: 3px 0;
            font-size: 10px;
        }
        
        /* Datos de la compra */
        .compra-info {
            margin-bottom: 15px;
        }
        
        .compra-info table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .compra-info td {
            padding: 4px;
            font-size: 10px;
            vertical-align: top;
        }
        
        /* Tabla de productos */
        .productos-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        
        .productos-table th {
            background: #f0f0f0;
            padding: 6px;
            font-size: 10px;
            text-align: center;
            border: 1px solid #000;
            font-weight: bold;
        }
        
        .productos-table td {
            padding: 5px;
            font-size: 10px;
            border: 1px solid #000;
            vertical-align: top;
        }
        
        .productos-table td.text-left {
            text-align: left;
        }
        
        .productos-table td.text-right {
            text-align: right;
        }
        
        .productos-table td.text-center {
            text-align: center;
        }
        
        /* Totales */
        .totales {
            margin-bottom: 15px;
        }
        
        .totales p {
            margin: 3px 0;
            font-size: 10px;
        }
        
        .total-pagar {
            font-weight: bold;
            font-size: 11px;
        }
        
        /* Son */
        .son-text {
            margin-top: 15px;
            padding: 8px 0;
            font-size: 10px;
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }
        
        /* Footer */
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 8px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Encabezado - Empresa que compra -->
        <div class="header">
            <h1>DISTRIBUIDORA BEJAR E.I.R.L.</h1>
            <p>MZA. E LOTE. 2 CAS. SAN MARTIN ICA - ICA - ICA</p>
            <p>Ica - Ica - Ica</p>
            <p>Teléfono: -</p>
        </div>
        
        <!-- Información del Proveedor (a quien se le compra) -->
        <div class="cliente-info">
            <p><strong>NOMBRE :</strong> {{ $compra->proveedor->nombre_razon_social ?? '-' }}</p>
            <p><strong>RUC :</strong> {{ $compra->proveedor->numero_documento ?? '-' }}</p>
            <p><strong>DIRECCIÓN :</strong> {{ $compra->proveedor->direccion ?? '-' }}</p>
        </div>
        
        <!-- Datos de la Compra -->
        <div class="compra-info">
            <p><strong>MONEDA :</strong> SOLES</p>
            <p><strong>VENDEDOR :</strong> {{ $compra->usuario->name ?? 'ADMINISTRADOR' }}</p>
        </div>
        
        <!-- Tabla de Productos -->
        <table class="productos-table">
            <thead>
                <tr>
                    <th width="5%">#</th>
                    <th width="35%">CODIGO</th>
                    <th width="30%">DESCRIPCIÓN</th>
                    <th width="8%">CANT.</th>
                    <th width="8%">UND.</th>
                    <th width="7%">P.UNIT.</th>
                    <th width="7%">TOTAL</th>
                </tr>
            </thead>
            <tbody>
                @foreach($compra->detalles as $index => $detalle)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-left">{{ $detalle->producto->codigo_interno ?? '-' }}</td>
                    <td class="text-left">{{ $detalle->producto->descripcion ?? '-' }}</td>
                    <td class="text-center">{{ number_format($detalle->cantidad, 0) }}</td>
                    <td class="text-center">{{ $detalle->producto->unidad ?? 'UND' }}</td>
                    <td class="text-right">{{ number_format($detalle->precio_unitario, 2) }}</td>
                    <td class="text-right">{{ number_format($detalle->total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        
        <!-- Totales -->
        <div class="totales">
            <p>OP. GRAVADAS: S/. {{ number_format($compra->subtotal, 2) }}</p>
            <p>IGV: S/. {{ number_format($compra->igv, 2) }}</p>
            <p class="total-pagar"><strong>TOTAL A PAGAR: S/. {{ number_format($compra->total, 2) }}</strong></p>
        </div>
        
        <!-- Número en letras -->
        <div class="son-text">
            <strong>SON: {{ convertirNumeroLetras($compra->total) }} SOLES</strong>
        </div>
        
        <!-- Observaciones (si existen) -->
        @if($compra->observaciones)
        <div class="observaciones" style="margin-top: 15px; padding: 8px; border: 1px solid #ccc; font-size: 9px;">
            <strong>OBSERVACIONES:</strong><br>
            {{ $compra->observaciones }}
        </div>
        @endif
        
        <!-- Footer -->
        <div class="footer">
            <p>Documento generado el {{ $compra->created_at ? $compra->created_at->format('d/m/Y H:i:s') : date('d/m/Y H:i:s') }}</p>
        </div>
    </div>
</body>
</html>

<?php
function convertirNumeroLetras($numero)
{
    $numero = round($numero, 2);
    $partes = explode('.', number_format($numero, 2, '.', ''));
    $entero = (int)$partes[0];
    $decimal = isset($partes[1]) ? (int)$partes[1] : 0;
    
    $unidades = ['', 'UN', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE'];
    $decenas = ['', 'DIEZ', 'VEINTE', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];
    $centenas = ['', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS', 'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'];
    
    if ($entero == 0) {
        $letras = 'CERO';
    } else {
        $letras = '';
        $miles = floor($entero / 1000);
        $resto = $entero % 1000;
        
        if ($miles > 0) {
            if ($miles == 1) {
                $letras .= 'MIL ';
            } else {
                $letras .= convertirNumeroLetras($miles) . ' MIL ';
            }
        }
        
        if ($resto > 0) {
            $c = floor($resto / 100);
            $r = $resto % 100;
            
            if ($c > 0) {
                if ($c == 1 && $r == 0) {
                    $letras .= 'CIEN ';
                } else {
                    $letras .= $centenas[$c] . ' ';
                }
            }
            
            if ($r > 0) {
                if ($r < 10) {
                    $letras .= $unidades[$r] . ' ';
                } elseif ($r < 20) {
                    $letras .= ['DIEZ', 'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE', 'DIECISÉIS', 'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE'][$r - 10] . ' ';
                } else {
                    $d = floor($r / 10);
                    $u = $r % 10;
                    if ($u > 0) {
                        $letras .= $decenas[$d] . ' Y ' . $unidades[$u] . ' ';
                    } else {
                        $letras .= $decenas[$d] . ' ';
                    }
                }
            }
        }
    }
    
    $letras = trim($letras);
    $letras = ucfirst(strtolower($letras));
    
    if ($decimal > 0) {
        $letras .= ' CON ' . sprintf('%02d', $decimal) . '/100';
    } else {
        $letras .= ' CON 00/100';
    }
    
    return $letras;
}
?>