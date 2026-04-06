<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VentaCuota extends Model
{
    use HasFactory;

    protected $table = 'venta_cuotas';

    protected $fillable = [
        'venta_id',
        'numero_cuota',
        'fecha_vencimiento',
        'monto',
        'estado',
        'fecha_pago'
    ];

    protected $casts = [
        'fecha_vencimiento' => 'date',
        'fecha_pago' => 'date',
        'monto' => 'decimal:2'
    ];

    public function venta()
    {
        return $this->belongsTo(Venta::class);
    }
}