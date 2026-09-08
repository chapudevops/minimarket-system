<?php

namespace App\Catalogo\Fuentes;

use App\Catalogo\CodigoBarras;
use App\Catalogo\EsquemaRaw;
use App\Catalogo\Texto;

/**
 * Convierte el catalogo publico de una tienda VTEX en filas RAW.
 *
 * --------------------------------------------------------------------------
 * Que se copia y que no
 * --------------------------------------------------------------------------
 *
 * Se copia lo que la tienda publica y se puede volver a verificar entrando a
 * la URL: nombre, marca, categoria, presentacion, precio de gondola y EAN.
 * Cada fila guarda su url_fuente y su fecha_consulta, que es lo que convierte
 * al dato en verificable y no en una afirmacion nuestra.
 *
 * No se copia, ni se deduce:
 *
 *   precio_compra   la gondola de un supermercado no es el costo del
 *                   minimarket. Sale de la lista del proveedor y hasta que
 *                   exista queda NULL.
 *   codigo_barras   solo si el GTIN valida el digito verificador Y tiene
 *                   largo de fabricante. Lo demas es NULL.
 *   stock           el catalogo describe productos, no existencias.
 *   operacion       la decide ReglasTributarias, no el nombre del producto.
 *
 * --------------------------------------------------------------------------
 * Que se descarta
 * --------------------------------------------------------------------------
 *
 * Packs y combos armados por la tienda ("Pack 2un", "Combo Rostizado + Gaseosa"):
 * no son un producto del catalogo del minimarket, son una promocion del
 * retailer. Entrarian como referencias fantasma que nadie va a poder comprarle
 * a un distribuidor.
 */
class ExtractorVtex
{
    /**
     * Marcadores de armado propio del retailer en el nombre.
     *
     * Se buscan sobre la clave normalizada, asi que "Pack 2 Un" y "PACK 2UN"
     * caen igual.
     */
    private const ARMADOS = ['pack', 'combo', 'tripack', 'sixpack', 'twopack', 'multipack'];

    private int $descartadosPack = 0;

    private int $descartadosSinCategoria = 0;

    private int $descartadosSinNombre = 0;

    private int $conEan = 0;

    private int $sinEan = 0;

    private int $eanInvalido = 0;

    /** GTIN validos pero de largo interno del retailer (GTIN-8). */
    private int $eanInterno = 0;

    public function __construct(
        private readonly string $fuente,
        private readonly MapeoFuentes $mapeo,
    ) {}

    /**
     * Aplana un producto VTEX en una fila RAW por SKU vendible.
     *
     * Un "producto" VTEX puede tener varios items (sabores, tamanos). Cada
     * item es una referencia distinta para el minimarket, con su propio EAN.
     *
     * @param  array<string,mixed>  $producto
     * @return array<int,array<string,string>>
     */
    public function filasDe(array $producto, string $host, string $fecha): array
    {
        $rutas = array_values(array_filter((array) ($producto['categories'] ?? [])));
        $destino = $this->mapeo->resolver($this->fuente, $rutas);

        if ($destino === null) {
            $this->descartadosSinCategoria++;

            return [];
        }

        $marca = trim((string) ($producto['brand'] ?? ''));
        $url = $this->url($producto, $host);
        $filas = [];

        foreach ((array) ($producto['items'] ?? []) as $item) {
            $nombre = trim((string) ($item['nameComplete'] ?? $item['name'] ?? $producto['productName'] ?? ''));

            if ($nombre === '') {
                $this->descartadosSinNombre++;

                continue;
            }

            if ($this->esArmadoDeTienda($nombre)) {
                $this->descartadosPack++;

                continue;
            }

            $filas[] = [
                'fuente'            => $this->fuente,
                'categoria'         => $destino['categoria'],
                'subcategoria'      => $destino['subcategoria'],
                'producto_tipo'     => $destino['producto_tipo'],
                'marca'             => $marca,
                'descripcion'       => $nombre,
                'presentacion'      => $this->presentacion($nombre),
                'codigo_barras'     => $this->ean($item),
                'precio_referencia' => $this->precio($item),
                'url_fuente'        => $url,
                'fecha_consulta'    => $fecha,
            ];
        }

        return $filas;
    }

