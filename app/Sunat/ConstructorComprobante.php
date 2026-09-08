<?php

namespace App\Sunat;

use App\Models\Empresa;
use App\Models\NotaCredito;
use App\Models\NotaDebito;
use App\Models\Venta;
use Greenter\Model\Client\Client;
use Greenter\Model\Company\Address;
use Greenter\Model\Company\Company;
use Greenter\Model\Sale\Invoice;
use Greenter\Model\Sale\Note;
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
    /**
     * Punto de entrada unico: resuelve el tipo de documento.
     */
    public function desde(object $documento): Invoice|Note
    {
        return match (true) {
            $documento instanceof Venta       => $this->desdeVenta($documento),
            $documento instanceof NotaCredito => $this->desdeNota($documento, 'NOTA_CREDITO'),
            $documento instanceof NotaDebito  => $this->desdeNota($documento, 'NOTA_DEBITO'),
            default => throw new \InvalidArgumentException(
                'No sé cómo construir un comprobante desde ' . $documento::class
            ),
        };
    }

    /**
     * Nota de credito o debito.
     *
     * Una nota siempre modifica a otro comprobante: SUNAT exige declarar cual
     * es (tipo y numero) y por que motivo, con los codigos de los catalogos
     * 09 y 10. Sin esa referencia la nota se rechaza.
     */
    public function desdeNota(NotaCredito|NotaDebito $nota, string $tipo): Note
    {
        $nota->loadMissing(['cliente', 'venta', 'detalles']);

        $afectado = $nota->venta;

        if (! $afectado) {
            throw new \RuntimeException(
                "La nota {$nota->serie}-{$nota->numero} no referencia ningún comprobante."
            );
        }

        $lineas = $tipo === 'NOTA_CREDITO'
            ? $this->lineasDeProductos($nota)
            : $this->lineasDeConceptos($nota);

        $gravadas = Monto::redondear(array_sum(array_map(fn ($l) => $l->getMtoValorVenta(), $lineas)));
        $igv = Monto::redondear(array_sum(array_map(fn ($l) => $l->getIgv(), $lineas)));

        return (new Note())
            ->setUblVersion('2.1')
            ->setTipoDoc(Catalogo::comprobante($tipo))
            ->setSerie($nota->serie)
            ->setCorrelativo((string) $nota->numero)
            ->setFechaEmision($nota->fecha_emision)
            ->setTipoMoneda(config('sunat.moneda', 'PEN'))
            ->setCodMotivo($tipo === 'NOTA_CREDITO'
                ? Catalogo::motivoNotaCredito($nota->tipo_nota)
                : Catalogo::motivoNotaDebito($nota->tipo_nota))
            ->setDesMotivo($nota->motivo)
            ->setTipDocAfectado($afectado->tipo_comprobante_sunat)
            ->setNumDocfectado($afectado->serie . '-' . $afectado->numero)
            ->setClient($this->clienteDe($nota->cliente))
            ->setCompany($this->emisor())
            ->setMtoOperGravadas($gravadas)
            ->setMtoIGV($igv)
            ->setTotalImpuestos($igv)
            ->setMtoImpVenta(Monto::redondear((float) $nota->total))
            ->setDetails($lineas)
            ->setLegends([
                (new \Greenter\Model\Sale\Legend())
                    ->setCode('1000')
                    ->setValue(Letras::deMonto((float) $nota->total)),
            ]);
    }

    /** Lineas de una nota de credito: van contra productos reales. */
    private function lineasDeProductos(NotaCredito $nota): array
    {
        return $nota->detalles->map(function ($detalle) {
            $producto = $detalle->producto;
            $grava = $producto?->gravaIgv() ?? true;

            // Ojo: en las notas el precio NO incluye IGV — el controller lo
            // suma encima con agregarIgv(). Es al reves que en el terminal,
            // donde el precio de gondola ya lo trae.
            $unitario = Monto::agregarIgv((float) $detalle->precio_unitario, $grava);
            $linea = Monto::agregarIgv((float) $detalle->total, $grava);

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

    /**
     * Lineas de una nota de debito: son conceptos libres (intereses, gastos),
     * no productos del catalogo, asi que van con unidad ZZ (servicio).
     */
    private function lineasDeConceptos(NotaDebito $nota): array
    {
        return $nota->detalles->map(function ($detalle) {
            // Mismo criterio que la nota de credito: el importe no incluye IGV.
            $unitario = Monto::agregarIgv((float) $detalle->precio_unitario);
            $linea = Monto::agregarIgv((float) $detalle->total);

            return (new SaleDetail())
                ->setCodProducto('CONCEPTO')
                ->setUnidad('ZZ')
                ->setCantidad((float) $detalle->cantidad)
                ->setDescripcion($detalle->concepto)
                ->setMtoBaseIgv($linea['gravado'])
                ->setPorcentajeIgv(Monto::tasaIgv() * 100)
                ->setIgv($linea['igv'])
                ->setTipAfeIgv(Catalogo::AFECTACIONES_IGV['GRAVADO'])
                ->setTotalImpuestos($linea['igv'])
                ->setMtoValorVenta($linea['gravado'])
                ->setMtoValorUnitario($unitario['gravado'])
                ->setMtoPrecioUnitario($unitario['total'])
                ->setFactorIcbper(0);
        })->all();
    }

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
            // SUNAT exige la forma de pago en facturas desde 2021; sin esto
            // rechaza con el codigo 3244. Las boletas no la piden, pero
            // mandarla siempre no molesta y evita la asimetria.
            ->setFormaPago($this->formaPago($venta))
            ->setLegends([
                (new \Greenter\Model\Sale\Legend())
                    ->setCode('1000')             // monto en letras: obligatorio
                    ->setValue(Letras::deMonto((float) $venta->total)),
            ]);
    }

    private function formaPago(Venta $venta): \Greenter\Model\Sale\PaymentTerms
    {
        if ($venta->tipo_venta !== 'CREDITO') {
            return new \Greenter\Model\Sale\FormaPagos\FormaPagoContado();
        }

        // A credito va el saldo pendiente, no el total del comprobante.
        $pendiente = Monto::redondear((float) $venta->total - (float) $venta->pagado);

        return new \Greenter\Model\Sale\FormaPagos\FormaPagoCredito(
            $pendiente,
            config('sunat.moneda', 'PEN')
        );
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
        return $this->clienteDe($venta->cliente);
    }

    private function clienteDe(?\App\Models\Cliente $cliente): Client
    {
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
