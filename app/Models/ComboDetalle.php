<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComboDetalle extends Model
{
    use HasFactory;

    protected $table = 'combo_detalles';

    protected $fillable = [
        'combo_id',
        'producto_id',
        'cantidad'
    ];

    // Relación con el combo
    public function combo()
    {
        return $this->belongsTo(Combo::class);
    }

    // Relación con el producto
    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
}
