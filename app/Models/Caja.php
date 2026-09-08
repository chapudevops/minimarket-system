<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Caja extends Model
{
    use Auditable, HasFactory;

    protected $table = 'cajas';

    protected $fillable = [
        'descripcion'
    ];

    public function etiquetaAuditoria(): string
    {
        return $this->descripcion;
    }
}