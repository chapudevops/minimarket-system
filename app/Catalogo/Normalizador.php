<?php

namespace App\Catalogo;

/**
 * Convierte una fila RAW en una fila del catalogo maestro.
 *
 * Lo que NO hace, y es deliberado:
 *   - no deriva precio_compra de precio_referencia (gondola != costo)
 *   - no inventa codigo de barras: solo deja pasar el que trajo el RAW, y
 *     unicamente si valida el digito verificador
 *   - no inventa fecha de vencimiento ni stock
 *   - no fuerza una operacion tributaria que no este justificada
 *
 * Todo eso queda vacio y el reporte lo lista como pendiente.
 */
class Normalizador
{
    public function __construct(
        private readonly NormalizadorMarca $marcas,
        private readonly NormalizadorPresentacion $presentaciones,
        private readonly Taxonomia $taxonomia,
        private readonly ReglasTributarias $tributos,
        private readonly ClaveProducto $claves,
        private readonly GeneradorCodigoInterno $codigos,
    ) {}

    /** Arma el pipeline con los diccionarios del proyecto. */
    public static function porDefecto(?GeneradorCodigoInterno $codigos = null): self
    {
        $marcas = NormalizadorMarca::desdeArchivo();
        $presentaciones = new NormalizadorPresentacion();

        return new self(
            $marcas,
            $presentaciones,
            Taxonomia::desdeArchivo(),
            ReglasTributarias::desdeArchivo(),
            new ClaveProducto($marcas, $presentaciones),
            $codigos ?? new GeneradorCodigoInterno(),
        );
    }

    /**
     * @param  array<string,mixed>  $raw
     * @return array<string,mixed>|null null si la fila no se puede clasificar
     */
    public function normalizar(array $raw): ?array
    {
        $categoria = strtoupper(trim((string) ($raw['categoria'] ?? '')));
        $subcategoria = strtoupper(trim((string) ($raw['subcategoria'] ?? '')));
        $productoTipo = strtoupper(trim((string) ($raw['producto_tipo'] ?? '')));
        $prefijo = $this->taxonomia->prefijo($categoria, $subcategoria);

        // Sin prefijo no hay codigo interno posible: la fila vuelve al RAW
        // para que alguien declare la subcategoria en el diccionario.
        if ($prefijo === null) {
            return null;
        }

        $marca = $this->marcas->normalizar($raw['marca'] ?? null);
        $presentacion = $this->presentaciones->normalizar($raw['presentacion'] ?? null);
        $descripcion = $this->descripcion($raw['descripcion'] ?? null, $marca, $presentacion);

        $clave = $this->claves->de($marca, $raw['descripcion'] ?? null, $raw['presentacion'] ?? null);

        return [
            'codigo_interno'    => $this->codigos->para($prefijo, $clave),
            'codigo_barras'     => CodigoBarras::paraCatalogo($raw['codigo_barras'] ?? null) ?? '',
            'descripcion'       => $descripcion,
            'categoria'         => $categoria,
            'subcategoria'      => $subcategoria,
            'producto_tipo'     => $productoTipo,
            'unidad'            => $this->taxonomia->unidad($categoria, $subcategoria),
            'marca'             => $marca ?? '',
            'presentacion'      => $presentacion ?? '',
            // Los tres ejes tributarios salen separados de la matriz. La
            // afectacion puede quedar PENDIENTE, y entonces la fila no se
            // importa: eso es correcto, no un fallo del pipeline.
            'operacion'         => $this->tributos->igv($categoria, $subcategoria, $productoTipo),
            'afecto_isc'        => $this->tributos->afectoIsc($categoria, $subcategoria, $productoTipo) ? '1' : '0',
            'afecto_ivap'       => $this->tributos->afectoIvap($categoria, $subcategoria, $productoTipo) ? '1' : '0',
            'requiere_revision_tributaria' => $this->tributos->requiereRevision($categoria, $subcategoria, $productoTipo) ? '1' : '0',
            'precio_compra'     => '',
            'precio_venta'      => '',
            'fecha_vencimiento' => '',
            'tipo_producto'     => 'PRODUCTO',
            'foto'              => '',
            'detraccion'        => '0',
            'stock_minimo'      => '',
            'fuente'            => trim((string) ($raw['fuente'] ?? '')),
            'url_fuente'        => trim((string) ($raw['url_fuente'] ?? '')),
        ];
    }

    /** Clave natural de la fila RAW, para agrupar o rastrear. */
    public function clave(array $raw): string
    {
        return $this->claves->de(
            $this->marcas->normalizar($raw['marca'] ?? null),
            $raw['descripcion'] ?? null,
            $raw['presentacion'] ?? null,
        );
    }

    public function marcas(): NormalizadorMarca
    {
        return $this->marcas;
    }

    public function taxonomia(): Taxonomia
    {
        return $this->taxonomia;
    }

    public function tributos(): ReglasTributarias
    {
        return $this->tributos;
    }

    /**
     * La descripcion se deja como la publica la fuente, solo limpiada de
     * espacios y capitalizada; si la fuente no repitio marca ni presentacion,
     * se las agrega para que el nombre en el POS sea buscable.
     *
     * No se reescribe el nombre comercial: eso seria inventar producto.
     */
    private function descripcion(?string $original, ?string $marca, ?string $presentacion): string
    {
        // Texto::titulo capitaliza y normaliza espacios por su cuenta. Pasarle
        // antes por Texto::plano rompia los apostrofes —"Bell's" quedaba como
        // "Bell S", "Kellogg's" como "Kellogg S"— porque plano los convierte en
        // separador y despues ya no hay como distinguirlos de un espacio.
        $base = $this->presentaciones->canonizarUnidades(Texto::titulo($original));

        if ($base === '') {
            return '';
        }

        $partes = [$base];
        $claveBase = Texto::clave($base);

        if ($marca !== null && ! str_contains($claveBase, Texto::clave($marca))) {
            array_unshift($partes, $marca);
        }

        if ($presentacion !== null && ! str_contains($claveBase, Texto::clave($presentacion))) {
            $partes[] = $presentacion;
        }

        return implode(' ', $partes);
    }
}
