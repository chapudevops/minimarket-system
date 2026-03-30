<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    use HasFactory;

    protected $table = 'empresa'; // Cambiado a singular
    
    protected $fillable = [
        'ruc',
        'razon_social',
        'direccion',
        'pais',
        'departamento',
        'provincia',
        'distrito',
        'url_api',
        'email_contabilidad',
        'cuenta_bancaria_detracciones',
        'logo',
        'nombre_comercial',
        'usuario_secundario',
        'clave',
        'clave_certificado',
        'certificado_pfx',
        'client_id',
        'client_secret',
        'servidor_sunat',
        'estado'
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    public function getLogoUrlAttribute()
    {
        if ($this->logo) {
            return asset('storage/empresa/' . $this->logo);
        }
        return asset('build/images/default-empresa.png');
    }
}