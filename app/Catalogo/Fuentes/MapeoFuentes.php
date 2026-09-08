<?php

namespace App\Catalogo\Fuentes;

use App\Catalogo\Csv;
use App\Catalogo\Rutas;
use App\Catalogo\Texto;

/**
 * Traduce el arbol de categorias de una tienda al del catalogo propio.
 *
 * Las tiendas agrupan distinto que nosotros y a veces mas grueso: Plaza Vea
 * mete galletas, caramelos y chicles en "Galletas y Golosinas", y ahi no se
 * puede decidir la subcategoria sin mirar el producto. Por eso el diccionario
 * mapea la ruta MAS ESPECIFICA que haga falta —dos o tres niveles— y gana la
 * coincidencia mas larga.
 *
 * Una ruta que no esta en el diccionario NO se adivina: el producto se
 * descarta y queda contado como "categoria sin mapear". Preferimos 400
 * productos bien clasificados que 900 con la categoria puesta a ojo.
 */
class MapeoFuentes
{
    /** @var array<string,array<string,string>> clave normalizada => destino */
    private array $reglas = [];

    /** @var array<string,int> rutas de origen que no matchearon */
    private array $sinMapear = [];

    /** @param array<int,array<string,string>> $filas */
    public function __construct(array $filas = [])
    {
        foreach ($filas as $fila) {
            $fuente = strtoupper(trim((string) ($fila['fuente'] ?? '')));
            $ruta = trim((string) ($fila['ruta_fuente'] ?? ''));

            if ($fuente === '' || $ruta === '') {
                continue;
            }

            $this->reglas[$this->clave($fuente, $ruta)] = [
                'categoria'     => strtoupper(trim((string) ($fila['categoria'] ?? ''))),
                'subcategoria'  => strtoupper(trim((string) ($fila['subcategoria'] ?? ''))),
                'producto_tipo' => strtoupper(trim((string) ($fila['producto_tipo'] ?? ''))),
                'ruta'          => $ruta,
            ];
        }
    }

    public static function desdeArchivo(?string $ruta = null): self
    {
        return new self(Csv::leer($ruta ?? Rutas::diccionario('mapeo_fuentes.csv')));
    }

    /**
     * Resuelve el destino a partir de las rutas que declara el producto.
     *
     * VTEX devuelve el arbol completo de mas especifico a mas general
     * (["/Bebidas/Gaseosas/Familiares/", "/Bebidas/Gaseosas/", "/Bebidas/"]),
     * asi que se prueba en ese orden y la primera que matchee es la mejor.
     *
     * @param  array<int,string>  $rutas
     * @return array<string,string>|null
     */
    public function resolver(string $fuente, array $rutas): ?array
    {
        $fuente = strtoupper($fuente);

        foreach ($rutas as $ruta) {
            $destino = $this->reglas[$this->clave($fuente, $ruta)] ?? null;

            if ($destino !== null) {
                return $destino;
            }
        }

        if ($rutas !== []) {
            $mas = $rutas[0];
            $this->sinMapear[$mas] = ($this->sinMapear[$mas] ?? 0) + 1;
        }

        return null;
    }

    /**
     * Categorias a recorrer, como segmentos de slug.
     *
     * Se recorre por el segundo nivel aunque el diccionario clasifique por el
     * tercero: pedir "/abarrotes/galletas-y-golosinas" trae todo de una vez y
     * despues cada producto se clasifica por la ruta que el mismo declara. Es
     * una peticion en lugar de seis, y ademas no se pierde nada si la tienda
     * agrega una subcategoria nueva.
     *
     * @return array<int,array{ruta:string,segmentos:array<int,string>}>
     */
    public function rutasDe(string $fuente): array
    {
        $fuente = strtoupper($fuente);
        $rutas = [];

        foreach ($this->reglas as $clave => $regla) {
            if (! str_starts_with($clave, $fuente.'|')) {
                continue;
            }

            $segmentos = array_slice($this->segmentos($regla['ruta']), 0, 2);

            if (count($segmentos) < 2) {
                continue;
            }

            $rutas[implode('/', $segmentos)] = [
                'ruta'      => '/'.implode('/', array_slice($this->partes($regla['ruta']), 0, 2)).'/',
                'segmentos' => $segmentos,
            ];
        }

        ksort($rutas);

        return array_values($rutas);
    }

    /** @return array<string,int> */
    public function sinMapear(): array
    {
        arsort($this->sinMapear);

        return $this->sinMapear;
    }

    public function total(): int
    {
        return count($this->reglas);
    }

    /**
     * "/Bebidas/Jugos y Otras Bebidas/Té Bebible/" => ["bebidas","jugos-y-otras-bebidas","te-bebible"]
     *
     * @return array<int,string>
     */
    private function segmentos(string $ruta): array
    {
        return array_map($this->slug(...), $this->partes($ruta));
    }

    /** @return array<int,string> */
    private function partes(string $ruta): array
    {
        return array_values(array_filter(
            array_map('trim', explode('/', $ruta)),
            fn (string $parte) => $parte !== '',
        ));
    }

    /**
     * El slug tal como lo arma VTEX para la URL de categoria: minusculas, sin
     * tildes, y un guion por cada tramo de caracteres que no sean letra o
     * numero. "Café e Infusiones" => "cafe-e-infusiones".
     */
    private function slug(string $parte): string
    {
        return trim(preg_replace('/-+/', '-', preg_replace('/[^a-z0-9]+/', '-', Texto::plano($parte)) ?? '') ?? '', '-');
    }

    private function clave(string $fuente, string $ruta): string
    {
        return $fuente.'|'.Texto::clave($ruta);
    }
}
