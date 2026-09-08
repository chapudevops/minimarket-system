<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Categoria extends Model
{
    use Auditable;

    protected $table = 'categorias';

    protected $fillable = ['nombre', 'codigo', 'orden', 'estado'];

    protected $casts = ['estado' => 'boolean', 'orden' => 'integer'];

    protected array $auditarSolo = ['nombre', 'codigo', 'estado'];

    public function subcategorias(): HasMany
    {
        return $this->hasMany(Subcategoria::class);
    }

    public function scopeActivas($query)
    {
        return $query->where('estado', 1)->orderBy('orden')->orderBy('nombre');
    }
}
