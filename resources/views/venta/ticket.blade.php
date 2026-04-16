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
    <title>Ticket - {{ $venta->documento }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            width: 80mm;
            margin: 0 auto;
            padding: 5mm;
            background: white;
            color: #000;
        }
        
        .ticket { width: 100%; }
        
        .header { text-align: center; margin-bottom: 10px; }
        .logo-img { max-width: 100px; height: auto; margin-bottom: 5px; }
        .empresa-nombre { font-weight: bold; font-size: 14px; text-transform: uppercase; }
        .empresa-info { font-size: 9px; margin-bottom: 1px; text-transform: uppercase; }

        .comprobante-box {
            background: #000;
            color: #fff;
            text-align: center;
            padding: 8px;
            margin: 10px 0;
        }
        .comprobante-tipo { font-weight: bold; font-size: 14px; display: block; }
        .comprobante-numero {
            background: #fff;
            color: #000;
            display: inline-block;
            padding: 3px 12px;
            margin-top: 5px;
            font-size: 15px;
            font-weight: bold;
            border: 1px solid #000;
        }
        
        .seccion { margin-bottom: 10px; font-size: 11px; }
        .linea { display: flex; margin-bottom: 2px; }
        .label { font-weight: bold; width: 95px; }

        .separador-grueso { border-top: 2px solid #000; margin: 8px 0; }
        .separador-punteado { border-top: 1px dashed #000; margin: 8px 0; }
        
        .tabla { width: 100%; border-collapse: collapse; }
        .tabla th { text-align: left; font-size: 10px; padding-bottom: 5px; border-bottom: 1px solid #000; }
        .item-row td { padding: 5px 0; vertical-align: top; font-size: 11px; }

        .total-linea { display: flex; justify-content: flex-end; margin-bottom: 2px; font-size: 11px; }
        .total-label { width: 100px; text-align: right; font-weight: bold; padding-right: 10px; }
        .total-monto { width: 70px; text-align: right; }

        .total-final {
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 5px 0;
            margin: 8px 0;
            display: flex;
            justify-content: space-between;
            font-weight: bold;
            font-size: 13px;
        }

        .monto-letras {
            background: #f0f0f0;
            padding: 6px;
            text-align: center;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 10px 0;
            border: 1px solid #ccc;
        }

        .qr-container { text-align: center; margin-top: 15px; font-size: 9px; }

        @media print {
            .no-print { display: none; }
            body { padding: 0; margin: 0; }
        }
    </style>
</head>
<body>
    <div class="ticket">
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
            <img src="{{ $base64 }}" class="logo-img">
        @else
            <img src="{{ asset('storage/empresa/' . $empresa->logo) }}" class="logo-img">
        @endisset
    @endif
    
    <div class="empresa-nombre">{{ $empresa->razon_social ?? 'DISTRIBUIDORA BEJAR E.I.R.L.' }}</div>
    </div>

        <div class="comprobante-box">
            <span class="comprobante-tipo">{{ $venta->tipo_comprobante == 'BOLETA' ? 'BOLETA DE VENTA' : 'FACTURA' }} ELECTRÓNICA</span>
            <div class="comprobante-numero">{{ $venta->documento }}</div>
        </div>

        <div class="seccion">
            <div class="linea"><span class="label">N° DOCUMENTO:</span> <span>{{ $venta->cliente->numero_documento ?? '00000000' }}</span></div>
            <div class="linea"><span class="label">NOMBRE:</span> <span>{{ $venta->cliente->nombre_razon_social ?? 'CLIENTES VARIOS' }}</span></div>
            <div class="linea"><span class="label">DIRECCIÓN:</span> <span>{{ $venta->cliente->direccion ?? '-' }}</span></div>
            <div class="linea"><span class="label">FECHA/HORA:</span> <span>{{ $venta->fecha_emision->format('d/m/Y H:i:s') }}</span></div>
            <div class="linea"><span class="label">MONEDA/PAGO:</span> <span>SOLES / {{ strtoupper($venta->forma_pago) }}</span></div>
            <div class="linea"><span class="label">VENDEDOR:</span> <span>{{ $venta->usuario->name ?? 'ADMIN' }}</span></div>
        </div>

        <div class="separador-grueso"></div>

        <table class="tabla">
            <thead>
                <tr>
                    <th width="15%">CANT</th>
                    <th width="45%">DESCRIPCIÓN</th>
                    <th width="20%" style="text-align: right;">P/U</th>
                    <th width="20%" style="text-align: right;">TOTAL</th>
                </tr>
            </thead>
            <tbody>
                @foreach($venta->detalles as $detalle)
                <tr class="item-row">
                    <td style="text-align: center;">{{ number_format($detalle->cantidad, 0) }}</td>
                    <td>{{ $detalle->producto->descripcion ?? '-' }}</td>
                    <td style="text-align: right;">{{ number_format($detalle->precio_unitario, 2) }}</td>
                    <td style="text-align: right;"><strong>{{ number_format($detalle->total, 2) }}</strong></td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="separador-punteado"></div>

        <div class="totales-container">
            <div class="total-linea">
                <span class="total-label">Gravada:</span>
                <span class="total-monto">S/ {{ number_format($venta->subtotal, 2) }}</span>
            </div>
            <div class="total-linea">
                <span class="total-label">IGV (18%):</span>
                <span class="total-monto">S/ {{ number_format($venta->igv, 2) }}</span>
            </div>
            <div class="total-final">
                <span>TOTAL A PAGAR:</span>
                <span>S/ {{ number_format($venta->total, 2) }}</span>
            </div>
        </div>

        <div class="monto-letras">
            SON: {{ convertirNumeroLetras($venta->total) }}
        </div>

        <div class="separador-punteado"></div>
        <div style="text-align: center; font-weight: bold; font-size: 11px; margin-bottom: 5px;">MÉTODOS DE PAGO</div>
        <div class="linea" style="justify-content: center; font-size: 11px;">
            <span>{{ strtoupper($venta->forma_pago) }}: <strong>S/ {{ number_format($venta->total, 2) }}</strong></span>
        </div>

        <div class="qr-container">
            <p>Representación impresa de la {{ $venta->tipo_comprobante }} ELECTRÓNICA</p>
            <p style="margin-top: 8px; font-weight: bold; font-size: 11px;">¡GRACIAS POR SU COMPRA!</p>
            <p>InfinityDev - Sistema de Gestión</p>
        </div>

        <div class="no-print" style="text-align: center; margin-top: 20px;">
            <button onclick="window.print()" style="padding: 10px 20px; background: #28a745; color: #fff; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;">
                🖨️ IMPRIMIR TICKET
            </button>
        </div>
    </div>
</body>
</html>