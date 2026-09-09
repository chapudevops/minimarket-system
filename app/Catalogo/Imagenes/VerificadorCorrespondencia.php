<?php

namespace App\Catalogo\Imagenes;

use App\Catalogo\NormalizadorMarca;
use App\Catalogo\NormalizadorPresentacion;
use App\Catalogo\Texto;
use App\Models\Producto;

/**
 * Decide si una imagen candidata es de ESTE producto.
 *
 * Es la pieza que evita el error caro: que "Coca-Cola Original 500 ml" termine
 * con la foto de "Coca-Cola Zero 500 ml" o de la de 1.5 L. En un POS eso hace
 * que el cajero cobre lo que no es.
 *
 * La regla de fondo: que la fuente devuelva algo para un numero NO alcanza.
 * Un EAN puede estar mal cargado en el origen, o apuntar al producto padre de
 * una familia. Se compara ademas marca, presentacion y variante, y ante
 * cualquier contradiccion la respuesta es REVISAR, nunca VERIFICADA.
 */
class VerificadorCorrespondencia
{
    /**
     * Palabras que distinguen productos de la misma familia y el mismo tamano.
     *
     * Si una aparece en un lado y no en el otro, no son el mismo articulo por
     * mas que coincidan marca y mililitros.
     */
    private const VARIANTES = [
        'zero', 'sin azucar', 'light', 'diet', 'cero',
        'sin lactosa', 'deslactosada', 'descremada', 'semidescremada', 'entera',
        'integral', 'sin gluten', 'vegano',
        'original', 'clasico', 'clasica',
        'chocolate', 'fresa', 'vainilla', 'naranja', 'limon', 'durazno', 'lucuma',
        'menta', 'canela', 'coco', 'mango', 'piña',
    ];

    public function __construct(
        private readonly NormalizadorMarca $marcas,
        private readonly NormalizadorPresentacion $presentaciones,
    ) {}

    public static function porDefecto(): self
    {
        return new self(NormalizadorMarca::desdeArchivo(), new NormalizadorPresentacion());
    }

    /**
     * @return array{estado: string, motivos: array<int,string>}
     *         estado es VERIFICADA o REVISAR
     */
    public function verificar(Producto $producto, CandidataImagen $candidata): array
    {
        $motivos = [];

        if (trim($candidata->url) === '') {
            return ['estado' => EstadoFoto::SIN_IMAGEN, 'motivos' => ['la fuente no devolvió imagen']];
        }

        // 1. El EAN es la unica identidad fuerte. Si la fuente devuelve uno y
        //    no es el nuestro, la respuesta no corresponde a este producto.
        if ($candidata->ean !== null && $producto->codigo_barras !== null) {
            if ($this->soloDigitos($candidata->ean) !== $this->soloDigitos($producto->codigo_barras)) {
                $motivos[] = "el EAN de la fuente ({$candidata->ean}) no es el del producto ({$producto->codigo_barras})";
            }
        }

        // 2. Marca.
        if ($candidata->marca !== null && $producto->marca !== null) {
            if ($this->marcas->clave($candidata->marca) !== $this->marcas->clave($producto->marca)) {
                $motivos[] = "la marca no coincide ({$candidata->marca} vs {$producto->marca})";
            }
        }

        // 3. Presentacion: 500 ml y 1.5 L son productos distintos.
        if ($candidata->presentacion !== null && $producto->presentacion !== null) {
            $unaU = $this->presentaciones->clave($candidata->presentacion);
            $otraU = $this->presentaciones->clave($producto->presentacion);

            if ($unaU !== '' && $otraU !== '' && $unaU !== $otraU) {
                $motivos[] = "la presentación no coincide ({$candidata->presentacion} vs {$producto->presentacion})";
            }
        }

        // 4. Variante: mismo tamano y misma marca no basta.
        foreach ($this->variantesDiscrepantes($producto, $candidata) as $variante) {
            $motivos[] = "la variante no coincide: '{$variante}' aparece en un lado y no en el otro";
        }

        return [
            'estado' => $motivos === [] ? EstadoFoto::VERIFICADA : EstadoFoto::REVISAR,
            'motivos' => $motivos,
        ];
    }

    /** @return array<int,string> */
    private function variantesDiscrepantes(Producto $producto, CandidataImagen $candidata): array
    {
        if ($candidata->nombre === null) {
            return [];
        }

        $nuestro = ' '.Texto::plano($producto->descripcion.' '.$producto->presentacion).' ';
        $suyo = ' '.Texto::plano($candidata->nombre.' '.($candidata->presentacion ?? '')).' ';

        $discrepantes = [];

        foreach (self::VARIANTES as $variante) {
            $aca = str_contains($nuestro, ' '.$variante.' ');
            $alla = str_contains($suyo, ' '.$variante.' ');

            if ($aca !== $alla) {
                $discrepantes[] = $variante;
            }
        }

        return $discrepantes;
    }

    private function soloDigitos(string $valor): string
    {
        return ltrim(preg_replace('/\D/', '', $valor), '0');
    }
}
