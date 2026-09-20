<?php

namespace App;

use App\Models\Empresa;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * Identidad visual de la tienda: logo y nombre, resueltos en un solo sitio.
 *
 * Antes cada vista apuntaba a su propia ruta de imagen. La mitad tiraba del
 * logo de la plantilla (infinitydevlogo.png) y la otra mitad de un archivo
 * `1788986003_logo.png.png` que no existe — con la extension duplicada — asi
 * que todas las pantallas de sesion mostraban la imagen rota.
 *
 * Ahora el logo sale de la empresa configurada. Cambiarlo en Configuracion →
 * Empresa lo cambia en el sistema entero, sin tocar vistas.
 */
class Marca
{
    /** Logo de la plantilla, por si la empresa todavia no subio el suyo. */
    private const RESPALDO = 'build/images/infinitydevlogo.png';

    private const CLAVE_CACHE = 'marca.empresa';

    /** URL del logo de la empresa, o el de respaldo si no hay ninguno. */
    public static function logo(): string
    {
        $empresa = self::empresa();

        // El nombre en base de datos no garantiza que el archivo siga ahi: si
        // alguien lo borro a mano, mejor el respaldo que una imagen rota.
        if ($empresa?->logo && Storage::disk('public')->exists('empresa/'.$empresa->logo)) {
            return asset('storage/empresa/'.$empresa->logo);
        }

        return asset(self::RESPALDO);
    }

    /** Mismo archivo que el logo: el favicon no necesita uno aparte. */
    public static function favicon(): string
    {
        return self::logo();
    }

    /**
     * Nombre con el que se identifica la tienda en todo el sistema.
     *
     * Manda la RAZON SOCIAL, no el nombre comercial. Estaba al reves y el
     * efecto era que cambiar la razon social en Configuracion → Empresa no
     * se notaba en ninguna parte: el sidebar seguia leyendo `nombre_comercial`,
     * que casi nadie edita.
     *
     * Ademas la razon social es lo que ya imprimian los PDF y los tickets, asi
     * que la pantalla y el comprobante dicen ahora lo mismo.
     */
    public static function nombre(): string
    {
        $empresa = self::empresa();

        return trim((string) $empresa?->razon_social)
            ?: trim((string) $empresa?->nombre_comercial)
            ?: config('app.name', 'Minimarket');
    }

    /** Nombre comercial, cuando hace falta distinguirlo del legal. */
    public static function nombreComercial(): string
    {
        $empresa = self::empresa();

        return trim((string) $empresa?->nombre_comercial) ?: self::nombre();
    }

    public static function ruc(): ?string
    {
        return self::empresa()?->ruc;
    }

    /**
     * La empresa se consulta una vez por peticion. El layout la pedia en cada
     * vista que necesitaba el logo, y eran varias consultas identicas por
     * pantalla.
     */
    public static function empresa(): ?Empresa
    {
        return Cache::driver('array')->remember(
            self::CLAVE_CACHE,
            60,
            fn () => Empresa::where('estado', 1)->first() ?: Empresa::first()
        );
    }

    /** Se llama al guardar la configuracion para que el cambio se vea ya. */
    public static function olvidar(): void
    {
        Cache::driver('array')->forget(self::CLAVE_CACHE);
    }
}
