<?php

namespace App\Catalogo;

/**
 * Unifica la forma en que cada fuente escribe una misma marca.
 *
 *   "Coca Cola", "Coca-Cola", "COCA COLA"  ->  "Coca-Cola"
 *
 * La equivalencia por mayusculas, tildes, guiones y espacios la resuelve
 * Texto::clave(). El diccionario data/catalogo-minimarket/diccionarios/marcas.csv
 * solo hace falta para dos cosas: fijar la grafia oficial que se va a guardar
 * y declarar alias que no son deducibles ("H&S" = "Head & Shoulders").
 *
 * Una marca que no esta en el diccionario NO se descarta ni se inventa: se
 * capitaliza y se marca como desconocida para que alguien la revise.
 */
class NormalizadorMarca
{
    /** @var array<string,string> clave normalizada => grafia oficial */
    private array $canonicas;

    /** @var array<string,true> claves que no estaban en el diccionario */
    private array $desconocidas = [];

    /** @param array<string,string> $canonicas clave => grafia oficial */
    public function __construct(array $canonicas = [])
    {
        $this->canonicas = $canonicas;
    }

    /**
     * Arma el normalizador desde el CSV del diccionario.
     *
     * Formato: canonica,alias — una fila por alias; alias vacio significa que
     * la marca solo aporta su grafia oficial.
     */
    public static function desdeArchivo(?string $ruta = null): self
    {
        $ruta ??= Rutas::diccionario('marcas.csv');
        $canonicas = [];

        foreach (Csv::leer($ruta) as $fila) {
            $oficial = trim((string) ($fila['canonica'] ?? ''));

            if ($oficial === '') {
                continue;
            }

            $canonicas[Texto::clave($oficial)] = $oficial;

            $alias = trim((string) ($fila['alias'] ?? ''));

            if ($alias !== '') {
                $canonicas[Texto::clave($alias)] = $oficial;
            }
        }

        return new self($canonicas);
    }

    /** Clave de comparacion: dos marcas son la misma si comparten esta clave. */
    public function clave(?string $marca): string
    {
        $clave = Texto::clave($marca);

        // Un alias tiene que colapsar con su marca oficial, no quedarse
        // aparte: si no, "H&S" y "Head & Shoulders" serian dos productos.
        return isset($this->canonicas[$clave])
            ? Texto::clave($this->canonicas[$clave])
            : $clave;
    }

    /** @return string|null null cuando la fuente no publica marca. */
    public function normalizar(?string $marca): ?string
    {
        $clave = Texto::clave($marca);

        if ($clave === '') {
            return null;
        }

        if (isset($this->canonicas[$clave])) {
            return $this->canonicas[$clave];
        }

        $this->desconocidas[$clave] = true;

        return Texto::titulo(Texto::plano($marca));
    }

    public function conoce(?string $marca): bool
    {
        return isset($this->canonicas[Texto::clave($marca)]);
    }

    /** Marcas vistas en el RAW que el diccionario todavia no cubre. */
    public function desconocidas(): array
    {
        return array_keys($this->desconocidas);
    }
}
