<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GuiaRemisionDetalle extends Model
{
    use HasFactory;

    protected $table = 'guia_remision_detalles';

    protected $fillable = [
        'guia_remision_id',
        'producto_id',
        'cantidad',
        'unidad_medida'
    ];

    public function guiaRemision()
    {
        return $this->belongsTo(GuiaRemision::class);
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
}