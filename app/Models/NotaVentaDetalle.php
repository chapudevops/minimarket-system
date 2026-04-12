<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotaVentaDetalle extends Model
{
    use HasFactory;

    protected $table = 'nota_venta_detalles';

    protected $fillable = [
        'nota_venta_id',
        'producto_id',
        'cantidad',
        'precio_unitario',
        'total',
        'almacen_id'
    ];

    public function notaVenta()
    {
        return $this->belongsTo(NotaVenta::class);
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function almacen()
    {
        return $this->belongsTo(Almacen::class);
    }
}