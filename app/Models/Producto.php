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
        'stock_minimo',
        'foto',
        'estado'
    ];

    protected $casts = [
        'estado' => 'boolean',
        'detraccion' => 'boolean',
        'precio_compra' => 'decimal:2',
        'precio_venta' => 'decimal:2',
        'fecha_vencimiento' => 'date'
    ];

    // Relación con almacenes (stock por almacén)
    public function almacenes()
    {
        return $this->belongsToMany(Almacen::class, 'producto_almacen')
                    ->withPivot('stock')
                    ->withTimestamps();
    }

    // Relación con producto_almacen
    public function stocks()
    {
        return $this->hasMany(ProductoAlmacen::class);
    }

    // Calcular stock total (suma de todos los stocks por almacén)
    public function getStockTotalAttribute()
    {
        return $this->stocks()->sum('stock');
    }

    // Obtener URL de la foto
    public function getFotoUrlAttribute()
    {
        if ($this->foto) {
            return asset('storage/productos/' . $this->foto);
        }
        return asset('build/images/default-product.png');
    }

    // Obtener stock de un almacén específico
    public function getStockByAlmacen($almacenId)
    {
        $stock = $this->stocks()->where('almacen_id', $almacenId)->first();
        return $stock ? $stock->stock : 0;
    }

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