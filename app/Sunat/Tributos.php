<?php

namespace App\Sunat;

/**
 * Los tres ejes tributarios de un producto de minimarket, separados.
 *
 * Antes de esta clase `productos.operacion` cargaba con todo y no daba:
 *
 *   - AFECTACION IGV  (Catalogo 07 de SUNAT) — GRAVADO / EXONERADO / INAFECTO.
 *     Es lo unico que viaja en el comprobante electronico, linea por linea.
 *
 *   - ISC — una bebida energetica es GRAVADA de IGV *y ademas* esta en el
 *     ambito del ISC. Son dos hechos distintos sobre el mismo producto.
 *
 *   - IVAP (Ley 28211) — el arroz pilado tiene un tratamiento propio que no es
 *     ninguno de los tres valores de la Catalogo 07.
 *
 * Por que ISC e IVAP son banderas informativas y no un calculo:
 *
 * El ISC grava la venta a nivel de productor e importador. Un minimarket que le
 * compra a un distribuidor y revende NO declara ISC en sus boletas: le llega
 * incorporado en el costo. Marcar el producto sirve para identificarlo, analizar
 * margenes y responder ante una revision — no para emitir un tributo aparte.
 *
 * Si algun dia el negocio importa o produce, hara falta el sistema de calculo
 * (Catalogo 08) y la tasa por producto. Recien ahi conviene una tabla
 * `producto_tributos`; hoy serian dos booleanos con una junta de por medio.
 */
class Tributos
{
    /** Afectaciones del IGV que acepta el sistema. Son las que van al XML. */
    public const AFECTACIONES = ['GRAVADO', 'EXONERADO', 'INAFECTO'];

    /**
     * Marcador del pipeline de catalogo, NO un valor de negocio.
     *
     * Significa "todavia nadie determino como tributa este producto". Vive en
     * los CSV de data/catalogo-minimarket y tiene prohibido llegar a la tabla
     * productos: la validacion del controller y esVendible() lo bloquean.
     *
     * Ojo con el homonimo: `ventas.estado` tambien usa 'PENDIENTE', pero eso es
     * una venta a credito sin cobrar. Ejes distintos, tablas distintas.
     */
    public const PENDIENTE = 'PENDIENTE';

    public static function esAfectacionValida(?string $operacion): bool
    {
        return in_array($operacion, self::AFECTACIONES, true);
    }

    /**
     * Si un producto puede salir en una venta.
     *
     * Un producto sin afectacion resuelta no se vende: el comprobante saldria
     * con un IGV que nadie decidio. Es preferible frenar la venta a emitir un
     * documento mal.
     */
    public static function esVendible(?string $operacion): bool
    {
        return self::esAfectacionValida($operacion);
    }

    /** Etiqueta para la interfaz. */
    public static function etiqueta(?string $operacion): string
    {
        return match ($operacion) {
            'GRAVADO'       => 'Gravado - Operación Onerosa',
            'EXONERADO'     => 'Exonerado - Operación Onerosa',
            'INAFECTO'      => 'Inafecto - Operación Onerosa',
            self::PENDIENTE => 'Pendiente de clasificación tributaria',
            default         => (string) $operacion,
        };
    }

    /**
     * Combinaciones que ameritan que las mire un contador.
     *
     * No son errores: son avisos. El sistema no decide por el contribuyente,
     * pero tampoco deja pasar en silencio una combinacion sospechosa.
     *
     * @return array<int,string>
     */
    public static function advertencias(?string $operacion, bool $afectoIsc = false, bool $afectoIvap = false): array
    {
        $avisos = [];

        if (! self::esAfectacionValida($operacion)) {
            $avisos[] = "La afectación de IGV no está resuelta ({$operacion}).";
        }

        // El IVAP de la Ley 28211 sustituye al IGV en las operaciones que
        // alcanza. Un producto marcado como IVAP y a la vez GRAVADO de IGV no
        // es imposible —depende de que operacion haga el negocio— pero es
        // justo la combinacion que hay que revisar antes de facturar.
        if ($afectoIvap && $operacion === 'GRAVADO') {
            $avisos[] = 'Marcado como IVAP y GRAVADO de IGV a la vez: confirmar con el contador qué operación realiza el negocio.';
        }

        if ($afectoIsc && $operacion !== 'GRAVADO') {
            $avisos[] = 'Marcado como afecto al ISC pero no GRAVADO de IGV: revisar, es una combinación poco habitual.';
        }

        return $avisos;
    }
}
