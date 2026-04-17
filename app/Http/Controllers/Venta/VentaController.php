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
                    'acciones' => $this->generateActions($venta)
                ];
            })
        ]);
    }

    private function getXmlBadge($venta)
    {
        // Simulación - Aquí verificarías si existe el archivo XML
        return '<span class="badge bg-secondary">Pendiente</span>';
    }

    private function getCdrBadge($venta)
    {
        // Simulación - Aquí verificarías si existe el CDR
        return '<span class="badge bg-secondary">Pendiente</span>';
    }

    private function getSunatBadge($venta)
    {
        // Simulación - Aquí verificarías el estado en SUNAT
        return '<span class="badge bg-warning">Pendiente</span>';
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
                'detalles' => $venta->detalles->map(function($detalle) {
                    return [
                        'producto' => $detalle->producto->descripcion ?? '-',
                        'codigo' => $detalle->producto->codigo_interno ?? '-',
                        'cantidad' => $detalle->cantidad,
                        'precio_unitario' => number_format($detalle->precio_unitario, 2),
                        'total' => number_format($detalle->total, 2)
                    ];
                }),
                'created_at' => $venta->created_at->format('d/m/Y H:i:s'),
                'updated_at' => $venta->updated_at->format('d/m/Y H:i:s')
            ]
        ]);
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
        // Si la venta ya tiene QR guardado, úsalo
        if (isset($venta->codigo_qr) && $venta->codigo_qr) {
            $qrCode = $venta->codigo_qr;
        } else {
            // Generar nuevo QR con los datos de la venta
            $qrData = json_encode([
                'documento' => $venta->documento,
                'fecha' => $venta->fecha_emision->format('Y-m-d H:i:s'),
                'total' => $venta->total,
                'tipo' => $venta->tipo_comprobante,
                'serie' => $venta->serie,
                'numero' => $venta->numero,
                'empresa' => $empresa->razon_social ?? 'Mi Empresa',
                'ruc' => $empresa->ruc ?? '00000000000'
            ]);
            
            // Usar Simple QR Code (requiere: composer require simplesoftwareio/simple-qrcode)
            $qrCode = 'data:image/svg+xml;base64,' . base64_encode(
                \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
                    ->size(100)
                    ->generate($qrData)
            );
        }
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
            
            if ($venta->estado != 'COMPLETADA') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden anular ventas completadas'
                ], 422);
            }

            // Devolver stock
            foreach ($venta->detalles as $detalle) {
                $stock = \App\Models\ProductoAlmacen::where('producto_id', $detalle->producto_id)
                                                    ->where('almacen_id', $detalle->almacen_id)
                                                    ->first();
                if ($stock) {
                    $stock->stock += $detalle->cantidad;
                    $stock->save();
                }
            }

            $venta->update([
                'estado' => 'ANULADA'
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => '✅ Venta anulada exitosamente'
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