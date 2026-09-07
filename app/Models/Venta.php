<?php

namespace App\Models;

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