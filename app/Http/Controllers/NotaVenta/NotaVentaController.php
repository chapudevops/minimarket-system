<?php

namespace App\Http\Controllers\NotaVenta;

use App\Http\Controllers\Controller;
use App\Models\NotaVenta;
use App\Models\NotaVentaDetalle;
use App\Models\Producto;
use App\Models\Serie;
use App\Models\AperturaCaja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class NotaVentaController extends Controller
{
    public function index()
    {
        return view('nota-venta.index');
    }

    public function getData(Request $request)
    {
        $notas = NotaVenta::with(['cliente'])
                          ->orderBy('id', 'desc')
                          ->get();
        
        $index = 1;
        
        return response()->json([
            'data' => $notas->map(function($nota) use (&$index) {
                return [
                    'correlativo' => $index++,
                    'id' => $nota->id,
                    'documento' => $nota->documento,
                    'fecha_emision' => $nota->fecha_emision ? $nota->fecha_emision->format('d/m/Y H:i') : '-',
                    'ruc_dni' => $nota->cliente->numero_documento ?? '00000000',
                    'cliente' => $nota->cliente->nombre_razon_social ?? 'CLIENTES VARIOS',
                    'total' => 'S/ ' . number_format($nota->total, 2),
                    'estado' => $nota->estado,
                    'estado_badge' => $nota->estado_badge,
                    'acciones' => $this->generateActions($nota)
                ];
            })
        ]);
    }

    private function generateActions($nota)
    {
        return '
            <button type="button" class="btn btn-sm btn-info btn-view" 
                    data-id="' . $nota->id . '"
                    data-bs-toggle="modal" 
                    data-bs-target="#modalView">
                <i class="bi bi-eye"></i>
            </button>
            <button type="button" class="btn btn-sm btn-danger btn-pdf" 
                    data-id="' . $nota->id . '">
                <i class="bi bi-file-pdf"></i>
            </button>
        ';
    }

    public function create()
    {
        $productos = Producto::where('estado', 1)
                            ->orderBy('descripcion', 'asc')
                            ->get();
        
        $clientes = \App\Models\Cliente::orderBy('nombre_razon_social', 'asc')->get();
        
        return view('nota-venta.create', compact('productos', 'clientes'));
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
        
        $serie = Serie::where('tipo_comprobante', 'NOTA_VENTA')
                      ->where('caja_id', $cajaAbierta->id)
                      ->first();
        
        if (!$serie) {
            return response()->json([
                'success' => false,
                'message' => 'No hay serie configurada para Notas de Venta'
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

    public function getStock(Request $request)
    {
        $productoId = $request->get('producto_id');
        $almacenId = $request->get('almacen_id');
        
        $stock = \App\Models\ProductoAlmacen::where('producto_id', $productoId)
                                            ->where('almacen_id', $almacenId)
                                            ->first();
        
        return response()->json([
            'success' => true,
            'stock' => $stock ? $stock->stock : 0
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
                'tipo_nota' => 'required|in:CREDITO_FISCAL,DEBITO_FISCAL,OTRO',
                'observaciones' => 'nullable',
                'productos' => 'required|array|min:1',
                'productos.*.id' => 'required|exists:productos,id',
                'productos.*.cantidad' => 'required|integer|min:1',
                'productos.*.precio' => 'required|numeric|min:0',
                'productos.*.almacen_id' => 'required|exists:almacenes,id'
            ]);

            // Validar stock
            foreach ($request->productos as $item) {
                $stock = \App\Models\ProductoAlmacen::where('producto_id', $item['id'])
                                                    ->where('almacen_id', $item['almacen_id'])
                                                    ->first();
                
                if (!$stock || $stock->stock < $item['cantidad']) {
                    $producto = Producto::find($item['id']);
                    return response()->json([
                        'success' => false,
                        'message' => "Stock insuficiente para: {$producto->descripcion}"
                    ], 422);
                }
            }

            // Obtener serie
            $serie = Serie::where('tipo_comprobante', 'NOTA_VENTA')
                          ->where('caja_id', $cajaAbierta->id)
                          ->first();
            
            if (!$serie) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay serie configurada para Notas de Venta'
                ], 422);
            }

            $numero = $serie->correlativo + 1;
            
            // Calcular totales
            $subtotal = 0;
            foreach ($request->productos as $item) {
                $subtotal += $item['cantidad'] * $item['precio'];
            }
            $igv = $subtotal * 0.18;
            $total = $subtotal + $igv;

            // Crear nota de venta
            $nota = NotaVenta::create([
                'tipo_comprobante' => 'NOTA_VENTA',
                'serie' => $serie->serie,
                'numero' => $numero,
                'fecha_emision' => now(),
                'cliente_id' => $request->cliente_id,
                'tipo_nota' => $request->tipo_nota,
                'subtotal' => $subtotal,
                'igv' => $igv,
                'total' => $total,
                'detraccion' => $request->has('detraccion'),
                'observaciones' => $request->observaciones,
                'caja_id' => $cajaAbierta->id,
                'usuario_id' => Auth::id(),
                'estado' => 'REGISTRADA'
            ]);

            // Actualizar correlativo
            $serie->correlativo = $numero;
            $serie->save();

            // Crear detalles y descontar stock
            foreach ($request->productos as $item) {
                NotaVentaDetalle::create([
                    'nota_venta_id' => $nota->id,
                    'producto_id' => $item['id'],
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $item['precio'],
                    'total' => $item['cantidad'] * $item['precio'],
                    'almacen_id' => $item['almacen_id']
                ]);

                // Descontar stock
                $stock = \App\Models\ProductoAlmacen::where('producto_id', $item['id'])
                                                    ->where('almacen_id', $item['almacen_id'])
                                                    ->first();
                $stock->stock -= $item['cantidad'];
                $stock->save();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => '✅ Nota de Venta creada exitosamente',
                'data' => $nota
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la nota de venta: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $nota = NotaVenta::with(['cliente', 'usuario', 'caja', 'detalles.producto'])
                         ->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $nota->id,
                'documento' => $nota->documento,
                'fecha_emision' => $nota->fecha_emision->format('d/m/Y H:i:s'),
                'cliente' => [
                    'nombre' => $nota->cliente->nombre_razon_social ?? 'CLIENTES VARIOS',
                    'documento' => $nota->cliente->numero_documento ?? '00000000',
                    'direccion' => $nota->cliente->direccion ?? '-'
                ],
                'tipo_nota' => $nota->tipo_nota_texto,
                'subtotal' => number_format($nota->subtotal, 2),
                'igv' => number_format($nota->igv, 2),
                'total' => number_format($nota->total, 2),
                'detraccion' => $nota->detraccion ? 'Sí' : 'No',
                'observaciones' => $nota->observaciones ?? '-',
                'caja' => $nota->caja->descripcion ?? '-',
                'usuario' => $nota->usuario->name ?? '-',
                'estado_badge' => $nota->estado_badge,
                'detalles' => $nota->detalles->map(function($detalle) {
                    return [
                        'producto' => $detalle->producto->descripcion ?? '-',
                        'codigo' => $detalle->producto->codigo_interno ?? '-',
                        'cantidad' => $detalle->cantidad,
                        'precio_unitario' => number_format($detalle->precio_unitario, 2),
                        'total' => number_format($detalle->total, 2)
                    ];
                }),
                'created_at' => $nota->created_at->format('d/m/Y H:i:s')
            ]
        ]);
    }

    public function generarPdf($id)
    {
        $nota = NotaVenta::with(['cliente', 'detalles.producto'])
                         ->findOrFail($id);
        
        $empresa = \App\Models\Empresa::first();
        
        $pdf = Pdf::loadView('nota-venta.pdf', compact('nota', 'empresa'));
        $pdf->setPaper('a4', 'portrait');
        
        return $pdf->download('nota_venta_' . $nota->documento . '.pdf');
    }
}