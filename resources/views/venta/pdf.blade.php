<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 1cm; }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #333;
            line-height: 1.4;
            font-size: 11px;
            margin: 0;
        }

        .factura { max-width: 100%; margin: auto; }

        /* CABECERA */
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .logo-img { max-width: 150px; height: auto; }
        .empresa-info { text-align: center; padding: 0 10px; }
        .empresa-nombre { font-size: 16px; font-weight: bold; text-transform: uppercase; }
        
        .ruc-box {
            width: 250px;
            border: 1px solid #333;
            text-align: center;
            padding: 10px;
            border-radius: 5px;
        }
        .ruc-text { font-size: 14px; font-weight: bold; }
        .tipo-comprobante { font-size: 13px; margin: 5px 0; background: #eee; padding: 5px; }

        /* BLOQUE CLIENTE */
        .info-box {
            width: 100%;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 15px;
            padding: 10px;
        }
        .label { font-weight: bold; width: 100px; }

        /* TABLA PRODUCTOS */
        .tabla-productos { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .tabla-productos th {
            background-color: #f2f2f2;
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }
        .tabla-productos td { border: 1px solid #ddd; padding: 7px; }

        /* SECCIÓN FINAL */
        .totales-container { width: 100%; margin-top: 10px; }
        .monto-letras {
            border: 1px solid #ddd;
            padding: 8px;
            border-radius: 4px;
            margin-bottom: 10px;
            background-color: #fafafa;
        }
        .tabla-totales { width: 220px; float: right; border-collapse: collapse; }
        .tabla-totales td { padding: 4px; border: none; }
        .total-row { font-weight: bold; font-size: 13px; border-top: 1px solid #333 !important; }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .footer { margin-top: 40px; text-align: center; font-size: 9px; color: #777; border-top: 1px dotted #ccc; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="factura">
        <table class="header-table">
            <tr>
                <td width="25%">
                    @if($empresa && $empresa->logo)
                        <img src="{{ public_path('storage/empresa/' . $empresa->logo) }}" class="logo-img">
                    @else
                        <div style="font-weight: bold; color: #ccc;">LOGO</div>
                    @endif
                </td>
                <td class="empresa-info">
                    <div class="empresa-nombre">{{ $empresa->razon_social ?? 'MI EMPRESA S.A.C' }}</div>
                    <div style="font-size: 9px;">
                        {{ $empresa->direccion ?? 'Dirección Fiscal' }}<br>
                        Teléfono: {{ $empresa->telefono ?? '-' }} | Email: {{ $empresa->email ?? '-' }}
                    </div>
                </td>
                <td width="35%">
                    <div class="ruc-box">
                        <div class="ruc-text">R.U.C. {{ $empresa->ruc ?? '00000000000' }}</div>
                        <div class="tipo-comprobante">
                            {{ $venta->tipo_comprobante == 'BOLETA' ? 'BOLETA DE VENTA' : 'FACTURA' }} ELECTRÓNICA
                        </div>
                        <div class="ruc-text">{{ $venta->documento }}</div>
                    </div>
                </td>
            </tr>
        </table>

        <div class="info-box">
            <table width="100%">
                <tr>
                    <td width="65%">
                        <table width="100%">
                            <tr><td class="label">NOMBRE:</td><td>{{ $venta->cliente->nombre_razon_social ?? 'CLIENTES VARIOS' }}</td></tr>
                            <tr><td class="label">N° DOC:</td><td>{{ $venta->cliente->numero_documento ?? '00000000' }}</td></tr>
                            <tr><td class="label">DIRECCIÓN:</td><td>{{ $venta->cliente->direccion ?? '-' }}</td></tr>
                        </table>
                    </td>
                    <td width="35%">
                        <table width="100%">
                            <tr><td class="label">FECHA:</td><td>{{ $venta->fecha_emision->format('d/m/Y H:i') }}</td></tr>
                            <tr><td class="label">MONEDA:</td><td>SOLES</td></tr>
                            <tr><td class="label">FORMA PAGO:</td><td>{{ $venta->forma_pago }}</td></tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>

        <table class="tabla-productos">
            <thead>
                <tr>
                    <th width="5%">#</th>
                    <th width="15%">CÓDIGO</th>
                    <th>DESCRIPCIÓN</th>
                    <th width="10%">CANT.</th>
                    <th width="10%">UND.</th>
                    <th width="12%">P. UNIT</th>
                    <th width="15%">TOTAL</th>
                </tr>
            </thead>
            <tbody>
                @foreach($venta->detalles as $detalle)
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td class="text-center">{{ $detalle->producto->codigo_interno ?? '-' }}</td>
                    <td>{{ $detalle->producto->descripcion ?? '-' }}</td>
                    <td class="text-center">{{ number_format($detalle->cantidad, 2) }}</td>
                    <td class="text-center">UNIDAD</td>
                    <td class="text-right">S/ {{ number_format($detalle->precio_unitario, 2) }}</td>
                    <td class="text-right">S/ {{ number_format($detalle->total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="monto-letras">
            <strong>SON:</strong> {{ convertirNumeroLetras($venta->total) }}
        </div>

        <div class="totales-container">
            <table class="tabla-totales">
                <tr>
                    <td>OP. GRAVADAS:</td>
                    <td class="text-right">S/ {{ number_format($venta->subtotal, 2) }}</td>
                </tr>
                <tr>
                    <td>I.G.V. (18%):</td>
                    <td class="text-right">S/ {{ number_format($venta->igv, 2) }}</td>
                </tr>
                <tr class="total-row">
                    <td>TOTAL A PAGAR:</td>
                    <td class="text-right">S/ {{ number_format($venta->total, 2) }}</td>
                </tr>
            </table>
            <div style="clear: both;"></div>
        </div>

        <div class="footer">
            <p>Representación impresa de la {{ $venta->tipo_comprobante == 'BOLETA' ? 'Boleta' : 'Factura' }} Electrónica.<br>
            Consulte su documento en: <strong>www.sunat.gob.pe</strong></p>
            <p>© {{ date('Y') }} - Generado por InfinityDev</p>
        </div>
    </div>
</body>
</html>

<?php
/**
 * Lógica de conversión (mantenemos la versión compatible)
 */
function convertirNumeroLetras($numero) {
    $numero = round($numero, 2);
    $partes = explode('.', number_format($numero, 2, '.', ''));
    $entero = (int)$partes[0];
    $decimal = $partes[1];

    $letras = ($entero == 0) ? 'CERO' : convertirBloque($entero);
    $texto = strtoupper($letras) . " CON " . $decimal . "/100 SOLES";
    
    return str_replace(['UN SOLES', 'UNO CON'], ['UN SOL', 'UN CON'], $texto);
}

function convertirBloque($num) {
    if ($num == 100) return "CIEN";
    $unidades = ['', 'UN', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE'];
    $decenas = ['', 'DIEZ', 'VEINTE', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];
    $centenas = ['', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS', 'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'];
    $especiales = [11=>'ONCE', 12=>'DOCE', 13=>'TRECE', 14=>'CATORCE', 15=>'QUINCE', 16=>'DIECISÉIS', 17=>'DIECISIETE', 18=>'DIECIOCHO', 19=>'DIECINUEVE', 21=>'VEINTIUNO', 22=>'VEINTIDÓS', 23=>'VEINTITRÉS', 24=>'VEINTICUATRO', 25=>'VEINTICINCO', 26=>'VEINTISÉIS', 27=>'VEINTISIETE', 28=>'VEINTIOCHO', 29=>'VEINTINUEVE'];

    $res = "";
    if ($num >= 1000000) {
        $mill = floor($num / 1000000); $num %= 1000000;
        $res .= ($mill == 1) ? "UN MILLÓN " : convertirBloque($mill) . " MILLONES ";
    }
    if ($num >= 1000) {
        $mil = floor($num / 1000); $num %= 1000;
        $res .= ($mil == 1) ? "MIL " : convertirBloque($mil) . " MIL ";
    }
    if ($num >= 100) {
        $c = floor($num / 100); $num %= 100;
        $res .= ($c == 1 && $num == 0) ? "CIEN" : $centenas[$c] . " ";
    }
    if ($num > 0) {
        if (isset($especiales[$num])) $res .= $especiales[$num];
        else {
            $d = floor($num / 10); $u = $num % 10;
            if ($d > 0) { $res .= $decenas[$d]; if ($u > 0) $res .= " Y " . $unidades[$u]; }
            else $res .= $unidades[$u];
        }
    }
    return trim($res);
}
?>