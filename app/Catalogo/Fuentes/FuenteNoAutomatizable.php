<?php

namespace App\Catalogo\Fuentes;

/**
 * La fuente no se puede recorrer automaticamente.
 *
 * Se lanza cuando el robots.txt prohibe la ruta o cuando no se lo puede leer.
 * No es un fallo a reintentar: es la respuesta del sitio sobre si quiere que
 * lo recorran, y la unica salida valida es cargar esa fuente a mano.
 */
class FuenteNoAutomatizable extends \RuntimeException {}
