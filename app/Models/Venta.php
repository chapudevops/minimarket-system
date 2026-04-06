<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    use HasFactory;

    protected $table = 'ventas';

    protected $fillable = [
        'tipo_comprobante',
        'serie',
        'numero',
        'fecha_emision',
        'cliente_id',
        'tipo_venta',
        'forma_pago',
        'subtotal',
        'igv',
        'total',
        'pagado',
        'cambio',
        'detraccion',
        'observaciones',
        'caja_id',
        'usuario_id',
        'estado'
    ];

    protected $casts = [
        'fecha_emision' => 'datetime',
        'subtotal' => 'decimal:2',
        'igv' => 'decimal:2',
        'total' => 'decimal:2',
        'pagado' => 'decimal:2',
        'cambio' => 'decimal:2',
        'detraccion' => 'boolean'
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function caja()
    {
        return $this->belongsTo(Caja::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class);
    }

    public function detalles()
    {
        return $this->hasMany(VentaDetalle::class);
    }

    public function cuotas()
    {
        return $this->hasMany(VentaCuota::class);
    }

    public function getDocumentoAttribute()
    {
        return $this->serie . '-' . str_pad($this->numero, 8, '0', STR_PAD_LEFT);
    }
}