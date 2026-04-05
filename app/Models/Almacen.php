<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Almacen extends Model
{
    use HasFactory;

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
}