<?php

namespace App\Catalogo\Fuentes;

/**
 * Lee el robots.txt de un sitio y responde si una ruta esta permitida.
 *
 * No es un detalle de cortesia: es la condicion que decide si una fuente se
 * automatiza o no. Si el robots.txt prohibe la ruta, el extractor no la pide,
 * y la fuente pasa a carga manual.
 *
 * Implementa lo que hace falta para este caso —User-agent, Allow, Disallow,
 * comodin final— y no pretende ser un parser completo del estandar. Ante la
 * duda responde que NO se puede, que es el lado seguro para equivocarse.
 */
class Robots
{
    /** @var array<int,string> */
    private array $permitidas = [];

    /** @var array<int,string> */
    private array $prohibidas = [];

    private function __construct(private readonly bool $accesible) {}

    public static function de(string $host, ?callable $descargar = null): self
    {
        $descargar ??= self::descargaPorDefecto(...);

        try {
            $texto = $descargar("https://{$host}/robots.txt");
        } catch (\Throwable) {
            $texto = null;
        }

        // Sin robots.txt legible no se asume permiso. Un 403 de Cloudflare
        // delante del propio robots.txt ya dice bastante sobre si el sitio
        // quiere que lo recorran.
        if ($texto === null || trim($texto) === '' || str_contains($texto, '<html')) {
            return new self(accesible: false);
        }

        $robots = new self(accesible: true);
        $robots->interpretar($texto);

        return $robots;
    }

    public function accesible(): bool
    {
        return $this->accesible;
    }

    public function permite(string $ruta): bool
    {
        if (! $this->accesible) {
            return false;
        }

        $prohibida = $this->coincidenciaMasLarga($this->prohibidas, $ruta);
        $permitida = $this->coincidenciaMasLarga($this->permitidas, $ruta);

        // Regla de GS1... perdon, de robots: gana la regla mas especifica, y
        // ante empate gana Allow.
        return $permitida >= $prohibida;
    }

    /** Interpreta solo el bloque de User-agent: * */
    private function interpretar(string $texto): void
    {
        $aplica = false;

        foreach (preg_split('/\R/', $texto) ?: [] as $linea) {
            $linea = trim(preg_replace('/#.*$/', '', $linea) ?? '');

            if ($linea === '' || ! str_contains($linea, ':')) {
                continue;
            }

            [$clave, $valor] = array_map('trim', explode(':', $linea, 2));
            $clave = strtolower($clave);

            if ($clave === 'user-agent') {
                $aplica = $valor === '*';

                continue;
            }

            if (! $aplica || $valor === '') {
                continue;
            }

            if ($clave === 'disallow') {
                $this->prohibidas[] = $valor;
            } elseif ($clave === 'allow') {
                $this->permitidas[] = $valor;
            }
        }
    }

    /** @param array<int,string> $reglas */
    private function coincidenciaMasLarga(array $reglas, string $ruta): int
    {
        $mejor = 0;

        foreach ($reglas as $regla) {
            $patron = rtrim($regla, '*');

            if ($patron !== '' && str_starts_with($ruta, $patron)) {
                $mejor = max($mejor, strlen($patron));
            }
        }

        return $mejor;
    }

    private static function descargaPorDefecto(string $url): ?string
    {
        $contexto = stream_context_create(['http' => [
            'timeout'    => 20,
            'user_agent' => ClienteVtex::AGENTE,
        ]]);

        $cuerpo = @file_get_contents($url, false, $contexto);

        return $cuerpo === false ? null : $cuerpo;
    }
}
