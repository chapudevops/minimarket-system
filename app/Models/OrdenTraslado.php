<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrdenTraslado extends Model
{
    use HasFactory;

    protected $table = 'ordenes_traslado';

    protected $fillable = [
        'serie',
        'numero',
        'fecha_emision',
        'fecha_vencimiento',
        'almacen_origen_id',
        'almacen_destino_id',
        'observaciones',
        'estado',
        'creado_por',
        'aprobado_por',
        'anulado_por',
        'fecha_aprobacion',
        'fecha_anulacion',
        'motivo_anulacion'
    ];

    protected $casts = [
        'fecha_emision' => 'date',
        'fecha_vencimiento' => 'date',
        'fecha_aprobacion' => 'datetime',
        'fecha_anulacion' => 'datetime'
    ];

    // Relaciones
    public function almacenOrigen()
    {
        return $this->belongsTo(Almacen::class, 'almacen_origen_id');
    }

    public function almacenDestino()
    {
        return $this->belongsTo(Almacen::class, 'almacen_destino_id');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function aprobador()
    {
        return $this->belongsTo(User::class, 'aprobado_por');
    }

    public function anulador()
    {
        return $this->belongsTo(User::class, 'anulado_por');
    }

    public function detalles()
    {
        return $this->hasMany(OrdenTrasladoDetalle::class, 'orden_traslado_id');
    }

    // Accesor para número de documento
    public function getDocumentoAttribute()
    {
        return $this->serie . '-' . str_pad($this->numero, 8, '0', STR_PAD_LEFT);
    }

    // Accesor para estado con badge
    public function getEstadoBadgeAttribute()
    {
        $badges = [
            'PENDIENTE' => '<span class="badge bg-warning">Pendiente</span>',
            'APROBADO' => '<span class="badge bg-success">Aprobado</span>',
            'RECHAZADO' => '<span class="badge bg-danger">Rechazado</span>',
            'ANULADO' => '<span class="badge bg-secondary">Anulado</span>',
            'COMPLETADO' => '<span class="badge bg-info">Completado</span>'
        ];
        return $badges[$this->estado] ?? '<span class="badge bg-secondary">' . $this->estado . '</span>';
    }
}