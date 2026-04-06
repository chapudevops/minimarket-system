<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Serie extends Model
{
    use HasFactory;

    protected $table = 'series';

    protected $fillable = [
        'serie',
        'correlativo',
        'tipo_comprobante',
        'caja_id'
    ];

    protected $casts = [
        'correlativo' => 'integer'
    ];

    // Relación con caja
    public function caja()
    {
        return $this->belongsTo(Caja::class);
    }

    // Obtener el siguiente número correlativo
    public function getSiguienteCorrelativo()
    {
        $this->correlativo += 1;
        $this->save();
        return $this->correlativo;
    }

    // Accesor para tipo comprobante formateado
    public function getTipoComprobanteTextoAttribute()
    {
        $tipos = [
            'FACTURA' => 'Factura',
            'BOLETA' => 'Boleta',
            'NOTA_CREDITO' => 'Nota de Crédito',
            'NOTA_DEBITO' => 'Nota de Débito'
        ];
        return $tipos[$this->tipo_comprobante] ?? $this->tipo_comprobante;
    }

    // Accesor para documento completo
    public function getDocumentoAttribute()
    {
        return $this->serie . '-' . str_pad($this->correlativo, 8, '0', STR_PAD_LEFT);
    }
}