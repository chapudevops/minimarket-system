<?php

namespace App\Catalogo;

use App\Sunat\Tributos;

/**
 * Matriz tributaria del catalogo: diccionarios/reglas_tributarias.csv.
 *
 * Responde tres preguntas separadas sobre un producto —afectacion de IGV,
 * ambito del ISC, ambito del IVAP— y ademas dice si la respuesta es segura.
 *
 * La granularidad es (categoria, subcategoria, producto_tipo). El producto_tipo
 * existe justamente porque hay familias que NO se pueden clasificar enteras:
 * "leche cruda entera" esta nominada en el Apendice I y "leche evaporada" es un
 * producto industrializado. Una regla por subcategoria daria una de las dos por
 * buena para las dos, que es exactamente el error que hay que evitar.
 *
 * Cuando no hay regla segura la afectacion sale PENDIENTE. PENDIENTE bloquea la
 * importacion y bloquea la venta: es preferible frenar a emitir un comprobante
 * con un IGV que nadie decidio.
 */
class ReglasTributarias
{
    /** producto_tipo que aplica a toda la subcategoria. */
    public const CUALQUIERA = '*';

    /** Valores admitidos en las columnas isc e ivap. */
    public const SI = 'SI';
    public const NO = 'NO';
    public const REVISAR = 'REVISAR';

    /** @var array<string,array<string,mixed>> */
    private array $reglas = [];

    /** @param array<int,array<string,string>> $filas */
    public function __construct(array $filas = [])
    {
        foreach ($filas as $fila) {
            $tipo = trim((string) ($fila['producto_tipo'] ?? '')) ?: self::CUALQUIERA;

            $clave = $this->clave(
                $fila['categoria'] ?? '',
                $fila['subcategoria'] ?? '',
                $tipo,
            );

            $this->reglas[$clave] = [
                'categoria'         => trim((string) ($fila['categoria'] ?? '')),
                'subcategoria'      => trim((string) ($fila['subcategoria'] ?? '')),
                'producto_tipo'     => strtoupper($tipo),
                'igv'               => strtoupper(trim((string) ($fila['igv'] ?? Tributos::PENDIENTE))),
                'isc'               => strtoupper(trim((string) ($fila['isc'] ?? self::REVISAR))),
                'ivap'              => strtoupper(trim((string) ($fila['ivap'] ?? self::REVISAR))),
                'requiere_revision' => $this->booleano($fila['requiere_revision'] ?? 'true'),
                'fuente_normativa'  => trim((string) ($fila['fuente_normativa'] ?? '')),
                'observacion'       => trim((string) ($fila['observacion'] ?? '')),
            ];
        }
    }

    public static function desdeArchivo(?string $ruta = null): self
    {
        return new self(Csv::leer($ruta ?? Rutas::diccionario('reglas_tributarias.csv')));
    }

    /**
     * Regla aplicable, de la mas especifica a la mas general.
     *
     * Si no hay ninguna, devuelve una regla PENDIENTE en vez de null: un
     * producto sin regla no es un producto gravado por defecto, es un producto
     * sin clasificar. Ese matiz es todo el punto de esta fase.
     *
     * @return array<string,mixed>
     */
    public function para(?string $categoria, ?string $subcategoria, ?string $productoTipo = null): array
    {
        $tipo = trim((string) $productoTipo);

        if ($tipo !== '') {
            $especifica = $this->reglas[$this->clave((string) $categoria, (string) $subcategoria, $tipo)] ?? null;

            if ($especifica !== null) {
                return $especifica;
            }
        }

        $general = $this->reglas[$this->clave((string) $categoria, (string) $subcategoria, self::CUALQUIERA)] ?? null;

        if ($general !== null) {
            // Un producto_tipo que no tiene regla propia dentro de una familia
            // que SI distingue por tipo no puede heredar la general a ciegas.
            if ($tipo !== '' && $this->distinguePorTipo((string) $categoria, (string) $subcategoria)) {
                return $this->sinRegla(
                    "La subcategoría distingue por producto_tipo y '{$tipo}' no tiene regla propia."
                );
            }

            return $general;
        }

        return $this->sinRegla('No hay ninguna regla declarada para esta categoría y subcategoría.');
    }

