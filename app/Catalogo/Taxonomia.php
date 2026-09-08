<?php

namespace App\Catalogo;

/**
 * Categorias y subcategorias del catalogo, con su codigo de tres letras.
 *
 * La tabla productos no tiene columnas categoria ni subcategoria (ver
 * database/mysql-sqlserver/bd.sql), asi que la clasificacion viaja dentro del
 * codigo interno: BEB-GAS-000001. El CSV maestro si las lleva como columnas
 * para poder auditar de donde salio cada codigo.
 *
 * Este diccionario NO opina sobre tributos: eso vive en reglas_tributarias.csv
 * y lo resuelve ReglasTributarias. Son dos preguntas distintas —donde va el
 * producto en el catalogo, y como tributa— y mezclarlas fue lo que hizo que
 * `operacion` significara varias cosas a la vez.
 */
class Taxonomia
{
    /** Unidades que ofrece el formulario de productos (modal-create.blade.php). */
    public const UNIDADES_VALIDAS = ['UNIDAD', 'KG', 'LITRO', 'DOCENA', 'CAJA', 'HORA', 'MES'];

    /** @var array<string,array<string,mixed>> "categoria|subcategoria" => definicion */
    private array $entradas = [];

    /** @param array<int,array<string,string>> $filas */
    public function __construct(array $filas = [])
    {
        foreach ($filas as $fila) {
            $clave = $this->clave($fila['categoria'] ?? '', $fila['subcategoria'] ?? '');

            $this->entradas[$clave] = [
                'categoria'                => trim((string) ($fila['categoria'] ?? '')),
                'subcategoria'             => trim((string) ($fila['subcategoria'] ?? '')),
                'codigo_categoria'         => strtoupper(trim((string) ($fila['codigo_categoria'] ?? ''))),
                'codigo_subcategoria'      => strtoupper(trim((string) ($fila['codigo_subcategoria'] ?? ''))),
                'unidad_sugerida'          => strtoupper(trim((string) ($fila['unidad_sugerida'] ?? 'UNIDAD'))),
            ];
        }
    }

    public static function desdeArchivo(?string $ruta = null): self
    {
        return new self(Csv::leer($ruta ?? Rutas::diccionario('taxonomia.csv')));
    }

    /** @return array<string,mixed>|null null si la combinacion no esta declarada. */
    public function buscar(?string $categoria, ?string $subcategoria): ?array
    {
        return $this->entradas[$this->clave((string) $categoria, (string) $subcategoria)] ?? null;
    }

    /**
     * Prefijo del codigo interno: "BEB-GAS".
     *
     * @return string|null null si la subcategoria no esta declarada; sin
     *                     prefijo no hay codigo y la fila se rechaza.
     */
    public function prefijo(?string $categoria, ?string $subcategoria): ?string
    {
        $entrada = $this->buscar($categoria, $subcategoria);

        if ($entrada === null || $entrada['codigo_categoria'] === '' || $entrada['codigo_subcategoria'] === '') {
            return null;
        }

        return $entrada['codigo_categoria'] . '-' . $entrada['codigo_subcategoria'];
    }

    public function unidad(?string $categoria, ?string $subcategoria): string
    {
        $unidad = $this->buscar($categoria, $subcategoria)['unidad_sugerida'] ?? 'UNIDAD';

        return in_array($unidad, self::UNIDADES_VALIDAS, true) ? $unidad : 'UNIDAD';
    }

    /** @return array<int,array<string,mixed>> */
    public function todas(): array
    {
        return array_values($this->entradas);
    }

    private function clave(string $categoria, string $subcategoria): string
    {
        return Texto::clave($categoria) . '|' . Texto::clave($subcategoria);
    }
}
