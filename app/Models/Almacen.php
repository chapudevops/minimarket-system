<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Almacen extends Model
{
    use Auditable, HasFactory;

    protected $table = 'almacenes';

    protected $fillable = [
        'descripcion',
        'establecimiento'
    ];
     // Relación con productos
    public function productos()
    {
        return $this->belongsToMany(Producto::class, 'producto_almacen')
                    ->withPivot('stock')
                    ->withTimestamps();
    }

    public function etiquetaAuditoria(): string
    {
        return $this->descripcion;
    }
}