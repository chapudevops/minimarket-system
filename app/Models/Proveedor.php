<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proveedor extends Model
{
    use HasFactory;

    protected $table = 'proveedores';

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
}