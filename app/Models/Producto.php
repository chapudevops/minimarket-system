<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    use Auditable, HasFactory;

    protected $table = 'productos';

    protected $fillable = [
        'codigo_interno',
        'codigo_barras',
        'unidad',
        'descripcion',
        'marca',
        'presentacion',
        'subcategoria_id',
        'operacion',
        'afecto_isc',
        'afecto_ivap',
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
        'afecto_isc' => 'boolean',
        'afecto_ivap' => 'boolean',
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

    /* --- Tributos ---------------------------------------------------------
     *
     * `operacion` es SOLO la afectacion del IGV (Catalogo 07). Los otros
     * tratamientos viven en sus propias columnas porque no son lo mismo: una
     * bebida energetica es GRAVADA de IGV y ademas esta en el ambito del ISC.
     *
     * afecto_isc y afecto_ivap son informativos: no entran al XML. Ver
     * App\Sunat\Tributos para el porque.
     */

    /** Un producto sin afectacion de IGV resuelta no puede venderse. */
    public function puedeVenderse(): bool
    {
        return \App\Sunat\Tributos::esVendible($this->operacion);
    }

    /**
     * Tiene precio de venta puesto por el comercio.
     *
     * El catalogo entra sin precios a proposito: precio_compra sale de la
     * lista del proveedor y precio_venta lo decide la tienda. Hasta entonces
     * la columna vale 0.00, que no es "gratis" sino "todavia no se puso a la
     * venta".
     */
    public function tienePrecio(): bool
    {
        return (float) $this->precio_venta > 0;
    }

    /**
     * Listo para cobrarse: activo, con IGV resuelto y con precio.
     *
     * Es la misma idea que el stock. Un producto recien importado se busca,
     * se ve y se puede comprar a un proveedor, pero no se puede cobrar hasta
     * que alguien complete lo que el catalogo no trae. Vender a 0.00 seria
     * regalar mercaderia por un dato que falta.
     */
    public function estaListoParaVender(): bool
    {
        return (bool) $this->estado && $this->puedeVenderse() && $this->tienePrecio();
    }

    /** Solo productos activos y con la afectacion de IGV resuelta. */
    public function scopeVendibles($query)
    {
        return $query->where('estado', 1)
            ->whereIn('operacion', \App\Sunat\Tributos::AFECTACIONES);
    }

    /** Los que ademas ya tienen precio: los unicos que el POS puede cobrar. */
    public function scopeConPrecio($query)
    {
        return $query->vendibles()->where('precio_venta', '>', 0);
    }

    /** @return array<int,string> combinaciones tributarias a revisar. */
    public function advertenciasTributarias(): array
    {
        return \App\Sunat\Tributos::advertencias(
            $this->operacion,
            (bool) $this->afecto_isc,
            (bool) $this->afecto_ivap,
        );
    }

    /* --- Codigos SUNAT derivados de los valores del formulario --- */

    public function getUnidadSunatAttribute(): string
    {
        return \App\Sunat\Catalogo::unidad($this->unidad);
    }

    public function getAfectacionIgvSunatAttribute(): string
    {
        return \App\Sunat\Catalogo::afectacionIgv($this->operacion);
    }

    public function getTributoSunatAttribute(): array
    {
        return \App\Sunat\Catalogo::tributo($this->operacion);
    }

    public function gravaIgv(): bool
    {
        return \App\Sunat\Catalogo::gravaIgv($this->operacion);
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
        return \App\Sunat\Tributos::etiqueta($this->operacion);
    }

    public function getAfectoIscTextoAttribute(): string
    {
        return $this->afecto_isc ? 'Sí' : 'No';
    }

    public function getAfectoIvapTextoAttribute(): string
    {
        return $this->afecto_ivap ? 'Sí' : 'No';
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

    /** Solo estos campos generan entrada en la bitacora. */
    protected array $auditarSolo = ['codigo_interno', 'codigo_barras', 'descripcion', 'subcategoria_id', 'precio_compra', 'precio_venta', 'stock_minimo', 'estado', 'unidad', 'operacion', 'afecto_isc', 'afecto_ivap'];

    public function etiquetaAuditoria(): string
    {
        return $this->codigo_interno . " - " . $this->descripcion;
    }

    public function subcategoria(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Subcategoria::class);
    }

    /**
     * La categoria no se guarda: se deriva de la subcategoria.
     *
     * Tener las dos columnas permitiria grabar "Bebidas / Arroz". Con una sola
     * esa incoherencia no se puede ni escribir.
     */
    public function categoria(): ?\App\Models\Categoria
    {
        return $this->subcategoria?->categoria;
    }

    /**
     * SKU del negocio.
     *
     * Es codigo_interno, no una columna nueva: ya es obligatorio, unico,
     * indexado y estable, y ademas viaja como SellersItemIdentification en los
     * comprobantes electronicos ya emitidos. Este accessor existe para que el
     * codigo pueda hablar el idioma del negocio sin duplicar el dato.
     */
    public function getSkuAttribute(): ?string
    {
        return $this->codigo_interno;
    }
}