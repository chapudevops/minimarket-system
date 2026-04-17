<?php
/**
 * Lógica de conversión de números a letras
 */
if (!function_exists('convertirNumeroLetras')) {
    function convertirNumeroLetras($numero) {
        $numero = round($numero, 2);
        $partes = explode('.', number_format($numero, 2, '.', ''));
        $entero = (int)$partes[0];
        $decimal = $partes[1];

        if ($entero == 0) {
            $letras = 'CERO';
        } else {
            $letras = convertirBloque($entero);
        }

        $texto = strtoupper($letras) . " CON " . $decimal . "/100 SOLES";
        
        $buscar = ['UN SOLES', 'UNO CON', 'DIECI CON', 'VEINTI CON'];
        $reemplazar = ['UN SOL', 'UN CON', 'DIECI CON', 'VEINTI CON'];
        
        return str_replace($buscar, $reemplazar, $texto);
    }
}

if (!function_exists('convertirBloque')) {
    function convertirBloque($num) {
        if ($num == 100) return "CIEN";
        
        $unidades = ['', 'UN', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE'];
        $decenas = ['', 'DIEZ', 'VEINTE', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];
        $centenas = ['', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS', 'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'];
        $especiales = [
            11=>'ONCE', 12=>'DOCE', 13=>'TRECE', 14=>'CATORCE', 15=>'QUINCE', 
            16=>'DIECISÉIS', 17=>'DIECISIETE', 18=>'DIECIOCHO', 19=>'DIECINUEVE', 
            21=>'VEINTIUNO', 22=>'VEINTIDÓS', 23=>'VEINTITRÉS', 24=>'VEINTICUATRO', 
            25=>'VEINTICINCO', 26=>'VEINTISÉIS', 27=>'VEINTISIETE', 28=>'VEINTIOCHO', 29=>'VEINTINUEVE'
        ];

        $res = "";

        if ($num >= 1000000) {
            $millones = floor($num / 1000000);
            $num %= 1000000;
            $res .= ($millones == 1) ? "UN MILLÓN " : convertirBloque($millones) . " MILLONES ";
        }

        if ($num >= 1000) {
            $miles = floor($num / 1000);
            $num %= 1000;
            $res .= ($miles == 1) ? "MIL " : convertirBloque($miles) . " MIL ";
        }

        if ($num >= 100) {
            $c = floor($num / 100);
            $num %= 100;
            $res .= ($c == 1 && $num == 0) ? "CIEN" : $centenas[$c] . " ";
        }

        if ($num > 0) {
            if (isset($especiales[$num])) {
                $res .= $especiales[$num];
            } else {
                $d = floor($num / 10);
                $u = $num % 10;
                if ($d > 0) {
                    $res .= $decenas[$d];
                    if ($u > 0) $res .= " Y " . $unidades[$u];
                } else {
                    $res .= $unidades[$u];
                }
            }
        }
        return trim($res);
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket - {{ $venta->documento }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif;
            font-size: 11px;
            width: 80mm;
            margin: 0 auto;
            padding: 0;
            background: #e9ecef;
        }
        
        .ticket {
            width: 100%;
            max-width: 80mm;
            background: white;
            margin: 8px auto;
            padding: 12px 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border-radius: 8px;
        }
        
        /* HEADER */
        .header {
            text-align: center;
            margin-bottom: 12px;
            padding-bottom: 10px;
            border-bottom: 2px solid #2c3e50;
        }
        
        .logo-img {
            max-width: 70px;
            height: auto;
            margin-bottom: 6px;
        }
        
        .empresa-nombre {
            font-weight: 800;
            font-size: 14px;
            text-transform: uppercase;
            color: #1a2a3a;
            letter-spacing: 0.5px;
        }
        
        .empresa-ruc, .empresa-direccion {
            font-size: 8px;
            color: #6c757d;
            margin-top: 2px;
        }
        
        /* COMPROBANTE */
        .comprobante-box {
            background: linear-gradient(135deg, #1a2a3a 0%, #2c3e50 100%);
            color: white;
            text-align: center;
            padding: 10px;
            margin: 10px 0;
            border-radius: 12px;
        }
        
        .comprobante-tipo {
            font-weight: 700;
            font-size: 13px;
            letter-spacing: 1px;
            display: block;
        }
        
        .comprobante-numero {
            background: white;
            color: #1a2a3a;
            display: inline-block;
            padding: 5px 15px;
            margin-top: 6px;
            font-size: 14px;
            font-weight: 800;
            border-radius: 20px;
            font-family: 'Courier New', monospace;
        }
        
        /* INFO CLIENTE */
        .info-section {
            background: #f8f9fa;
            padding: 8px 10px;
            margin: 10px 0;
            border-radius: 10px;
        }
        
        .info-row {
            display: flex;
            margin-bottom: 4px;
            font-size: 9px;
        }
        
        .info-label {
            font-weight: 700;
            width: 70px;
            color: #495057;
        }
        
        .info-value {
            flex: 1;
            color: #212529;
        }
        
        /* TABLA DE PRODUCTOS */
        .productos-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        
        .productos-table th {
            text-align: left;
            font-size: 9px;
            padding: 6px 2px;
            background: #e9ecef;
            color: #495057;
            border-bottom: 1.5px solid #dee2e6;
        }
        
        .productos-table td {
            padding: 6px 2px;
            font-size: 9px;
            border-bottom: 1px solid #f1f3f5;
            vertical-align: top;
        }
        
        .productos-table tr:last-child td {
            border-bottom: none;
        }
        
        /* TOTALES */
        .totales-container {
            margin-top: 10px;
            padding-top: 8px;
            border-top: 1px dashed #dee2e6;
        }
        
        .total-line {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 4px;
            font-size: 10px;
        }
        
        .total-label {
            width: 85px;
            text-align: right;
            font-weight: 600;
            color: #495057;
            padding-right: 12px;
        }
        
        .total-amount {
            width: 70px;
            text-align: right;
            font-weight: 600;
        }
        
        .total-final {
            display: flex;
            justify-content: flex-end;
            margin-top: 6px;
            padding-top: 6px;
            border-top: 2px solid #2c3e50;
            font-weight: 800;
            font-size: 12px;
        }
        
        .total-final .total-label {
            color: #1a2a3a;
        }
        
        .total-final .total-amount {
            color: #2c3e50;
            font-size: 13px;
        }
        
        /* MONTO EN LETRAS */
        .monto-letras {
            background: linear-gradient(135deg, #e8f4f0 0%, #d4e8e0 100%);
            padding: 8px 12px;
            text-align: center;
            font-size: 8px;
            font-weight: 600;
            text-transform: uppercase;
            margin: 12px 0;
            border-radius: 10px;
            color: #1a5d3c;
            letter-spacing: 0.3px;
        }
        
        /* MÉTODO DE PAGO */
        .payment-method {
            text-align: center;
            padding: 8px;
            background: #f8f9fa;
            border-radius: 10px;
            margin: 10px 0;
        }
        
        .payment-method span {
            font-weight: 800;
            color: #2c3e50;
        }
        
        /* CÓDIGO QR */
        .qr-container {
            text-align: center;
            margin: 12px 0;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 12px;
        }
        
        .qr-code-img {
            width: 80px;
            height: 80px;
            margin-bottom: 6px;
        }
        
        .qr-text {
            font-size: 7px;
            color: #6c757d;
        }
        
        /* FOOTER */
        .footer {
            text-align: center;
            margin-top: 12px;
            padding-top: 10px;
            border-top: 1px dashed #dee2e6;
        }
        
        .footer-gracias {
            font-weight: 800;
            font-size: 11px;
            color: #1a5d3c;
            margin-bottom: 6px;
        }
        
        .footer-legal {
            font-size: 7px;
            color: #adb5bd;
        }
        
        /* SEPARADORES */
        .separator-dashed {
            border-top: 1px dashed #dee2e6;
            margin: 8px 0;
        }
        
        /* BOTÓN IMPRESIÓN */
        .no-print {
            text-align: center;
            margin-top: 20px;
            padding: 15px;
        }
        
        .print-btn {
            background: linear-gradient(135deg, #2c3e50 0%, #1a2a3a 100%);
            color: white;
            border: none;
            padding: 12px 28px;
            border-radius: 40px;
            cursor: pointer;
            font-weight: 700;
            font-size: 14px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        
        .print-btn:hover {
            transform: scale(1.02);
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }
        
        @media print {
            .no-print {
                display: none;
            }
            body {
                background: white;
                padding: 0;
                margin: 0;
            }
            .ticket {
                margin: 0;
                padding: 8px;
                box-shadow: none;
                border-radius: 0;
            }
        }
    </style>
</head>
<body>
    <div class="ticket">
        <!-- HEADER - EMPRESA -->
        <div class="header">
            @if($empresa && $empresa->logo)
                @php
                    $path = public_path('storage/empresa/' . $empresa->logo);
                    if (file_exists($path)) {
                        $type = pathinfo($path, PATHINFO_EXTENSION);
                        $data = file_get_contents($path);
                        $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
                    }
                @endphp
                @isset($base64)
                    <img src="{{ $base64 }}" class="logo-img" alt="Logo">
                @else
                    <img src="{{ asset('storage/empresa/' . $empresa->logo) }}" class="logo-img" alt="Logo">
                @endisset
            @endif
            <div class="empresa-nombre">{{ $empresa->razon_social ?? 'DISTRIBUIDORA BEJAR E.I.R.L.' }}</div>
            <div class="empresa-ruc">RUC: {{ $empresa->ruc ?? '20100066603' }}</div>
            <div class="empresa-direccion">{{ $empresa->direccion ?? 'Av. Principal N° 123 - Lima' }}</div>
        </div>

        <!-- COMPROBANTE -->
        <div class="comprobante-box">
            <span class="comprobante-tipo">{{ $venta->tipo_comprobante == 'BOLETA' ? 'BOLETA DE VENTA' : ($venta->tipo_comprobante == 'FACTURA' ? 'FACTURA ELECTRÓNICA' : 'NOTA') }}</span>
            <div class="comprobante-numero">{{ $venta->documento }}</div>
        </div>

        <!-- INFORMACIÓN DEL CLIENTE -->
        <div class="info-section">
            <div class="info-row">
                <span class="info-label">CLIENTE:</span>
                <span class="info-value">{{ $venta->cliente->nombre_razon_social ?? 'CLIENTES VARIOS' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">DOCUMENTO:</span>
                <span class="info-value">{{ $venta->cliente->numero_documento ?? '00000000' }}</span>
            </div>
            @if($venta->cliente && $venta->cliente->direccion)
            <div class="info-row">
                <span class="info-label">DIRECCIÓN:</span>
                <span class="info-value">{{ $venta->cliente->direccion }}</span>
            </div>
            @endif
            <div class="info-row">
                <span class="info-label">FECHA:</span>
                <span class="info-value">{{ $venta->fecha_emision->format('d/m/Y H:i:s') }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">VENDEDOR:</span>
                <span class="info-value">{{ $venta->usuario->name ?? 'ADMINISTRADOR' }}</span>
            </div>
        </div>

        <!-- TABLA DE PRODUCTOS -->
        <table class="productos-table">
            <thead>
                <tr>
                    <th width="12%">CANT</th>
                    <th width="48%">DESCRIPCIÓN</th>
                    <th width="20%" style="text-align: right;">P/U</th>
                    <th width="20%" style="text-align: right;">TOTAL</th>
                </tr>
            </thead>
            <tbody>
                @foreach($venta->detalles as $detalle)
                <tr>
                    <td style="text-align: center;">{{ number_format($detalle->cantidad, 0) }}</td>
                    <td>{{ $detalle->producto->descripcion ?? '-' }}</td>
                    <td style="text-align: right;">S/ {{ number_format($detalle->precio_unitario, 2) }}</td>
                    <td style="text-align: right;"><strong>S/ {{ number_format($detalle->total, 2) }}</strong></td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- TOTALES -->
        <div class="totales-container">
            <div class="total-line">
                <span class="total-label">OP. GRAVADAS:</span>
                <span class="total-amount">S/ {{ number_format($venta->subtotal, 2) }}</span>
            </div>
            <div class="total-line">
                <span class="total-label">IGV (18%):</span>
                <span class="total-amount">S/ {{ number_format($venta->igv, 2) }}</span>
            </div>
            <div class="total-final">
                <span class="total-label">TOTAL:</span>
                <span class="total-amount">S/ {{ number_format($venta->total, 2) }}</span>
            </div>
        </div>

        <!-- MONTO EN LETRAS -->
        <div class="monto-letras">
            SON: {{ convertirNumeroLetras($venta->total) }}
        </div>

        <!-- MÉTODO DE PAGO -->
        <div class="payment-method">
            🏦 PAGADO CON: <span>{{ strtoupper($venta->forma_pago) }}</span> - S/ {{ number_format($venta->total, 2) }}
            @if($venta->tipo_venta == 'CONTADO' && $venta->cambio > 0)
            <br>💵 CAMBIO: S/ {{ number_format($venta->cambio, 2) }}
            @endif
        </div>

        <!-- CÓDIGO QR (si existe) -->
        @if(isset($qrCode) && $qrCode)
        <div class="qr-container">
            <img src="{{ $qrCode }}" class="qr-code-img" alt="Código QR">
            <div class="qr-text">Escanea para verificar tu comprobante</div>
        </div>
        @else
        <div class="qr-container">
            <svg width="80" height="80" viewBox="0 0 100 100" style="margin-bottom: 6px;">
                <rect width="100" height="100" fill="#f8f9fa" stroke="#dee2e6" stroke-width="1" rx="5"/>
                <text x="50" y="50" text-anchor="middle" dominant-baseline="middle" fill="#adb5bd" font-size="8" font-family="Arial">CÓDIGO QR</text>
                <text x="50" y="62" text-anchor="middle" dominant-baseline="middle" fill="#adb5bd" font-size="6">{{ $venta->documento }}</text>
            </svg>
            <div class="qr-text">{{ $venta->documento }}</div>
        </div>
        @endif

        <!-- FOOTER -->
        <div class="footer">
            <div class="footer-gracias">✨ ¡GRACIAS POR SU COMPRA! ✨</div>
            <div class="footer-legal">Representación impresa de {{ $venta->tipo_comprobante }} ELECTRÓNICA</div>
            <div class="footer-legal">Válido como comprobante de pago</div>
            <div class="separator-dashed"></div>
            <div class="footer-legal">InfinityDev - Sistema de Gestión POS</div>
        </div>
    </div>

    <!-- BOTÓN PARA IMPRIMIR -->
    <div class="no-print">
        <button onclick="window.print()" class="print-btn">
            🖨️ IMPRIMIR TICKET
        </button>
        <button onclick="window.close()" class="print-btn" style="background: linear-gradient(135deg, #6c757d 0%, #495057 100%); margin-left: 10px;">
            ❌ CERRAR
        </button>
    </div>

    <script>
        // Auto-imprimir al cargar la página (opcional)
        setTimeout(function() {
            window.print();
        }, 300);
    </script>
</body>
</html>