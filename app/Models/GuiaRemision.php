<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GuiaRemision extends Model
{
    use HasFactory;

    protected $table = 'guias_remision';

    protected $fillable = [
        'serie',
        'numero',
        'fecha_emision',
        'fecha_traslado',
        'motivo_traslado',
        'cliente_id',
        'peso_bruto_total',
        'unidad_peso',
        'modalidad_traslado',
        'ubigeo_partida',
        'direccion_partida',
        'ubigeo_llegada',
        'direccion_llegada',
        'conductor_id',
        'vehiculo_id',
        'observaciones',
        'estado_sunat',
        'xml',
        'cdr',
        'caja_id',
        'usuario_id'
    ];

    protected $casts = [
        'fecha_emision' => 'datetime',
        'fecha_traslado' => 'date',
        'peso_bruto_total' => 'decimal:3'
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function conductor()
    {
        return $this->belongsTo(Conductor::class);
    }

    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class);
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
        return $this->hasMany(GuiaRemisionDetalle::class, 'guia_remision_id');
    }

    public function getDocumentoAttribute()
    {
        return $this->serie . '-' . str_pad($this->numero, 8, '0', STR_PAD_LEFT);
    }

    public function getEstadoSunatBadgeAttribute()
    {
        $badges = [
            'PENDIENTE' => '<span class="badge bg-warning">Pendiente</span>',
            'ENVIADO' => '<span class="badge bg-info">Enviado</span>',
            'ACEPTADO' => '<span class="badge bg-success">Aceptado</span>',
            'RECHAZADO' => '<span class="badge bg-danger">Rechazado</span>'
        ];
        return $badges[$this->estado_sunat] ?? '<span class="badge bg-secondary">' . $this->estado_sunat . '</span>';
    }

    public function getMotivoTrasladoTextoAttribute()
    {
        $motivos = [
            '01' => 'VENTA',
            '02' => 'COMPRA',
            '03' => 'DEVOLUCIÓN',
            '04' => 'TRASLADO',
            '05' => 'CONSIGNACIÓN',
            '06' => 'OTROS'
        ];
        return $motivos[$this->motivo_traslado] ?? $this->motivo_traslado;
    }

    public function getModalidadTrasladoTextoAttribute()
    {
        $modalidades = [
            '01' => 'TRANSPORTE PÚBLICO',
            '02' => 'TRANSPORTE PRIVADO'
        ];
        return $modalidades[$this->modalidad_traslado] ?? $this->modalidad_traslado;
    }
}