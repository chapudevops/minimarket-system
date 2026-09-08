<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use Auditable, HasFactory;

    protected $table = 'clientes';

    protected $fillable = [
        'tipo_documento',
        'numero_documento',
        'nombre_razon_social',
        'direccion',
        'telefono',
        'departamento',
        'provincia',
        'distrito',
        'estado'
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // Accesor para obtener el tipo de documento formateado
    public function getTipoDocumentoTextoAttribute()
    {
        $tipos = [
            'DNI' => 'DNI',
            'RUC' => 'RUC',
            'CE' => 'Carné de Extranjería'
        ];
        return $tipos[$this->tipo_documento] ?? $this->tipo_documento;
    }

    /** Catalogo 06 de SUNAT: DNI=1, CE=4, RUC=6. */
    public function getTipoDocumentoSunatAttribute(): string
    {
        return \App\Sunat\Catalogo::documentoIdentidad($this->tipo_documento);
    }

    public function etiquetaAuditoria(): string
    {
        return $this->numero_documento . " - " . $this->nombre_razon_social;
    }
}