<?php

namespace App\Catalogo\Imagenes\Fuentes;

use App\Catalogo\Imagenes\CandidataImagen;
use App\Catalogo\Imagenes\FuenteEnrutablePorCategoria;
use App\Catalogo\Imagenes\FuenteImagen;

/**
 * Open Food Facts y sus proyectos hermanos, consultados por codigo de barras.
 *
 * Es la unica fuente del registro con una licencia que permite reutilizar las
 * imagenes: CC BY-SA 3.0, con atribucion. Las cuatro variantes comparten la
 * misma API y solo cambian de host segun el tipo de producto, asi que se
 * resuelven con una sola clase que enruta por la categoria del catalogo.
 *
 * Devuelve marca, nombre y presentacion ademas de la URL. No es un extra: sin
 * esos tres campos VerificadorCorrespondencia no puede distinguir la Coca-Cola
 * Original de la Zero, y el POS terminaria mostrando la foto equivocada.
 */
class FuenteOpenFoodFacts implements FuenteEnrutablePorCategoria, FuenteImagen
{
    public const NOMBRE = 'OPENFOODFACTS';

    /** Categoria del catalogo => host del proyecto que le corresponde. */
    private const HOSTS = [
        'HIGIENE PERSONAL' => 'world.openbeautyfacts.org',
        'LIMPIEZA' => 'world.openproductsfacts.org',
        'MASCOTAS' => 'world.openpetfoodfacts.org',
    ];

    private const HOST_ALIMENTOS = 'world.openfoodfacts.org';

    /** Idiomas en los que se busca la imagen frontal, por orden. */
    private const IDIOMAS = ['es', 'en', 'fr', 'de'];

    public function __construct(
        private readonly ClienteOpenFoodFacts $cliente,
        /** Categoria del producto que se esta consultando. La fija el enriquecedor. */
        private string $categoria = '',
    ) {}

    public function nombre(): string
    {
        return self::NOMBRE;
    }

    /** El enriquecedor la usa para enrutar al proyecto correcto. */
    public function paraCategoria(string $categoria): self
    {
        $this->categoria = strtoupper(trim($categoria));

        return $this;
    }

    public function host(): string
    {
        return self::HOSTS[strtoupper(trim($this->categoria))] ?? self::HOST_ALIMENTOS;
    }

    public function buscarPorEan(string $ean): ?CandidataImagen
    {
        $producto = $this->cliente->producto($this->host(), $ean);

        if ($producto === null) {
            return null;
        }

        $url = $this->imagenFrontal($producto);

        // Un producto en OFF sin foto frontal existe y es normal: alguien cargo
        // los datos pero no la imagen. No es un error, es que no hay imagen.
        if ($url === null) {
            return null;
        }

        return new CandidataImagen(
            fuente: self::NOMBRE,
            url: $url,
            marca: $this->primeraMarca($producto['brands'] ?? null),
            nombre: $this->texto($producto, ['product_name_es', 'product_name']),
            presentacion: $this->texto($producto, ['quantity', 'product_quantity']),
            ean: isset($producto['code']) ? (string) $producto['code'] : null,
        );
    }

    /**
     * URL de la foto de frente.
     *
     * Se prefiere la version "display" de selected_images, que es la que el
     * propio OFF eligio como frontal y viene ya redimensionada. image_front_url
     * queda de respaldo.
     */
    private function imagenFrontal(array $producto): ?string
    {
        $display = $producto['selected_images']['front']['display'] ?? null;

        if (is_array($display)) {
            foreach (self::IDIOMAS as $idioma) {
                if (! empty($display[$idioma])) {
                    return (string) $display[$idioma];
                }
            }

            // Cualquier idioma antes que nada.
            foreach ($display as $url) {
                if (is_string($url) && $url !== '') {
                    return $url;
                }
            }
        }

        foreach (['image_front_url', 'image_url'] as $campo) {
            if (! empty($producto[$campo])) {
                return (string) $producto[$campo];
            }
        }

        return null;
    }

    /**
     * OFF guarda las marcas como lista separada por comas ("Coca-Cola, Coca
     * Cola Company"). Para comparar alcanza con la primera.
     */
    private function primeraMarca(?string $marcas): ?string
    {
        if ($marcas === null || trim($marcas) === '') {
            return null;
        }

        return trim(explode(',', $marcas)[0]) ?: null;
    }

    /** @param array<int,string> $campos */
    private function texto(array $producto, array $campos): ?string
    {
        foreach ($campos as $campo) {
            $valor = trim((string) ($producto[$campo] ?? ''));

            if ($valor !== '') {
                return $valor;
            }
        }

        return null;
    }
}
