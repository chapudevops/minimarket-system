<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrdenTrasladoDetalle extends Model
{
    use HasFactory;

    protected $table = 'orden_traslado_detalle';

    protected $fillable = [
        'orden_traslado_id',
        'producto_id',
        'cantidad',
        'precio_unitario'
    ];

    public function ordenTraslado()
    {
        return $this->belongsTo(OrdenTraslado::class, 'orden_traslado_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function getSubtotalAttribute()
    {
        return $this->cantidad * $this->precio_unitario;
    }
}