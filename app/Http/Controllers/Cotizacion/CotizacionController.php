<?php

namespace App\Http\Controllers\Cotizacion;

use App\Http\Controllers\Controller;
use App\Models\Cotizacion;
use App\Models\CotizacionDetalle;
use App\Models\Producto;
use App\Models\Serie;
use App\Models\AperturaCaja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class CotizacionController extends Controller
{
    public function index()
    {
        return view('cotizacion.index');
    }

    public function getData(Request $request)
    {
        $cotizaciones = Cotizacion::with(['cliente'])
                                  ->orderBy('id', 'desc')
                                  ->get();
        
        $index = 1;
        
        return response()->json([
            'data' => $cotizaciones->map(function($cotizacion) use (&$index) {
                return [
                    'correlativo' => $index++,
                    'id' => $cotizacion->id,
                    'documento' => $cotizacion->documento,
                    'fecha_emision' => $cotizacion->fecha_emision ? $cotizacion->fecha_emision->format('d/m/Y H:i') : '-',
                    'fecha_validez' => $cotizacion->fecha_validez ? $cotizacion->fecha_validez->format('d/m/Y') : '-',
                    'cliente' => $cotizacion->cliente->nombre_razon_social ?? 'CLIENTES VARIOS',
                    'total' => 'S/ ' . number_format($cotizacion->total, 2),
                    'estado' => $cotizacion->estado,
                    'estado_badge' => $cotizacion->estado_badge,
                    'acciones' => $this->generateActions($cotizacion)
                ];
            })
        ]);
    }

    private function generateActions($cotizacion)
    {
        $actions = '
            <button type="button" class="btn btn-sm btn-info btn-view" 
                    data-id="' . $cotizacion->id . '"
                    data-bs-toggle="modal" 
                    data-bs-target="#modalView">
                <i class="bi bi-eye"></i>
            </button>
            <button type="button" class="btn btn-sm btn-danger btn-pdf" 
                    data-id="' . $cotizacion->id . '">
                <i class="bi bi-file-pdf"></i>
            </button>
        ';
        
        if ($cotizacion->estado == 'PENDIENTE') {
            $actions .= '
                <button type="button" class="btn btn-sm btn-success btn-aprobar" 
                        data-id="' . $cotizacion->id . '">
                    <i class="bi bi-check-circle"></i> Aprobar
                </button>
                <button type="button" class="btn btn-sm btn-danger btn-rechazar" 
                        data-id="' . $cotizacion->id . '">
                    <i class="bi bi-x-circle"></i> Rechazar
                </button>
            ';
        }
        
        return $actions;
    }

    public function create()
    {
        $productos = Producto::where('estado', 1)
                            ->orderBy('descripcion', 'asc')
                            ->get();
        
        $clientes = \App\Models\Cliente::orderBy('nombre_razon_social', 'asc')->get();
        
        return view('cotizacion.create', compact('productos', 'clientes'));
    }

    public function getSerie(Request $request)
    {
        $cajaAbierta = AperturaCaja::where('responsable_id', Auth::id())
                                   ->where('estado', 'ABIERTA')
                                   ->first();
        
        if (!$cajaAbierta) {
            return response()->json([
                'success' => false,
                'message' => 'No hay una caja abierta'
            ]);
        }
        
        $serie = Serie::where('tipo_comprobante', 'COTIZACION')
                      ->where('caja_id', $cajaAbierta->id)
                      ->first();
        
        if (!$serie) {
            return response()->json([
                'success' => false,
                'message' => 'No hay serie configurada para Cotizaciones'
            ]);
        }
        
        $numero = $serie->correlativo + 1;
        
        return response()->json([
            'success' => true,
            'serie' => $serie->serie,
            'numero' => $numero,
            'documento' => $serie->serie . '-' . str_pad($numero, 8, '0', STR_PAD_LEFT)
        ]);
    }

    public function searchProductos(Request $request)
    {
        $search = $request->get('q');
        
        $productos = Producto::where('estado', 1)
            ->where(function($query) use ($search) {
                $query->where('descripcion', 'LIKE', "%{$search}%")
                    ->orWhere('codigo_interno', 'LIKE', "%{$search}%")
                    ->orWhere('codigo_barras', 'LIKE', "%{$search}%");
            })
            ->limit(10)
            ->get();
        
        return response()->json([
            'success' => true,
            'productos' => $productos->map(function($producto) {
                return [
                    'id' => $producto->id,
                    'codigo_interno' => $producto->codigo_interno,
                    'descripcion' => $producto->descripcion,
                    'precio_venta' => floatval($producto->precio_venta),
                    'unidad' => $producto->unidad,
                    'stock' => $producto->stock_total
                ];
            })
        ]);
    }

    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            $cajaAbierta = AperturaCaja::where('responsable_id', Auth::id())
                                       ->where('estado', 'ABIERTA')
                                       ->first();
            
            if (!$cajaAbierta) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay una caja abierta'
                ], 422);
            }

            $request->validate([
                'cliente_id' => 'required|exists:clientes,id',
                'fecha_validez' => 'required|date|after_or_equal:today',
                'tipo_moneda' => 'required|in:PEN,USD',
                'tipo_cambio' => 'required|numeric|min:0',
                'observaciones' => 'nullable',
                'descuento_general' => 'nullable|numeric|min:0',
                'productos' => 'required|array|min:1',
                'productos.*.id' => 'required|exists:productos,id',
                'productos.*.cantidad' => 'required|integer|min:1',
                'productos.*.precio' => 'required|numeric|min:0',
                'productos.*.almacen_id' => 'required|exists:almacenes,id'
            ]);

            // Obtener serie
            $serie = Serie::where('tipo_comprobante', 'COTIZACION')
                          ->where('caja_id', $cajaAbierta->id)
                          ->first();
            
            if (!$serie) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay serie configurada para Cotizaciones'
                ], 422);
            }

            $numero = $serie->correlativo + 1;
            
            // Calcular totales
            $subtotal = 0;
            foreach ($request->productos as $item) {
                $subtotal += $item['cantidad'] * $item['precio'];
            }
            
            $descuentoGeneral = $request->descuento_general ?? 0;
            $subtotalConDescuento = $subtotal - $descuentoGeneral;
            $igv = $subtotalConDescuento * 0.18;
            $total = $subtotalConDescuento + $igv;

            // Crear cotización
            $cotizacion = Cotizacion::create([
                'serie' => $serie->serie,
                'numero' => $numero,
                'fecha_emision' => now(),
                'fecha_validez' => $request->fecha_validez,
                'cliente_id' => $request->cliente_id,
                'tipo_moneda' => $request->tipo_moneda,
                'tipo_cambio' => $request->tipo_cambio,
                'subtotal' => $subtotalConDescuento,
                'igv' => $igv,
                'total' => $total,
                'descuento' => $descuentoGeneral,
                'observaciones' => $request->observaciones,
                'estado' => 'PENDIENTE',
                'caja_id' => $cajaAbierta->id,
                'usuario_id' => Auth::id()
            ]);

            // Actualizar correlativo
            $serie->correlativo = $numero;
            $serie->save();

            // Crear detalles
            foreach ($request->productos as $item) {
                CotizacionDetalle::create([
                    'cotizacion_id' => $cotizacion->id,
                    'producto_id' => $item['id'],
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $item['precio'],
                    'total' => $item['cantidad'] * $item['precio'],
                    'descuento' => 0,
                    'almacen_id' => $item['almacen_id']
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => '✅ Cotización creada exitosamente',
                'data' => $cotizacion
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la cotización: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $cotizacion = Cotizacion::with(['cliente', 'usuario', 'caja', 'detalles.producto'])
                                ->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $cotizacion->id,
                'documento' => $cotizacion->documento,
                'fecha_emision' => $cotizacion->fecha_emision->format('d/m/Y H:i:s'),
                'fecha_validez' => $cotizacion->fecha_validez->format('d/m/Y'),
                'cliente' => [
                    'nombre' => $cotizacion->cliente->nombre_razon_social ?? 'CLIENTES VARIOS',
                    'documento' => $cotizacion->cliente->numero_documento ?? '00000000',
                    'direccion' => $cotizacion->cliente->direccion ?? '-'
                ],
                'tipo_moneda' => $cotizacion->tipo_moneda_texto,
                'tipo_cambio' => number_format($cotizacion->tipo_cambio, 4),
                'subtotal' => number_format($cotizacion->subtotal, 2),
                'igv' => number_format($cotizacion->igv, 2),
                'total' => number_format($cotizacion->total, 2),
                'descuento' => number_format($cotizacion->descuento, 2),
                'observaciones' => $cotizacion->observaciones ?? '-',
                'estado_badge' => $cotizacion->estado_badge,
                'detalles' => $cotizacion->detalles->map(function($detalle) {
                    return [
                        'producto' => $detalle->producto->descripcion ?? '-',
                        'codigo' => $detalle->producto->codigo_interno ?? '-',
                        'cantidad' => $detalle->cantidad,
                        'precio_unitario' => number_format($detalle->precio_unitario, 2),
                        'total' => number_format($detalle->total, 2),
                        'unidad' => $detalle->producto->unidad ?? 'UND'
                    ];
                }),
                'created_at' => $cotizacion->created_at->format('d/m/Y H:i:s')
            ]
        ]);
    }

    public function aprobar($id)
    {
        try {
            $cotizacion = Cotizacion::findOrFail($id);
            
            if ($cotizacion->estado != 'PENDIENTE') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden aprobar cotizaciones pendientes'
                ], 422);
            }
            
            $cotizacion->update([
                'estado' => 'APROBADA'
            ]);
            
            return response()->json([
                'success' => true,
                'message' => '✅ Cotización aprobada exitosamente'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al aprobar la cotización: ' . $e->getMessage()
            ], 500);
        }
    }

    public function rechazar($id)
    {
        try {
            $cotizacion = Cotizacion::findOrFail($id);
            
            if ($cotizacion->estado != 'PENDIENTE') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden rechazar cotizaciones pendientes'
                ], 422);
            }
            
            $cotizacion->update([
                'estado' => 'RECHAZADA'
            ]);
            
            return response()->json([
                'success' => true,
                'message' => '✅ Cotización rechazada exitosamente'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al rechazar la cotización: ' . $e->getMessage()
            ], 500);
        }
    }

    public function generarPdf($id)
    {
        $cotizacion = Cotizacion::with(['cliente', 'detalles.producto'])
                                ->findOrFail($id);
        
        $empresa = \App\Models\Empresa::first();
        
        $pdf = Pdf::loadView('cotizacion.pdf', compact('cotizacion', 'empresa'));
        $pdf->setPaper('a4', 'portrait');
        
        return $pdf->download('cotizacion_' . $cotizacion->documento . '.pdf');
    }
}