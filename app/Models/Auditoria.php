<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;

class Auditoria extends Model
{
    protected $table = 'auditorias';

    /** Solo se escribe una vez: no hay updated_at. */
    public const UPDATED_AT = null;

    protected $fillable = [
        'usuario_id', 'usuario_nombre', 'accion', 'entidad',
        'entidad_id', 'descripcion', 'cambios', 'ip', 'navegador',
    ];

    protected $casts = [
        'cambios' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Campos que nunca deben quedar registrados: son secretos, o ruido.
     */
    public const NUNCA_REGISTRAR = [
        'password', 'remember_token', 'clave', 'clave_certificado',
        'client_secret', 'created_at', 'updated_at',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * Deja constancia de una accion. `cambios` es un mapa
     * campo => ['antes' => ..., 'despues' => ...].
     */
    public static function registrar(
        string $accion,
        string $entidad,
        ?int $entidadId,
        string $descripcion,
        ?array $cambios = null,
    ): self {
        $usuario = Auth::user();
        $peticion = request();

        $datos = [
            'usuario_id'     => $usuario?->id,
            // Se guarda el nombre ademas del id: si borran la cuenta, la
            // bitacora tiene que seguir diciendo quien fue.
            'usuario_nombre' => $usuario?->name ?? 'Sistema',
            'accion'         => $accion,
            'entidad'        => $entidad,
            'entidad_id'     => $entidadId,
            'descripcion'    => $descripcion,
            'cambios'        => $cambios ?: null,
            'ip'             => $peticion?->ip(),
            'navegador'      => substr((string) $peticion?->userAgent(), 0, 255) ?: null,
            'created_at'     => now(),
        ];

        try {
            return self::create($datos);
        } catch (QueryException $e) {
            // Caso borde: el actor es la propia fila que se acaba de borrar, y
            // la clave foranea del actor ya no resuelve. Se registra igual sin
            // el id: perder el rastro seria peor que perder el vinculo.
            return self::create(['usuario_id' => null] + $datos);
        }
    }

    public function getAccionBadgeAttribute(): string
    {
        $color = match ($this->accion) {
            'CREO'       => 'success',
            'ACTUALIZO'  => 'info',
            'ELIMINO'    => 'danger',
            'ANULO'      => 'danger',
            'APROBO'     => 'success',
            'RECHAZO'    => 'warning',
            'ABRIO_CAJA', 'CERRO_CAJA' => 'primary',
            default      => 'secondary',
        };

        return '<span class="badge bg-' . $color . '">' . ucfirst(strtolower($this->accion)) . '</span>';
    }
}
