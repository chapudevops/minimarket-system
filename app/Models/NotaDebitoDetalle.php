<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotaDebitoDetalle extends Model
{
    use HasFactory;

    protected $table = 'nota_debito_detalles';

    protected $fillable = [
        'nota_debito_id',
        'concepto',
        'cantidad',
        'precio_unitario',
        'total'
    ];

    public function notaDebito()
    {
        return $this->belongsTo(NotaDebito::class);
    }
}