<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cotizacion extends Model
{
    use HasFactory;

    protected $table = 'cotizaciones';

    protected $fillable = [
        'serie',
        'numero',
        'fecha_emision',
        'fecha_validez',
        'cliente_id',
        'tipo_moneda',
        'tipo_cambio',
        'subtotal',
        'igv',
        'total',
        'descuento',
        'observaciones',
        'estado',
        'caja_id',
        'usuario_id'
    ];

    protected $casts = [
        'fecha_emision' => 'datetime',
        'fecha_validez' => 'date',
        'subtotal' => 'decimal:2',
        'igv' => 'decimal:2',
        'total' => 'decimal:2',
        'descuento' => 'decimal:2',
        'tipo_cambio' => 'decimal:4'
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
        return $this->hasMany(CotizacionDetalle::class, 'cotizacion_id');
    }

    public function getDocumentoAttribute()
    {
        return $this->serie . '-' . str_pad($this->numero, 8, '0', STR_PAD_LEFT);
    }

    public function getEstadoBadgeAttribute()
    {
        $badges = [
            'PENDIENTE' => '<span class="badge bg-warning">Pendiente</span>',
            'APROBADA' => '<span class="badge bg-success">Aprobada</span>',
            'RECHAZADA' => '<span class="badge bg-danger">Rechazada</span>',
            'VENCIDA' => '<span class="badge bg-secondary">Vencida</span>'
        ];
        return $badges[$this->estado] ?? '<span class="badge bg-secondary">' . $this->estado . '</span>';
    }

    public function getTipoMonedaTextoAttribute()
    {
        $monedas = [
            'PEN' => 'Soles (S/)',
            'USD' => 'Dólares ($)'
        ];
        return $monedas[$this->tipo_moneda] ?? $this->tipo_moneda;
    }
}