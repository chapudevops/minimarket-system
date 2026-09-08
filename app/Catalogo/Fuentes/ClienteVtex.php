<?php

namespace App\Catalogo\Fuentes;

/**
 * Cliente del catalogo publico VTEX, que es lo que corren Plaza Vea y Metro.
 *
 * --------------------------------------------------------------------------
 * Como se comporta
 * --------------------------------------------------------------------------
 *
 * Antes de la primera peticion consulta el robots.txt del host y verifica que
 * la ruta este permitida. Si no lo esta, o si el robots.txt no se puede leer,
 * el cliente se niega a pedir nada. No hay flag para saltearlo.
 *
 * Entre peticiones espera. El objetivo no es ir rapido: son unos cientos de
 * productos que se bajan una vez, no un monitoreo continuo. Una pausa de un
 * segundo largo deja el trabajo terminado en minutos y no le mueve la aguja al
 * servidor de nadie.
 *
 * Ante un 429 o un 5xx espera mas y reintenta; despues de tres intentos
 * abandona esa pagina y sigue. Perder 50 productos es preferible a insistirle
 * a un servidor que esta pidiendo que pares.
 */
class ClienteVtex
{
    /**
     * Se identifica con un agente propio y un contacto.
     *
     * Un User-Agent que finge ser Chrome sirve para esconderse. Si el trabajo
     * es legitimo —y bajar 800 fichas publicas una vez lo es— conviene decir
     * quien lo hace, para que quien mire los logs pueda pedir que pare.
     */
    public const AGENTE = 'minimarket-system/1.0 (catalogo interno; contacto: chapudevops@gmail.com)';

    /** Pausa entre peticiones, en microsegundos. */
    private const PAUSA = 1_200_000;

    /** Espera tras un 429 o 5xx, en segundos, por intento. */
    private const ESPERA_REINTENTO = [5, 15, 40];

    private const MAX_INTENTOS = 3;

    private ?Robots $robots = null;

    private int $peticiones = 0;

    private int $reintentos = 0;

    /** @var array<int,string> */
    private array $bloqueadas = [];

    public function __construct(
        private readonly string $host,
        private readonly ?\Closure $descargar = null,
    ) {}

    public function host(): string
    {
        return $this->host;
    }

    public function peticiones(): int
    {
        return $this->peticiones;
    }

    public function reintentos(): int
    {
        return $this->reintentos;
    }

    /** @return array<int,string> */
    public function bloqueadas(): array
    {
        return $this->bloqueadas;
    }

    public function robots(): Robots
    {
        return $this->robots ??= Robots::de($this->host);
    }

    /**
     * Una pagina de productos del catalogo.
     *
     * @return array<int,array<string,mixed>>
     */
    public function buscar(string $termino, int $desde = 0, int $cantidad = 50): array
    {
        $ruta = '/api/catalog_system/pub/products/search';
        $url = sprintf(
            'https://%s%s?ft=%s&_from=%d&_to=%d',
            $this->host,
            $ruta,
            rawurlencode($termino),
            $desde,
            $desde + $cantidad - 1,
        );

        return $this->pedir($ruta, $url);
    }

    /**
     * Productos de una categoria del arbol de la tienda.
     *
     * @param  array<int,string>  $arbol  p. ej. ['bebidas', 'gaseosas']
     * @return array<int,array<string,mixed>>
     */
    public function porCategoria(array $arbol, int $desde = 0, int $cantidad = 50): array
    {
        $ruta = '/api/catalog_system/pub/products/search/'.implode('/', array_map('rawurlencode', $arbol));
        $url = sprintf('https://%s%s?_from=%d&_to=%d', $this->host, $ruta, $desde, $desde + $cantidad - 1);

        return $this->pedir($ruta, $url);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function pedir(string $ruta, string $url): array
    {
        if (! $this->robots()->accesible()) {
            throw new FuenteNoAutomatizable(
                "{$this->host}: no se pudo leer robots.txt. La fuente no se automatiza."
            );
        }

        if (! $this->robots()->permite($ruta)) {
            $this->bloqueadas[] = $ruta;

            throw new FuenteNoAutomatizable(
                "{$this->host}{$ruta}: prohibido por robots.txt. La fuente no se automatiza."
            );
        }

        for ($intento = 0; $intento < self::MAX_INTENTOS; $intento++) {
            usleep(self::PAUSA);
            $this->peticiones++;

            [$codigo, $cuerpo] = $this->descargar($url);

            if ($codigo === 200 || $codigo === 206) {
                $datos = json_decode((string) $cuerpo, true);

                return is_array($datos) ? $datos : [];
            }

            // 404 sobre una categoria que no existe no es un problema del
            // servidor: no se reintenta.
            if ($codigo === 404) {
                return [];
            }

            $this->reintentos++;
            sleep(self::ESPERA_REINTENTO[$intento] ?? 40);
        }

        return [];
    }

    /** @return array{0:int,1:?string} */
    private function descargar(string $url): array
    {
        if ($this->descargar !== null) {
            return ($this->descargar)($url);
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 40,
            CURLOPT_USERAGENT      => self::AGENTE,
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
        ]);

        $cuerpo = curl_exec($ch);
        $codigo = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [$codigo, $cuerpo === false ? null : (string) $cuerpo];
    }
}
