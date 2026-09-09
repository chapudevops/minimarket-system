<?php

namespace App\Catalogo\Imagenes;

/**
 * En que punto esta la imagen de un producto.
 *
 * El estado es lo que hace incremental el enriquecimiento: sin el, cada corrida
 * volveria a consultar los miles de productos ya resueltos.
 */
class EstadoFoto
{
    /** Fotografia tomada por la tienda. Manda sobre todo lo demas. */
    public const PROPIA = 'PROPIA';

    /** Imagen externa cuya correspondencia con el producto se comprobo. */
    public const VERIFICADA = 'VERIFICADA';

    /** Hay candidata pero algo no cuadra. La mira una persona. */
    public const REVISAR = 'REVISAR';

    /** Se busco y no se encontro nada utilizable. */
    public const SIN_IMAGEN = 'SIN_IMAGEN';

    public const TODOS = [self::PROPIA, self::VERIFICADA, self::REVISAR, self::SIN_IMAGEN];

    /** Prioridad: PROPIA > VERIFICADA > REVISAR > SIN_IMAGEN. */
    private const PESO = [
        self::PROPIA => 3,
        self::VERIFICADA => 2,
        self::REVISAR => 1,
        self::SIN_IMAGEN => 0,
    ];

    /**
     * Si el enriquecimiento automatico puede tocar un producto en este estado.
     *
     * PROPIA jamas: una foto que alguien saco en la tienda vale mas que
     * cualquier cosa que encuentre un robot, y perderla no tiene vuelta atras.
     * VERIFICADA tampoco, para no volver a pedir lo que ya esta resuelto.
     */
    public static function sePuedeReemplazar(?string $estado): bool
    {
        return ! in_array($estado, [self::PROPIA, self::VERIFICADA], true);
    }

    /**
     * SIN_IMAGEN se puede reintentar pasado un tiempo: la fuente pudo haber
     * incorporado el producto. REVISAR no, porque espera a una persona.
     */
    public static function seReintenta(?string $estado, ?string $fechaConsulta, int $diasEspera = 30): bool
    {
        if ($estado !== self::SIN_IMAGEN) {
            return self::sePuedeReemplazar($estado);
        }

        if ($fechaConsulta === null || $fechaConsulta === '') {
            return true;
        }

        return $fechaConsulta < now()->subDays($diasEspera)->toDateString();
    }

    public static function gana(?string $nuevo, ?string $actual): bool
    {
        return (self::PESO[$nuevo] ?? -1) > (self::PESO[$actual] ?? -1);
    }
}
