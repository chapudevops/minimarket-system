<?php

namespace App\Sunat;

/**
 * Traduccion de los valores que usa el sistema a los codigos que exige SUNAT.
 *
 * Se resuelve por mapeo y no por columnas duplicadas: el texto sigue siendo la
 * etiqueta que ve el usuario y el codigo se deriva, asi no hay dos fuentes de
 * verdad que puedan quedar descuadradas.
 *
 * Referencias: Anexo V de la Resolucion de Superintendencia 097-2012 y sus
 * modificatorias.
 */
class Catalogo
{
    /** Catalogo 01 — Tipo de comprobante. */
    public const COMPROBANTES = [
        'FACTURA'       => '01',
        'BOLETA'        => '03',
        'NOTA_CREDITO'  => '07',
        'NOTA_DEBITO'   => '08',
        'GUIA_REMISION' => '09',
    ];

    /** Catalogo 06 — Tipo de documento de identidad del adquiriente. */
    public const DOCUMENTOS_IDENTIDAD = [
        'SIN_DOCUMENTO' => '0',
        'DNI'           => '1',
        'CE'            => '4',
        'RUC'           => '6',
        'PASAPORTE'     => '7',
    ];

    /** Catalogo 03 — Unidad de medida (UN/ECE rec. 20). */
    public const UNIDADES = [
        'UNIDAD'   => 'NIU',
        'PAQUETE'  => 'PK',
        'CAJA'     => 'BX',
        'DOCENA'   => 'DZN',
        'KG'       => 'KGM',
        'LITRO'    => 'LTR',
        'HORA'     => 'HUR',
        'MES'      => 'MON',
        'SERVICIO' => 'ZZ',
    ];

    /** Catalogo 07 — Tipo de afectacion del IGV. */
    public const AFECTACIONES_IGV = [
        'GRAVADO'   => '10',
        'EXONERADO' => '20',
        'INAFECTO'  => '30',
    ];

    /**
     * Catalogo 09 — Tipo de nota de credito.
     * Las claves son los valores que ofrece el formulario del sistema.
     */
    public const MOTIVOS_NOTA_CREDITO = [
        'ANULACION'  => '01',   // Anulacion de la operacion
        'DESCUENTO'  => '04',   // Descuento global
        'DEVOLUCION' => '06',   // Devolucion total
        'OTRO'       => '10',   // Otros conceptos
    ];

    /** Catalogo 10 — Tipo de nota de debito. */
    public const MOTIVOS_NOTA_DEBITO = [
        'INTERESES' => '01',   // Intereses por mora
        'GASTOS'    => '03',   // Penalidades / otros conceptos
        'OTRO'      => '03',
    ];

    /**
     * Catalogo 05 — Codigo de tributo asociado a cada afectacion.
     * Cada linea del comprobante declara cual le corresponde.
     */
    public const TRIBUTOS = [
        'GRAVADO'   => ['codigo' => '1000', 'nombre' => 'IGV',  'tipo' => 'VAT'],
        'EXONERADO' => ['codigo' => '9997', 'nombre' => 'EXO',  'tipo' => 'VAT'],
        'INAFECTO'  => ['codigo' => '9998', 'nombre' => 'INA',  'tipo' => 'FRE'],
    ];

    public static function comprobante(?string $valor): ?string
    {
        return self::COMPROBANTES[$valor] ?? null;
    }

    public static function documentoIdentidad(?string $valor): string
    {
        return self::DOCUMENTOS_IDENTIDAD[$valor] ?? self::DOCUMENTOS_IDENTIDAD['SIN_DOCUMENTO'];
    }

    /** Ante una unidad no mapeada devuelve NIU, que es el comodin de SUNAT. */
    public static function unidad(?string $valor): string
    {
        return self::UNIDADES[$valor] ?? 'NIU';
    }

    public static function afectacionIgv(?string $valor): string
    {
        return self::AFECTACIONES_IGV[$valor] ?? self::AFECTACIONES_IGV['GRAVADO'];
    }

    public static function tributo(?string $operacion): array
    {
        return self::TRIBUTOS[$operacion] ?? self::TRIBUTOS['GRAVADO'];
    }

    /** Codigo de motivo de una nota de credito (catalogo 09). */
    public static function motivoNotaCredito(?string $valor): string
    {
        return self::MOTIVOS_NOTA_CREDITO[$valor] ?? self::MOTIVOS_NOTA_CREDITO['OTRO'];
    }

    /** Codigo de motivo de una nota de debito (catalogo 10). */
    public static function motivoNotaDebito(?string $valor): string
    {
        return self::MOTIVOS_NOTA_DEBITO[$valor] ?? self::MOTIVOS_NOTA_DEBITO['OTRO'];
    }

    /** true si la operacion paga IGV. */
    public static function gravaIgv(?string $operacion): bool
    {
        return ($operacion ?? 'GRAVADO') === 'GRAVADO';
    }
}