    /**
     * Si la AFECTACION DE IGV no es segura. Es lo unico que bloquea.
     *
     * Es el significado exacto de la columna requiere_revision del CSV: habla
     * del IGV, no de los otros tributos. Una gaseosa es GRAVADA sin discusion
     * aunque su ISC dependa del azucar que tenga; bloquear su importacion por
     * eso dejaria medio catalogo afuera sin ninguna razon.
     */
    public function requiereRevisionIgv(?string $categoria, ?string $subcategoria, ?string $productoTipo = null): bool
    {
        return $this->para($categoria, $subcategoria, $productoTipo)['requiere_revision'];
    }

    /** Afectacion de IGV, o PENDIENTE si no es segura. */
    public function igv(?string $categoria, ?string $subcategoria, ?string $productoTipo = null): string
    {
        $regla = $this->para($categoria, $subcategoria, $productoTipo);

        // Una regla marcada para revision no entrega una afectacion utilizable
        // aunque la columna igv traiga un valor: ese valor es la hipotesis, no
        // la decision.
        if ($regla['requiere_revision'] || ! Tributos::esAfectacionValida($regla['igv'])) {
            return Tributos::PENDIENTE;
        }

        return $regla['igv'];
    }

    /** true solo cuando la regla afirma el ambito; REVISAR no es true. */
    public function afectoIsc(?string $categoria, ?string $subcategoria, ?string $productoTipo = null): bool
    {
        return $this->para($categoria, $subcategoria, $productoTipo)['isc'] === self::SI;
    }

    public function afectoIvap(?string $categoria, ?string $subcategoria, ?string $productoTipo = null): bool
    {
        return $this->para($categoria, $subcategoria, $productoTipo)['ivap'] === self::SI;
    }

    /**
     * Si algo del producto amerita que lo mire un contador: el IGV, el ISC o
     * el IVAP.
     *
     * A diferencia de requiereRevisionIgv(), esto NO bloquea. Alimenta el
     * reporte de la normalizacion para que las dudas queden a la vista en vez
     * de perderse.
     */
    public function requiereRevision(?string $categoria, ?string $subcategoria, ?string $productoTipo = null): bool
    {
        $regla = $this->para($categoria, $subcategoria, $productoTipo);

        return $regla['requiere_revision']
            || $regla['isc'] === self::REVISAR
            || $regla['ivap'] === self::REVISAR;
    }

    /** @return array<int,array<string,mixed>> */
    public function todas(): array
    {
        return array_values($this->reglas);
    }

    /** Si alguna regla de esa subcategoria declara un producto_tipo concreto. */
    private function distinguePorTipo(string $categoria, string $subcategoria): bool
    {
        $prefijo = Texto::clave($categoria).'|'.Texto::clave($subcategoria).'|';

        foreach ($this->reglas as $clave => $regla) {
            if (str_starts_with($clave, $prefijo) && $regla['producto_tipo'] !== self::CUALQUIERA) {
                return true;
            }
        }

        return false;
    }

    /** @return array<string,mixed> */
    private function sinRegla(string $motivo): array
    {
        return [
            'categoria'         => '',
            'subcategoria'      => '',
            'producto_tipo'     => self::CUALQUIERA,
            'igv'               => Tributos::PENDIENTE,
            'isc'               => self::REVISAR,
            'ivap'              => self::REVISAR,
            'requiere_revision' => true,
            'fuente_normativa'  => '',
            'observacion'       => $motivo,
        ];
    }

    private function booleano(string $valor): bool
    {
        return in_array(strtolower(trim($valor)), ['1', 'true', 'si', 'sí', 'yes'], true);
    }

    private function clave(string $categoria, string $subcategoria, string $productoTipo): string
    {
        return Texto::clave($categoria).'|'.Texto::clave($subcategoria).'|'.Texto::clave($productoTipo);
    }
}
