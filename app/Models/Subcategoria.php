<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subcategoria extends Model
{
    use Auditable;

    protected $table = 'subcategorias';

    protected $fillable = ['categoria_id', 'nombre', 'codigo', 'unidad_sugerida', 'orden', 'estado'];

    protected $casts = ['estado' => 'boolean', 'orden' => 'integer'];

    protected array $auditarSolo = ['categoria_id', 'nombre', 'codigo', 'estado'];

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class);
    }

    /** "BEB-GAS": el prefijo con el que se construye el SKU. */
    public function prefijo(): string
    {
        return ($this->categoria?->codigo ?? '???').'-'.$this->codigo;
    }

    public function scopeActivas($query)
    {
        return $query->where('estado', 1)->orderBy('orden')->orderBy('nombre');
    }
}
