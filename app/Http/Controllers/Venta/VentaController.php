<?php

namespace App\Http\Controllers\Venta;

use App\Http\Controllers\Controller;
use App\Models\Venta;
use App\Models\VentaDetalle;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class VentaController extends Controller
{
    public function index()
    {
        return view('venta.index');
    }

    public function getData(Request $request)
    {
        $ventas = Venta::with(['cliente', 'usuario', 'caja'])
                       ->orderBy('id', 'desc')
                       ->get();
        
        $index = 1;
        
        return response()->json([
            'data' => $ventas->map(function($venta) use (&$index) {
                return [
                    'correlativo' => $index++,
                    'id' => $venta->id,
                    'documento' => $venta->documento,
                    'fecha_emision' => $venta->fecha_emision->format('d/m/Y H:i'),
                    'ruc_dni' => $venta->cliente->numero_documento ?? '00000000',
                    'cliente' => $venta->cliente->nombre_razon_social ?? 'CLIENTES VARIOS',
                    'total' => 'S/ ' . number_format($venta->total, 2),
                    'xml' => $this->getXmlBadge($venta),
                    'cdr' => $this->getCdrBadge($venta),
                    'sunat' => $this->getSunatBadge($venta),
                    'tipo_comprobante' => $venta->tipo_comprobante,
                    'estado' => $venta->estado,
                    'estado_badge' => $venta->estado_badge,
                    'devolucion_badge' => $venta->estado_devolucion !== \App\Estados\EstadoDevolucion::SIN_DEVOLUCION
                        ? $venta->estado_devolucion_badge
                        : '',
                    'pago_badge' => $venta->estado_pago_badge,
                    'acciones' => $this->generateActions($venta)
                ];
            })
        ]);
    }

    private function getXmlBadge($venta)
    {
        return $venta->ruta_xml
            ? '<span class="badge bg-success">Generado</span>'
            : '<span class="badge bg-secondary">Pendiente</span>';
    }

    private function getCdrBadge($venta)
    {
        return $venta->ruta_cdr
            ? '<span class="badge bg-success">Recibido</span>'
            : '<span class="badge bg-secondary">Pendiente</span>';
    }

    private function getSunatBadge($venta)
    {
        // El codigo del CDR precisa el motivo cuando SUNAT rechaza u observa.
        $detalle = $venta->codigo_respuesta ? " ({$venta->codigo_respuesta})" : '';

        // El badge sale de EstadoSunat para no mantener dos vocabularios; aqui
        // solo se le pega el codigo de respuesta cuando aporta el motivo.
        $badge = \App\Estados\EstadoSunat::badge($venta->estado_sunat);

        return $detalle && \App\Estados\EstadoSunat::necesitaAtencion($venta->estado_sunat)
            ? str_replace('</span>', $detalle.'</span>', $badge)
            : $badge;
    }

    private function generateActions($venta)
    {
        return '
            <button type="button" class="btn btn-sm btn-info btn-view" 
                    data-id="' . $venta->id . '"
                    data-bs-toggle="modal" 
                    data-bs-target="#modalView">
                <i class="bi bi-eye"></i>
            </button>
            <button type="button" class="btn btn-sm btn-danger btn-pdf" 
                    data-id="' . $venta->id . '">
                <i class="bi bi-file-pdf"></i>
            </button>
            <button type="button" class="btn btn-sm btn-secondary btn-ticket" 
                    data-id="' . $venta->id . '">
                <i class="bi bi-receipt"></i>
            </button>
        ';
    }

    public function show($id)
    {
        $venta = Venta::with(['cliente', 'usuario', 'caja', 'detalles.producto'])
                      ->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $venta->id,
                'documento' => $venta->documento,
                'tipo_comprobante' => $venta->tipo_comprobante,
                'serie' => $venta->serie,
                'numero' => $venta->numero,
                'fecha_emision' => $venta->fecha_emision->format('d/m/Y H:i:s'),
                'cliente' => [
                    'nombre' => $venta->cliente->nombre_razon_social ?? 'CLIENTES VARIOS',
                    'documento' => $venta->cliente->numero_documento ?? '00000000',
                    'direccion' => $venta->cliente->direccion ?? '-'
                ],
                'tipo_venta' => $venta->tipo_venta,
                'forma_pago' => $venta->forma_pago,
                'subtotal' => number_format($venta->subtotal, 2),
                'igv' => number_format($venta->igv, 2),
                'total' => number_format($venta->total, 2),
                'pagado' => number_format($venta->pagado, 2),
                'cambio' => number_format($venta->cambio, 2),
                'detraccion' => $venta->detraccion ? 'Sí' : 'No',
                'observaciones' => $venta->observaciones ?? '-',
                'caja' => $venta->caja->descripcion ?? '-',
                'usuario' => $venta->usuario->name ?? '-',
                'estado' => $venta->estado,
                'estado_badge' => $venta->estado_badge,
                // Los dos estados van separados tambien en el detalle: el
                // comercial responde "¿esta venta cuenta?" y el de SUNAT
                // "¿esta declarada?". Son preguntas distintas.
                'estado_sunat' => $venta->estado_sunat,
                'estado_sunat_badge' => $this->getSunatBadge($venta),
                'estado_devolucion' => $venta->estado_devolucion,
                'estado_devolucion_badge' => $venta->estado_devolucion_badge,
                'estado_pago_badge' => $venta->estado_pago_badge,
                'devolucion' => $this->resumenDevolucion($venta),
                'detalles' => $venta->detalles->map(function($detalle) use ($venta) {
                    return [
                        'producto' => $detalle->producto->descripcion ?? '-',
                        'codigo' => $detalle->producto->codigo_interno ?? '-',
                        'cantidad' => $detalle->cantidad,
                        'devueltas' => $venta->unidadesDevueltasDe($detalle->producto_id),
                        'precio_unitario' => number_format($detalle->precio_unitario, 2),
                        'total' => number_format($detalle->total, 2)
                    ];
                }),
                'created_at' => $venta->created_at->format('d/m/Y H:i:s'),
                'updated_at' => $venta->updated_at->format('d/m/Y H:i:s')
            ]
        ]);
    }

    /**
     * Importes de la devolucion. Se calculan aqui una vez y no en la vista,
     * para no repetir la misma suma en la tabla, el modal y el PDF.
     */
    private function resumenDevolucion(\App\Models\Venta $venta): array
    {
        $notas = $venta->notasCredito()
            ->where('estado', \App\Estados\EstadoDocumento::REGISTRADA)
            ->get();

        return [
            'tiene' => $notas->isNotEmpty(),
            'monto_original' => number_format((float) $venta->total, 2),
            'monto_devuelto' => number_format($venta->montoDevuelto(), 2),
            'monto_neto' => number_format($venta->montoNeto(), 2),
            'unidades_vendidas' => $venta->unidadesVendidas(),
            'unidades_devueltas' => $venta->unidadesDevueltas(),
            'notas' => $notas->map(fn ($n) => [
                'id' => $n->id,
                'documento' => sprintf('%s-%08d', $n->serie, $n->numero),
                'fecha' => $n->fecha_emision?->format('d/m/Y'),
                'motivo' => $n->tipo_nota,
                'total' => number_format((float) $n->total, 2),
                'estado_sunat' => \App\Estados\EstadoSunat::badge($n->estado_sunat),
            ])->values(),
        ];
    }

    public function generarPdf($id)
    {
        $venta = Venta::with(['cliente', 'usuario', 'caja', 'detalles.producto'])
                      ->findOrFail($id);
        
        $empresa = \App\Models\Empresa::first();
        
        $pdf = Pdf::loadView('venta.pdf', compact('venta', 'empresa'));
        $pdf->setPaper('a4', 'portrait');
        
        return $pdf->download('venta_' . $venta->documento . '.pdf');
    }

  public function imprimirTicket($id)
{
    $venta = Venta::with(['cliente', 'detalles.producto', 'usuario'])
                  ->findOrFail($id);
    
    $empresa = \App\Models\Empresa::first();
    
    // Generar código QR para el ticket (si no existe en la BD)
    $qrCode = null;
    
    try {
        // En la base se guarda el contenido del QR, no la imagen: se renderiza
        // aca. Las ventas viejas no tienen contenido guardado, se recalcula.
        $qrCode = Venta::qrComoImagen($venta->codigo_qr ?: $venta->contenidoQr());
    } catch (\Exception $e) {
        // Si hay error al generar QR, mostrar placeholder
        $qrCode = null;
    }
    
    return view('venta.ticket', compact('venta', 'empresa', 'qrCode'));
}

    public function anular($id)
    {
        try {
            DB::beginTransaction();

            $venta = Venta::findOrFail($id);
            
            if (! \App\Estados\EstadoVenta::puedeTransicionar($venta->estado, \App\Estados\EstadoVenta::ANULADA)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden anular ventas aprobadas'
                ], 422);
            }

            // Anular no es devolver. Si la venta ya tiene notas de credito, la
            // via correcta es seguir emitiendolas: anularla dejaria el stock
            // sumado dos veces (una por la nota, otra por la anulacion) y
            // borraria el rastro de la devolucion.
            if ($venta->notasCredito()->where('estado', \App\Estados\EstadoDocumento::REGISTRADA)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta venta tiene notas de crédito: para revertirla usa una nota de crédito, no la anulación.'
                ], 422);
            }

            // Devolver stock
            foreach ($venta->detalles as $detalle) {
                $stock = \App\Models\ProductoAlmacen::where('producto_id', $detalle->producto_id)
                                                    ->where('almacen_id', $detalle->almacen_id)
                                                    ->lockForUpdate()
                                                    ->first();
                if ($stock) {
                    $stock->stock += $detalle->cantidad;
                    $stock->save();
                }
            }

            $venta->update([
                'estado' => \App\Estados\EstadoVenta::ANULADA,
            ]);


            \App\Models\Auditoria::registrar(
                'VENTA_ANULADA',
                'Venta',
                $venta->id,
                "Anuló la venta {$venta->documento} por S/ " . number_format($venta->total, 2)
            );
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Venta anulada exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al anular la venta: ' . $e->getMessage()
            ], 500);
        }
    }
}