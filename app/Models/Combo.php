<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Combo extends Model
{
    use Auditable, HasFactory;

    protected $table = 'combos';

    protected $fillable = [
        'nombre',
        'descripcion',
        'precio_combo',
        'precio_regular',
        'foto',
        'estado'
    ];

    protected $casts = [
        'estado' => 'boolean',
        'precio_combo' => 'decimal:2',
        'precio_regular' => 'decimal:2'
    ];

    // Relación con los detalles del combo
    public function detalles()
    {
        return $this->hasMany(ComboDetalle::class);
    }

    // Calcular el ahorro del combo
    public function getAhorroAttribute()
    {
        return (float)$this->precio_regular - (float)$this->precio_combo;
    }

    // Calcular el porcentaje de descuento
    public function getDescuentoPorcentajeAttribute()
    {
        if ((float)$this->precio_regular > 0) {
            return round(($this->ahorro / (float)$this->precio_regular) * 100, 1);
        }
        return 0;
    }

    // Obtener URL de la foto
    public function getFotoUrlAttribute()
    {
        if ($this->foto) {
            return asset('storage/combos/' . $this->foto);
        }
        return asset('build/images/default-product.png');
    }

    // Verificar si hay stock suficiente para todos los productos del combo
    public function tieneStockEnAlmacen($almacenId)
    {
        foreach ($this->detalles as $detalle) {
            $stock = ProductoAlmacen::where('producto_id', $detalle->producto_id)
                                    ->where('almacen_id', $almacenId)
                                    ->first();
            if (!$stock || $stock->stock < $detalle->cantidad) {
                return false;
            }
        }
        return true;
    }

    // Obtener el stock mínimo disponible del combo en un almacén
    public function getStockComboEnAlmacen($almacenId)
    {
        $stockMinimo = PHP_INT_MAX;
        foreach ($this->detalles as $detalle) {
            $stock = ProductoAlmacen::where('producto_id', $detalle->producto_id)
                                    ->where('almacen_id', $almacenId)
                                    ->first();
            $stockProducto = $stock ? intdiv($stock->stock, $detalle->cantidad) : 0;
            $stockMinimo = min($stockMinimo, $stockProducto);
        }
        return $stockMinimo === PHP_INT_MAX ? 0 : $stockMinimo;
    }
    public function productos()
{
    return $this->belongsToMany(Producto::class, 'combo_detalles')
                ->withPivot('cantidad')
                ->withTimestamps();
}


    /** Solo estos campos generan entrada en la bitacora. */
    protected array $auditarSolo = ['nombre', 'precio_combo', 'estado'];

    public function etiquetaAuditoria(): string
    {
        return $this->nombre;
    }
}
