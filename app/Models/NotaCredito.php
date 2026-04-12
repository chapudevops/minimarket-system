<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotaCredito extends Model
{
    use HasFactory;

    protected $table = 'notas_credito';

    protected $fillable = [
        'tipo_comprobante',
        'serie',
        'numero',
        'fecha_emision',
        'cliente_id',
        'venta_id',
        'motivo',
        'tipo_nota',
        'subtotal',
        'igv',
        'total',
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
        'detraccion' => 'boolean'
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function venta()
    {
        return $this->belongsTo(Venta::class);
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
        return $this->hasMany(NotaCreditoDetalle::class, 'nota_credito_id');
    }

    public function getDocumentoAttribute()
    {
        return $this->serie . '-' . str_pad($this->numero, 8, '0', STR_PAD_LEFT);
    }

    public function getEstadoBadgeAttribute()
    {
        $badges = [
            'REGISTRADA' => '<span class="badge bg-success">Registrada</span>',
            'ANULADA' => '<span class="badge bg-danger">Anulada</span>'
        ];
        return $badges[$this->estado] ?? '<span class="badge bg-secondary">' . $this->estado . '</span>';
    }

    public function getTipoNotaTextoAttribute()
    {
        $tipos = [
            'ANULACION' => 'Anulación',
            'DESCUENTO' => 'Descuento',
            'DEVOLUCION' => 'Devolución',
            'OTRO' => 'Otro'
        ];
        return $tipos[$this->tipo_nota] ?? $this->tipo_nota;
    }
}