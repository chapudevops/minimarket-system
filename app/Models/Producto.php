<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    use HasFactory;

    protected $table = 'productos';

    protected $fillable = [
        'codigo_interno',
        'codigo_barras',
        'unidad',
        'descripcion',
        'marca',
        'presentacion',
        'operacion',
        'precio_compra',
        'precio_venta',
        'fecha_vencimiento',
        'tipo_producto',
        'detraccion',
        'stock',
        'stock_minimo',
        'estado'
    ];

    protected $casts = [
        'estado' => 'boolean',
        'detraccion' => 'boolean',
        'precio_compra' => 'decimal:2',
        'precio_venta' => 'decimal:2',
        'fecha_vencimiento' => 'date'
    ];

    // Accesores
    public function getOperacionTextoAttribute()
    {
        $operaciones = [
            'GRAVADO' => 'Gravado - Operación Onerosa',
            'EXONERADO' => 'Exonerado - Operación Onerosa',
            'INAFECTO' => 'Inafecto - Operación Onerosa'
        ];
        return $operaciones[$this->operacion] ?? $this->operacion;
    }

    public function getTipoProductoTextoAttribute()
    {
        $tipos = [
            'PRODUCTO' => 'Producto',
            'SERVICIO' => 'Servicio'
        ];
        return $tipos[$this->tipo_producto] ?? $this->tipo_producto;
    }

    public function getDetraccionTextoAttribute()
    {
        return $this->detraccion ? 'Sí' : 'No';
    }
}