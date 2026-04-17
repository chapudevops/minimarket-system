<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    use HasFactory;

    protected $table = 'empresa';
    
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
        'link_ubicacion', // NUEVO CAMPO
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
    
    // Método para obtener la dirección completa formateada
    public function getDireccionCompletaAttribute()
    {
        $partes = [];
        
        if ($this->direccion) $partes[] = $this->direccion;
        if ($this->distrito) $partes[] = $this->distrito;
        if ($this->provincia) $partes[] = $this->provincia;
        if ($this->departamento) $partes[] = $this->departamento;
        
        return implode(', ', $partes);
    }
    
    // Método para extraer coordenadas del link de Google Maps
    public function getCoordenadasFromLinkAttribute()
    {
        if (!$this->link_ubicacion) {
            return null;
        }
        
        // Buscar coordenadas en formato @lat,lng (formato de compartir)
        preg_match('/@(-?\d+\.\d+),(-?\d+\.\d+)/', $this->link_ubicacion, $matches);
        
        if (count($matches) >= 3) {
            return [
                'lat' => (float) $matches[1],
                'lng' => (float) $matches[2]
            ];
        }
        
        // Buscar formato alternativo: q=lat,lng
        preg_match('/q=(-?\d+\.\d+),(-?\d+\.\d+)/', $this->link_ubicacion, $matches);
        
        if (count($matches) >= 3) {
            return [
                'lat' => (float) $matches[1],
                'lng' => (float) $matches[2]
            ];
        }
        
        // Buscar formato !3d lat !4d lng
        preg_match('/!3d(-?\d+\.\d+)!4d(-?\d+\.\d+)/', $this->link_ubicacion, $matches);
        
        if (count($matches) >= 3) {
            return [
                'lat' => (float) $matches[1],
                'lng' => (float) $matches[2]
            ];
        }
        
        return null;
    }
}