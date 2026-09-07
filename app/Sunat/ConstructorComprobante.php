<?php

namespace App\Sunat;

use App\Models\Empresa;
use App\Models\Venta;
use Greenter\Model\Client\Client;
use Greenter\Model\Company\Address;
use Greenter\Model\Company\Company;
use Greenter\Model\Sale\Invoice;
use Greenter\Model\Sale\SaleDetail;

/**
 * Traduce una Venta del sistema al modelo de comprobante de Greenter.
 *
 * El punto delicado es el IGV: en el terminal el precio de gondola YA lo
 * incluye, mientras que SUNAT pide desglosado el valor unitario sin impuesto y
 * el precio unitario con impuesto. Aca se desagrega una sola vez, en
 * Monto::desagregarIgv, para que las lineas sumen exactamente el total cobrado.
 */
class ConstructorComprobante
{
    public function desdeVenta(Venta $venta): Invoice
    {
        $venta->loadMissing(['cliente', 'detalles.producto']);

        $lineas = $this->lineas($venta);

        $gravadas = 0.0;
        $exoneradas = 0.0;
        $inafectas = 0.0;
        $igvTotal = 0.0;

        foreach ($lineas as $linea) {
            $igvTotal += $linea->getIgv();

            match ($linea->getTipAfeIgv()) {
                Catalogo::AFECTACIONES_IGV['EXONERADO'] => $exoneradas += $linea->getMtoValorVenta(),
                Catalogo::AFECTACIONES_IGV['INAFECTO']  => $inafectas += $linea->getMtoValorVenta(),
                default                                 => $gravadas += $linea->getMtoValorVenta(),
            };
        }

        $gravadas = Monto::redondear($gravadas);
        $igvTotal = Monto::redondear($igvTotal);
        $valorVenta = Monto::redondear($gravadas + $exoneradas + $inafectas);

        return (new Invoice())
            ->setUblVersion('2.1')
            ->setTipoOperacion('0101')            // venta interna
            ->setTipoDoc($venta->tipo_comprobante_sunat)
            ->setSerie($venta->serie)
            ->setCorrelativo((string) $venta->numero)
            ->setFechaEmision($venta->fecha_emision)
            ->setTipoMoneda(config('sunat.moneda', 'PEN'))
            ->setClient($this->cliente($venta))
            ->setCompany($this->emisor())
            ->setMtoOperGravadas($gravadas)
            ->setMtoOperExoneradas(Monto::redondear($exoneradas))
            ->setMtoOperInafectas(Monto::redondear($inafectas))
            ->setMtoIGV($igvTotal)
            ->setTotalImpuestos($igvTotal)
            ->setValorVenta($valorVenta)
            ->setSubTotal(Monto::redondear($valorVenta + $igvTotal))
            ->setMtoImpVenta(Monto::redondear((float) $venta->total))
            ->setDetails($lineas)
            ->setLegends([
                (new \Greenter\Model\Sale\Legend())
                    ->setCode('1000')             // monto en letras: obligatorio
                    ->setValue(Letras::deMonto((float) $venta->total)),
            ]);
    }

    /** @return SaleDetail[] */
    private function lineas(Venta $venta): array
    {
        return $venta->detalles->map(function ($detalle) {
            $producto = $detalle->producto;
            $grava = $producto?->gravaIgv() ?? true;

            // precio_unitario viene con IGV incluido
            $unitario = Monto::desagregarIgv((float) $detalle->precio_unitario, $grava);
            $linea = Monto::desagregarIgv((float) $detalle->total, $grava);

            return (new SaleDetail())
                ->setCodProducto($producto?->codigo_interno ?? '')
                ->setUnidad($producto?->unidad_sunat ?? 'NIU')
                ->setCantidad((float) $detalle->cantidad)
                ->setDescripcion($producto?->descripcion ?? 'Producto')
                ->setMtoBaseIgv($linea['gravado'])
                ->setPorcentajeIgv($grava ? Monto::tasaIgv() * 100 : 0)
                ->setIgv($linea['igv'])
                ->setTipAfeIgv($producto?->afectacion_igv_sunat ?? Catalogo::AFECTACIONES_IGV['GRAVADO'])
                ->setTotalImpuestos($linea['igv'])
                ->setMtoValorVenta($linea['gravado'])
                ->setMtoValorUnitario($unitario['gravado'])
                ->setMtoPrecioUnitario($unitario['total'])
                ->setFactorIcbper(0);
        })->all();
    }

    private function cliente(Venta $venta): Client
    {
        $cliente = $venta->cliente;

        return (new Client())
            ->setTipoDoc($cliente?->tipo_documento_sunat ?? Catalogo::DOCUMENTOS_IDENTIDAD['SIN_DOCUMENTO'])
            ->setNumDoc($cliente?->numero_documento ?? '')
            ->setRznSocial($cliente?->nombre_razon_social ?? 'CLIENTE VARIOS')
            ->setAddress((new Address())->setDireccion($cliente?->direccion ?? '-'));
    }

    private function emisor(): Company
    {
        $empresa = Empresa::first();

        if (! $empresa) {
            throw new \RuntimeException('No hay datos de empresa cargados: completá la configuración antes de emitir.');
        }

        return (new Company())
            ->setRuc($empresa->ruc)
            ->setRazonSocial($empresa->razon_social)
            ->setNombreComercial($empresa->nombre_comercial ?? $empresa->razon_social)
            ->setAddress(
                (new Address())
                    ->setUbigueo('150101')          // TODO: la empresa aun no guarda ubigeo
                    ->setDepartamento($empresa->departamento)
                    ->setProvincia($empresa->provincia)
                    ->setDistrito($empresa->distrito)
                    ->setUrbanizacion('-')
                    ->setDireccion($empresa->direccion)
                    ->setCodLocal('0000')
            );
    }
}
