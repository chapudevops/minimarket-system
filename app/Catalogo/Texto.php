<?php

namespace App\Catalogo;

use Illuminate\Support\Str;

/**
 * Normalizacion basica de texto para la capa de catalogo.
 *
 * Todo el resto del pipeline (marcas, presentaciones, deteccion de duplicados)
 * se apoya en estas dos operaciones, asi que viven en un solo sitio: si el
 * criterio cambia, cambia una vez.
 */
class Texto
{
    /**
     * Forma canonica legible: sin tildes, en minusculas, sin espacios de mas
     * y sin puntuacion decorativa. Conserva espacios y digitos.
     *
     * "Coca-Cola  Original" -> "coca cola original"
     */
    public static function plano(?string $valor): string
    {
        $valor = Str::ascii((string) $valor);
        $valor = Str::lower($valor);

        // Los separadores se vuelven espacio; el punto solo si no separa
        // decimales, para no romper "1.5".
        $valor = preg_replace('/\.(?!\d)/', ' ', $valor);
        $valor = preg_replace('/[^a-z0-9.]+/', ' ', $valor);

        return trim(preg_replace('/\s+/', ' ', $valor));
    }

    /**
     * Clave de comparacion: solo letras y digitos, sin separadores.
     *
     * Es lo que hace que "Coca Cola", "Coca-Cola" y "COCA COLA" colapsen en
     * el mismo valor. Nunca se guarda en la base: sirve para comparar.
     */
    public static function clave(?string $valor): string
    {
        // El punto decimal sobrevive a proposito: sin el, "1.5 L" y "15 L"
        // colapsarian en la misma clave y se marcarian como duplicados.
        return preg_replace('/[^a-z0-9.]/', '', self::plano($valor));
    }

    /**
     * Capitaliza para mostrar, respetando siglas y palabras de enlace.
     *
     * Se usa solo cuando una marca no esta en el diccionario: el diccionario
     * siempre manda sobre esta heuristica.
     */
    public static function titulo(?string $valor): string
    {
        $enlaces = ['de', 'del', 'la', 'las', 'el', 'los', 'y', 'e', 'da', 'do'];
        $palabras = preg_split('/\s+/', trim(preg_replace('/\s+/', ' ', (string) $valor)), -1, PREG_SPLIT_NO_EMPTY);

        if ($palabras === []) {
            return '';
        }

        $salida = [];

        foreach ($palabras as $i => $palabra) {
            // Sigla real de la marca (7UP, GN, A1): se respeta tal cual.
            // El corte por longitud evita que una fuente que escribe todo en
            // mayusculas ("COCA COLA") se quede sin capitalizar.
            $esSigla = $palabra === Str::upper($palabra)
                && (mb_strlen($palabra) <= 3 || preg_match('/\d/', $palabra));

            if ($esSigla && preg_match('/[A-Z0-9]/', $palabra)) {
                $salida[] = $palabra;

                continue;
            }

            $minuscula = Str::lower($palabra);

            if ($i > 0 && in_array($minuscula, $enlaces, true)) {
                $salida[] = $minuscula;

                continue;
            }

            // Cada tramo de una marca compuesta lleva mayuscula: "coca-cola"
            // debe salir "Coca-Cola", no "Coca-cola".
            $salida[] = implode('-', array_map(
                fn (string $tramo) => Str::ucfirst($tramo),
                explode('-', $minuscula)
            ));
        }

        return implode(' ', $salida);
    }
}