    /** @return array<string,int> */
    public function estadisticas(): array
    {
        return [
            'descartados_pack'           => $this->descartadosPack,
            'descartados_sin_categoria'  => $this->descartadosSinCategoria,
            'descartados_sin_nombre'     => $this->descartadosSinNombre,
            'con_ean'                    => $this->conEan,
            'sin_ean'                    => $this->sinEan,
            'ean_invalido'               => $this->eanInvalido,
            'ean_interno_retailer'       => $this->eanInterno,
        ];
    }

    /**
     * El EAN del item, solo si es un GTIN de fabricante verificable.
     *
     * Se distingue "no vino" de "vino mal": el segundo caso se cuenta aparte
     * porque si crece mucho quiere decir que la fuente cambio de formato y
     * conviene mirarlo, no que los productos no tengan codigo.
     */
    private function ean(array $item): string
    {
        $crudo = trim((string) ($item['ean'] ?? ''));

        if ($crudo === '') {
            $this->sinEan++;

            return '';
        }

        // Se separan dos cosas que no son lo mismo: un codigo que no cierra el
        // digito verificador (dato roto, si crece hay que mirar la fuente) y un
        // GTIN-8 valido que descartamos por politica (codigo interno de tienda).
        if (! CodigoBarras::esValido($crudo)) {
            $this->eanInvalido++;
            $this->sinEan++;

            return '';
        }

        $gtin = CodigoBarras::paraCatalogo($crudo);

        if ($gtin === null) {
            $this->eanInterno++;
            $this->sinEan++;

            return '';
        }

        $this->conEan++;

        return $gtin;
    }

    /** Precio de gondola del primer vendedor. Referencia, no costo. */
    private function precio(array $item): string
    {
        $oferta = ($item['sellers'][0]['commertialOffer'] ?? null);
        $precio = $oferta['Price'] ?? null;

        return is_numeric($precio) && $precio > 0 ? number_format((float) $precio, 2, '.', '') : '';
    }

    /**
     * Extrae la presentacion del nombre publicado.
     *
     * Las tiendas la ponen al final: "Gaseosa COCA COLA Botella 1.5L". Si no
     * aparece una medida reconocible se devuelve vacio y el normalizador se
     * arregla con lo que haya; inventar "1 unidad" seria afirmar algo que la
     * fuente no dice.
     */
    private function presentacion(string $nombre): string
    {
        $unidades = 'ml|mL|ML|l|L|lt|Lt|LT|litros?|g|gr|G|GR|kg|Kg|KG|kilos?|cc|un|und|unid|unidades?|pack|rollos?|sobres?|capsulas?|tabletas?';

        if (preg_match('/(\d+(?:[.,]\d+)?)\s*('.$unidades.')\b/u', $nombre, $m)) {
            return trim($m[1].' '.$m[2]);
        }

        return '';
    }

    private function esArmadoDeTienda(string $nombre): bool
    {
        $clave = Texto::clave($nombre);

        foreach (self::ARMADOS as $marcador) {
            if (str_contains($clave, $marcador)) {
                return true;
            }
        }

        return false;
    }

    private function url(array $producto, string $host): string
    {
        $enlace = trim((string) ($producto['linkText'] ?? ''));

        if ($enlace !== '') {
            return "https://{$host}/{$enlace}/p";
        }

        return trim((string) ($producto['link'] ?? "https://{$host}"));
    }

    /** Las columnas que escribe, en el orden del esquema RAW. */
    public static function columnas(): array
    {
        return EsquemaRaw::COLUMNAS;
    }
}
