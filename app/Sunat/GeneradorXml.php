<?php

namespace App\Sunat;

use App\Models\Empresa;
use App\Models\Venta;
use Greenter\Model\Sale\Invoice;
use Greenter\Xml\Builder\InvoiceBuilder;
use Greenter\XMLSecLibs\Certificate\X509Certificate;
use Greenter\XMLSecLibs\Certificate\X509ContentType;
use Greenter\XMLSecLibs\Sunat\SignedXml;
use Illuminate\Support\Facades\Storage;

/**
 * Genera el XML UBL 2.1 del comprobante, lo firma con el certificado de la
 * empresa y lo deja en el disco privado.
 *
 * El envio al web service de SUNAT es un paso aparte: necesita ext-soap.
 */
class GeneradorXml
{
    /** Donde viven los XML firmados. Disco privado, no public. */
    public const DIRECTORIO = 'comprobantes/xml';

    public function __construct(
        private readonly ConstructorComprobante $constructor = new ConstructorComprobante(),
    ) {
    }

    /**
     * @return array{nombre: string, ruta: string, hash: string, xml: string}
     */
    public function paraVenta(Venta $venta, bool $guardar = true): array
    {
        $comprobante = $this->constructor->desdeVenta($venta);
        $xml = (new InvoiceBuilder())->build($comprobante);
        $firmado = $this->firmar($xml);

        $nombre = $this->nombreArchivo($comprobante);
        $ruta = self::DIRECTORIO . '/' . $nombre;

        if ($guardar) {
            Storage::put($ruta, $firmado);
        }

        return [
            'nombre' => $nombre,
            'ruta'   => $ruta,
            'hash'   => $this->hashDe($firmado),
            'xml'    => $firmado,
        ];
    }

    /**
     * Nombre normalizado por SUNAT: RUC-tipo-serie-correlativo.
     */
    private function nombreArchivo(Invoice $comprobante): string
    {
        return sprintf(
            '%s-%s-%s-%s.xml',
            $comprobante->getCompany()->getRuc(),
            $comprobante->getTipoDoc(),
            $comprobante->getSerie(),
            $comprobante->getCorrelativo()
        );
    }

    private function firmar(string $xml): string
    {
        $empresa = Empresa::first();

        if (! $empresa?->certificado_pfx) {
            throw new \RuntimeException(
                'No hay certificado digital cargado. Subilo desde la configuración de la empresa.'
            );
        }

        $rutaPfx = 'empresa/certificados/' . $empresa->certificado_pfx;

        if (! Storage::exists($rutaPfx)) {
            throw new \RuntimeException("El certificado {$empresa->certificado_pfx} no está en el disco.");
        }

        // El .pfx se convierte a PEM (clave publica + privada), que es el
        // formato que espera el firmador.
        $certificado = new X509Certificate(
            Storage::get($rutaPfx),
            $empresa->clave_certificado ?? ''
        );

        $firmador = new SignedXml();
        $firmador->setCertificate($certificado->export(X509ContentType::PEM));

        return $firmador->signXml($xml);
    }

    /**
     * Valor resumen del comprobante: el DigestValue de la firma. Es lo que va
     * en el QR y lo que SUNAT usa para identificar el documento.
     */
    private function hashDe(string $xmlFirmado): string
    {
        $doc = new \DOMDocument();
        $doc->loadXML($xmlFirmado);

        $xpath = new \DOMXPath($doc);
        $xpath->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');
        $nodo = $xpath->query('//ds:DigestValue')->item(0);

        return $nodo?->nodeValue ?? '';
    }
}
