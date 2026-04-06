<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AperturaCaja extends Model
{
    use HasFactory;

    protected $table = 'apertura_cajas';

    protected $fillable = [
        'fecha_apertura',
        'hora_apertura',
        'responsable_id',
        'monto_inicial',
        'estado',
        'monto_cierre',
        'fecha_cierre',
        'hora_cierre',
        'responsable_cierre_id'
    ];

    protected $casts = [
        'fecha_apertura' => 'date',
        'hora_apertura' => 'datetime',
        'monto_inicial' => 'decimal:2',
        'monto_cierre' => 'decimal:2',
        'fecha_cierre' => 'date',
        'hora_cierre' => 'datetime'
    ];

    public function responsable()
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function responsableCierre()
    {
        return $this->belongsTo(User::class, 'responsable_cierre_id');
    }

    public function getEstadoBadgeAttribute()
    {
        $badges = [
            'ABIERTA' => '<span class="badge bg-success">Abierta</span>',
            'CERRADA' => '<span class="badge bg-danger">Cerrada</span>'
        ];
        return $badges[$this->estado] ?? '<span class="badge bg-secondary">' . $this->estado . '</span>';
    }

    public function getHoraAperturaFormateadaAttribute()
    {
        return $this->hora_apertura ? date('H:i', strtotime($this->hora_apertura)) : '-';
    }

    public function getHoraCierreFormateadaAttribute()
    {
        return $this->hora_cierre ? date('H:i', strtotime($this->hora_cierre)) : '-';
    }
}