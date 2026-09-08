<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use Auditable, HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'caja_id',
        'almacen_id',
        'estado',
        'ultimo_acceso',
        'ultimo_ip',
        'avatar'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'estado' => 'boolean',
        'ultimo_acceso' => 'datetime'
    ];

    // Relaciones
    public function caja()
    {
        return $this->belongsTo(Caja::class);
    }

    public function almacen()
    {
        return $this->belongsTo(Almacen::class);
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    // Verificar si tiene un rol específico
    public function hasRole($roleName)
    {
        return $this->roles()->where('nombre', $roleName)->exists();
    }

    // Verificar si tiene algún rol
    public function hasAnyRole($roles)
    {
        return $this->roles()->whereIn('nombre', (array) $roles)->exists();
    }

    /**
     * Regla unica de autorizacion: la usan el middleware 'role' y la directiva
     * @rol de Blade, para que el menu nunca muestre algo que la ruta rechaza.
     * El Administrador pasa siempre, este o no en la lista.
     */
    public function puedeConRol($roles): bool
    {
        return $this->hasRole(self::ROL_ADMINISTRADOR) || $this->hasAnyRole($roles);
    }

    public const ROL_ADMINISTRADOR = 'Administrador';

    // Accesor para estado
    public function getEstadoBadgeAttribute()
    {
        return $this->estado 
            ? '<span class="badge bg-success">Activo</span>' 
            : '<span class="badge bg-danger">Inactivo</span>';
    }

    /** Solo estos campos generan entrada en la bitacora. */
    protected array $auditarSolo = ['name', 'email', 'caja_id', 'almacen_id', 'estado'];

    public function etiquetaAuditoria(): string
    {
        return $this->name;
    }
}