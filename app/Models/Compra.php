<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Compra extends Model
{
    use HasFactory;

    protected $table = 'compras';

    protected $fillable = [
        'tipo_comprobante',
        'serie',
        'numero',
        'fecha_emision',
        'fecha_vencimiento',
        'proveedor_id',
        'almacen_id',
        'tipo_cambio',
        'tipo_pago',
        'subtotal',
        'igv',
        'total',
        'estado',
        'usuario_id',
        'observaciones'
    ];

    protected $casts = [
        'fecha_emision' => 'date',
        'fecha_vencimiento' => 'date',
        'tipo_cambio' => 'decimal:4',
        'subtotal' => 'decimal:2',
        'igv' => 'decimal:2',
        'total' => 'decimal:2'
    ];

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function almacen()
    {
        return $this->belongsTo(Almacen::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function detalles()
    {
        return $this->hasMany(CompraDetalle::class, 'compra_id');
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
}