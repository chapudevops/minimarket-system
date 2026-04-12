<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotaCreditoDetalle extends Model
{
    use HasFactory;

    protected $table = 'nota_credito_detalles';

    protected $fillable = [
        'nota_credito_id',
        'producto_id',
        'cantidad',
        'precio_unitario',
        'total',
        'almacen_id'
    ];

    public function notaCredito()
    {
        return $this->belongsTo(NotaCredito::class);
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