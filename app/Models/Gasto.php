<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gasto extends Model
{
    use HasFactory;

    protected $table = 'gastos';

    protected $fillable = [
        'fecha_emision',
        'motivo',
        'cuenta',
        'monto',
        'detalle',
        'usuario_id'
    ];

    protected $casts = [
        'fecha_emision' => 'date',
        'monto' => 'decimal:2'
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class);
    }
}