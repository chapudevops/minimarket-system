<?php

namespace App\Models;

use App\Estados\EstadoDevolucion;
use App\Estados\EstadoDocumento;
use App\Estados\EstadoPago;
use App\Estados\EstadoSunat;
use App\Estados\EstadoVenta;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    use HasFactory;

    protected $table = 'ventas';

    protected $fillable = [
        'tipo_comprobante',
        'serie',
        'numero',
        'fecha_emision',
        'cliente_id',
        'tipo_venta',
        'forma_pago',
        'subtotal',
        'igv',
        'total',
        'pagado',
        'cambio',
        'detraccion',
        'observaciones',
        'codigo_qr',
        'caja_id',
        'usuario_id',
        'estado',
        'estado_devolucion',
        'estado_pago',
        'estado_sunat',
        'hash_xml',
        'ruta_xml',
        'ruta_cdr',
        'codigo_respuesta',
        'descripcion_respuesta',
        'ticket_sunat',
        'enviado_sunat_at',
        'intentos_envio',
    ];

    protected $casts = [
        'fecha_emision' => 'datetime',
        'subtotal' => 'decimal:2',
        'igv' => 'decimal:2',
        'total' => 'decimal:2',
        'pagado' => 'decimal:2',
        'cambio' => 'decimal:2',
        'detraccion' => 'boolean',
        'enviado_sunat_at' => 'datetime',
    ];

    public function notasCredito()
    {
        return $this->hasMany(NotaCredito::class, 'venta_id');
    }

    /* ---------- Estado comercial ---------- */

    /**
     * Venta valida a efectos de negocio.
     *
     * Deliberadamente NO mira estado_sunat: una venta rechazada por SUNAT
     * sigue siendo una venta que ocurrio y que hay que regularizar, no una
     * venta que nunca existio.
     */
    public function esValida(): bool
    {
        return $this->estado === EstadoVenta::APROBADA;
    }

    public function estaAnulada(): bool
    {
        return $this->estado === EstadoVenta::ANULADA;
    }

    /* ---------- Devoluciones ---------- */

    /** Unidades vendidas en total. */
    public function unidadesVendidas(): int
    {
        return (int) $this->detalles()->sum('cantidad');
    }

    /** Unidades ya devueltas por notas de credito vigentes. */
    public function unidadesDevueltas(): int
    {
        return (int) NotaCreditoDetalle::query()
            ->join('notas_credito', 'notas_credito.id', '=', 'nota_credito_detalles.nota_credito_id')
            ->where('notas_credito.venta_id', $this->id)
            ->where('notas_credito.estado', EstadoDocumento::REGISTRADA)
            ->sum('nota_credito_detalles.cantidad');
    }

    /** Unidades ya devueltas de UN producto concreto. */
    public function unidadesDevueltasDe(int $productoId): int
    {
        return (int) NotaCreditoDetalle::query()
            ->join('notas_credito', 'notas_credito.id', '=', 'nota_credito_detalles.nota_credito_id')
            ->where('notas_credito.venta_id', $this->id)
            ->where('notas_credito.estado', EstadoDocumento::REGISTRADA)
            ->where('nota_credito_detalles.producto_id', $productoId)
            ->sum('nota_credito_detalles.cantidad');
    }

    /** Importe total devuelto por notas de credito vigentes. */
    public function montoDevuelto(): float
    {
        return (float) $this->notasCredito()
            ->where('estado', EstadoDocumento::REGISTRADA)
            ->sum('total');
    }

    /** Lo que la venta dejo realmente en caja despues de devoluciones. */
    public function montoNeto(): float
    {
        return round((float) $this->total - $this->montoDevuelto(), 2);
    }

    /* ---------- Badges ---------- */

    public function getEstadoBadgeAttribute(): string
    {
        return EstadoVenta::badge($this->estado);
    }

    public function getEstadoSunatBadgeAttribute(): string
    {
        return EstadoSunat::badge($this->estado_sunat);
    }

    public function getEstadoDevolucionBadgeAttribute(): string
    {
        return EstadoDevolucion::badge($this->estado_devolucion);
    }

    public function getEstadoPagoBadgeAttribute(): string
    {
        return EstadoPago::badge($this->estado_pago);
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function caja()
    {
        return $this->belongsTo(Caja::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class);
    }

    public function detalles()
    {
        return $this->hasMany(VentaDetalle::class);
    }

    public function cuotas()
    {
        return $this->hasMany(VentaCuota::class);
    }

    /** Catalogo 01 de SUNAT: FACTURA=01, BOLETA=03. */
    public function getTipoComprobanteSunatAttribute(): ?string
    {
        return \App\Sunat\Catalogo::comprobante($this->tipo_comprobante);
    }

    /**
     * Contenido que codifica el QR del comprobante.
     */
    /**
     * Contenido del QR en el formato que exige SUNAT: campos separados por "|".
     *
     *   RUC | tipo comprobante | serie | numero | IGV | total | fecha |
     *   tipo doc. adquiriente | nro doc. adquiriente | hash del XML firmado
     *
     * El hash sale del XML firmado, asi que hasta que exista la firma el campo
     * va vacio. Antes se guardaba un JSON, que ningun validador de SUNAT lee.
     */
    public function contenidoQr(): string
    {
        $empresa = Empresa::first();

        return implode('|', [
            $empresa->ruc ?? '00000000000',
            $this->tipo_comprobante_sunat ?? '03',
            $this->serie,
            $this->numero,
            number_format((float) $this->igv, 2, '.', ''),
            number_format((float) $this->total, 2, '.', ''),
            $this->fecha_emision->format('Y-m-d'),
            $this->cliente?->tipo_documento_sunat ?? '0',
            $this->cliente?->numero_documento ?? '',
            $this->hash_xml ?? '',
        ]);
    }

    /**
     * El QR como data-URI listo para incrustar. La imagen se arma al momento de
     * mostrarla; en la base solo se guarda el contenido.
     */
    public static function qrComoImagen(string $contenido): string
    {
        return 'data:image/svg+xml;base64,' . base64_encode(
            \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(100)->generate($contenido)
        );
    }

    public function getDocumentoAttribute()
    {
        return $this->serie . '-' . str_pad($this->numero, 8, '0', STR_PAD_LEFT);
    }
}