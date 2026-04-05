<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductoAlmacen extends Model
{
    use HasFactory;

    protected $table = 'producto_almacen';

    protected $fillable = [
        'producto_id',
        'almacen_id',
        'stock'
    ];

    protected $casts = [
        'stock' => 'integer'
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function almacen()
    {
        return $this->belongsTo(Almacen::class);
    }
}