<?php

namespace App\Sunat;

use App\Models\Empresa;
use App\Models\Venta;
use Greenter\Model\Response\BillResult;
use Greenter\Ws\Services\BillSender;
use Greenter\Ws\Services\SoapClient;
use Greenter\Ws\Services\SunatEndpoints;
use Illuminate\Support\Facades\Storage;

/**
 * Envia el XML firmado al web service de SUNAT y guarda la respuesta.
 *
 * El CDR (Constancia de Recepcion) es el respaldo legal de que el comprobante
 * fue aceptado: se archiva tal cual llega, comprimido.
 */
class EnviadorSunat
{
    public const DIRECTORIO_CDR = 'comprobantes/cdr';

    public function __construct(
        private readonly GeneradorXml $generador = new GeneradorXml(),
    ) {
    }

    /**
     * Genera el XML si hace falta, lo envia y registra el resultado.
     *
     * @return array{estado: string, codigo: ?string, mensaje: string, cdr: ?string}
     */
    public function enviar(object $documento): array
    {
        $xml = $this->xmlDe($documento);

        $resultado = $this->sender()->send(
            pathinfo($xml['nombre'], PATHINFO_FILENAME),
            $xml['xml']
        );

        $documento->increment('intentos_envio');

        return $resultado instanceof BillResult && $resultado->isSuccess()
            ? $this->registrarCdr($documento, $resultado, $xml)
            : $this->registrarFallo($documento, $resultado, $xml);
    }

    /** Reusa el XML ya generado; si no existe, lo genera y lo guarda. */
    private function xmlDe(object $documento): array
    {
        if ($documento->ruta_xml && Storage::exists($documento->ruta_xml)) {
            return [
                'nombre' => basename($documento->ruta_xml),
                'ruta'   => $documento->ruta_xml,
                'hash'   => $documento->hash_xml,
                'xml'    => Storage::get($documento->ruta_xml),
            ];
        }

        $xml = $this->generador->para($documento);
        $documento->update(['ruta_xml' => $xml['ruta'], 'hash_xml' => $xml['hash']]);

        // Solo las ventas llevan QR impreso en el ticket.
        if ($documento instanceof Venta) {
            $documento->update(['codigo_qr' => $documento->contenidoQr()]);
        }

        return $xml;
    }

    private function registrarCdr(object $documento, BillResult $resultado, array $xml): array
    {
        $cdr = $resultado->getCdrResponse();
        $nombreCdr = 'R-' . pathinfo($xml['nombre'], PATHINFO_FILENAME) . '.zip';
        $rutaCdr = self::DIRECTORIO_CDR . '/' . $nombreCdr;

        // El zip se guarda tal cual llega: es el respaldo ante una fiscalizacion.
        Storage::put($rutaCdr, $resultado->getCdrZip());

        // Codigo 0 es aceptado; 4000 en adelante son observaciones que no
        // impiden la aceptacion pero conviene registrar.
        $codigo = (string) $cdr->getCode();
        $estado = $codigo === '0'
            ? ($cdr->getNotes() ? 'OBSERVADO' : 'ACEPTADO')
            : 'RECHAZADO';

        $documento->update([
            'estado_sunat'          => $estado,
            'ruta_cdr'              => $rutaCdr,
            'codigo_respuesta'      => $codigo,
            'descripcion_respuesta' => trim($cdr->getDescription() . ' ' . implode(' | ', $cdr->getNotes() ?: [])),
            'enviado_sunat_at'      => now(),
        ]);

        return [
            'estado'  => $estado,
            'codigo'  => $codigo,
            'mensaje' => $cdr->getDescription(),
            'cdr'     => $rutaCdr,
        ];
    }

    private function registrarFallo(object $documento, ?BillResult $resultado, array $xml): array
    {
        $error = $resultado?->getError();
        $codigo = $error?->getCode() ? (string) $error->getCode() : null;
        $mensaje = $error?->getMessage() ?: 'SUNAT no devolvió respuesta';

        // Los codigos 2xxx y 3xxx son rechazos definitivos: reintentar no
        // cambia nada. El resto (red, timeout, servicio caido) si es
        // reintentable, asi que el comprobante vuelve a PENDIENTE.
        $definitivo = $codigo !== null && preg_match('/^[23]\d{3}$/', $codigo);

        $documento->update([
            'estado_sunat'          => $definitivo ? 'RECHAZADO' : 'PENDIENTE',
            'codigo_respuesta'      => $codigo,
            'descripcion_respuesta' => $mensaje,
            'enviado_sunat_at'      => now(),
        ]);

        return [
            'estado'  => $definitivo ? 'RECHAZADO' : 'PENDIENTE',
            'codigo'  => $codigo,
            'mensaje' => $mensaje,
            'cdr'     => null,
        ];
    }

    private function sender(): BillSender
    {
        $empresa = Empresa::first();

        if (! $empresa?->usuario_secundario || ! $empresa->clave) {
            throw new \RuntimeException(
                'Faltan las credenciales SOL (usuario secundario y clave) en la configuración de la empresa.'
            );
        }

        $cliente = new SoapClient();
        // SUNAT espera el usuario SOL prefijado con el RUC del emisor.
        $cliente->setCredentials($empresa->ruc . $empresa->usuario_secundario, $empresa->clave);
        $cliente->setService($this->endpoint());

        return (new BillSender())->setClient($cliente);
    }

    /** Los endpoints salen de Greenter, no de una URL escrita a mano. */
    private function endpoint(): string
    {
        return config('sunat.ambiente') === 'produccion'
            ? SunatEndpoints::FE_PRODUCCION
            : SunatEndpoints::FE_BETA;
    }
}
