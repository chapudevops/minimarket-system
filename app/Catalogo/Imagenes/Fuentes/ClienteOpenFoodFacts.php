<?php

namespace App\Catalogo\Imagenes\Fuentes;

use Closure;

/**
 * Cliente de la API de la familia Open Food Facts.
 *
 * Sigue el mismo patron que App\Catalogo\Fuentes\ClienteVtex —cURL crudo, agente
 * identificable, pausa entre peticiones, reintentos con espera creciente— con
 * una diferencia que no es cosmetica: la pausa.
 *
 * Open Food Facts publica un limite de 15 peticiones por minuto para lectura de
 * producto y avisa que superarlo puede terminar en un baneo de IP. La pausa de
 * 1,2 s de ClienteVtex daria 50 por minuto. Aca son 4,5 s.
 *
 * El User-Agent tampoco es libre: OFF exige el formato
 * "AppName/Version (ContactEmail)" y con el identifican a quien consulta.
 */
class ClienteOpenFoodFacts
{
    /** Formato exigido por la documentacion de la API de OFF. */
    public const AGENTE = 'MinimarketSystem/1.0 (chapudevops@gmail.com)';

    /** 4,5 s -> ~13 peticiones por minuto, por debajo del limite de 15. */
    public const PAUSA = 4_500_000;

    private const ESPERA_REINTENTO = [5, 15, 40];

    private const MAX_INTENTOS = 3;

    private int $peticiones = 0;

    private int $reintentos = 0;

    private int $desconocidos = 0;

    /**
     * @param  Closure|null  $descargar  fn(string $url): array{0:int,1:?string}
     *                                   Se inyecta en los tests para no salir a
     *                                   internet.
     */
    public function __construct(private readonly ?Closure $descargar = null) {}

    /**
     * Ficha de un producto por su codigo de barras.
     *
     * @return array<string,mixed>|null null cuando la fuente no lo conoce
     */
    public function producto(string $host, string $ean): ?array
    {
        $url = "https://{$host}/api/v2/product/".rawurlencode($ean).'.json';

        for ($intento = 0; $intento < self::MAX_INTENTOS; $intento++) {
            $this->pausar();
            $this->peticiones++;

            [$codigo, $cuerpo] = $this->pedir($url);

            // 404 es la respuesta normal de OFF para un codigo que no tiene.
            // No es un fallo y no se reintenta.
            if ($codigo === 404) {
                $this->desconocidos++;

                return null;
            }

            if ($codigo === 200) {
                $datos = json_decode((string) $cuerpo, true);

                if (! is_array($datos)) {
                    // Respuesta invalida: se trata como fallo transitorio.
                    $this->esperar($intento);

                    continue;
                }

                // status 0 = "producto no encontrado" con HTTP 200. Es la otra
                // forma en que OFF dice que no lo tiene.
                if ((int) ($datos['status'] ?? 0) !== 1) {
                    $this->desconocidos++;

                    return null;
                }

                return is_array($datos['product'] ?? null) ? $datos['product'] : null;
            }

            $this->esperar($intento);
        }

        return null;
    }

    public function peticiones(): int
    {
        return $this->peticiones;
    }

    public function reintentos(): int
    {
        return $this->reintentos;
    }

    /** Cuantos codigos consultados no existen en la fuente. */
    public function desconocidos(): int
    {
        return $this->desconocidos;
    }

    private function esperar(int $intento): void
    {
        $this->reintentos++;

        if ($this->descargar === null) {
            sleep(self::ESPERA_REINTENTO[$intento] ?? 40);
        }
    }

    private function pausar(): void
    {
        // Con descarga inyectada (tests) no hay a quien respetarle el limite.
        if ($this->descargar === null) {
            usleep(self::PAUSA);
        }
    }

    /** @return array{0:int,1:?string} */
    private function pedir(string $url): array
    {
        if ($this->descargar !== null) {
            return ($this->descargar)($url);
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_USERAGENT => self::AGENTE,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
        ]);

        $cuerpo = curl_exec($ch);
        $codigo = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [$codigo, $cuerpo === false ? null : (string) $cuerpo];
    }
}
