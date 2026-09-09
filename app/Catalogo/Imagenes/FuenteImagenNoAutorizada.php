<?php

namespace App\Catalogo\Imagenes;

/**
 * La fuente no esta habilitada para consulta automatizada.
 *
 * No es un fallo a reintentar: es lo que dijo el sitio en su robots.txt o lo
 * que no dicen sus condiciones de uso. La unica salida valida es habilitarla en
 * el registro cuando exista permiso, o cargar la imagen a mano.
 */
class FuenteImagenNoAutorizada extends \RuntimeException {}
