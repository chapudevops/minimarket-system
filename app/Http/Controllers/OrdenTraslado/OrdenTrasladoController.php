<?php

namespace App\Http\Controllers\OrdenTraslado;

use App\Http\Controllers\Controller;
use App\Models\OrdenTraslado;
use App\Models\OrdenTrasladoDetalle;
use App\Models\Almacen;
use App\Models\Producto;
use App\Models\ProductoAlmacen;
use App\Models\Serie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrdenTrasladoController extends Controller
{
    public function index()
    {
        $ordenes = OrdenTraslado::with(['almacenOrigen', 'almacenDestino', 'creador'])
                                ->orderBy('id', 'desc')
                                ->paginate(10);
        return view('orden_traslado.index', compact('ordenes'));
    }

    public function create()
    {
        $almacenes = Almacen::orderBy('descripcion', 'asc')->get();
        $productos = Producto::where('estado', 1)->orderBy('descripcion', 'asc')->get();
        
        // Obtener siguiente número de serie
        $ultimaOrden = OrdenTraslado::orderBy('id', 'desc')->first();
        $numero = $ultimaOrden ? $ultimaOrden->numero + 1 : 1;
        $serie = 'T001';
        
        return view('orden_traslado.create', compact('almacenes', 'productos', 'serie', 'numero'));
    }

    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            $request->validate([
                'serie' => 'required',
                'numero' => 'required|integer',
                'fecha_emision' => 'required|date',
                'fecha_vencimiento' => 'required|date|after_or_equal:fecha_emision',
                'almacen_origen_id' => 'required|different:almacen_destino_id',
                'almacen_destino_id' => 'required',
                'observaciones' => 'nullable',
                'productos' => 'required|array|min:1',
                'productos.*.id' => 'required|exists:productos,id',
                'productos.*.cantidad' => 'required|integer|min:1',
                'productos.*.precio' => 'required|numeric|min:0'
            ], [
                'almacen_origen_id.different' => 'El almacén de origen y destino deben ser diferentes',
                'productos.required' => 'Debe agregar al menos un producto',
                'productos.*.cantidad.min' => 'La cantidad debe ser mayor a 0'
            ]);

            // Validar stock en almacén origen
            foreach ($request->productos as $item) {
                $stock = ProductoAlmacen::where('producto_id', $item['id'])
                                        ->where('almacen_id', $request->almacen_origen_id)
                                        ->first();
                
                if (!$stock || $stock->stock < $item['cantidad']) {
                    $producto = Producto::find($item['id']);
                    throw new \Exception("Stock insuficiente para el producto: {$producto->descripcion}. Stock disponible: " . ($stock ? $stock->stock : 0));
                }
            }

            // Crear orden de traslado
            $orden = OrdenTraslado::create([
                'serie' => $request->serie,
                'numero' => $request->numero,
                'fecha_emision' => $request->fecha_emision,
                'fecha_vencimiento' => $request->fecha_vencimiento,
                'almacen_origen_id' => $request->almacen_origen_id,
                'almacen_destino_id' => $request->almacen_destino_id,
                'observaciones' => $request->observaciones,
                'estado' => 'PENDIENTE',
                'creado_por' => Auth::id()
            ]);

            // Crear detalles
            foreach ($request->productos as $item) {
                OrdenTrasladoDetalle::create([
                    'orden_traslado_id' => $orden->id,
                    'producto_id' => $item['id'],
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $item['precio']
                ]);
            }

            DB::commit();

            return redirect()->route('traslados.index')
                ->with('success', '✅ Orden de traslado creada exitosamente');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error al crear la orden: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function show($id)
    {
        $orden = OrdenTraslado::with([
            'almacenOrigen', 
            'almacenDestino', 
            'creador', 
            'aprobador', 
            'anulador',
            'detalles.producto'
        ])->findOrFail($id);
        
        return view('orden_traslado.show', compact('orden'));
    }

    public function aprobar($id)
    {
        try {
            DB::beginTransaction();

            $orden = OrdenTraslado::findOrFail($id);
            
            if ($orden->estado != 'PENDIENTE') {
                throw new \Exception('Solo se pueden aprobar órdenes pendientes');
            }

            // Validar stock nuevamente antes de aprobar
            foreach ($orden->detalles as $detalle) {
                $stock = ProductoAlmacen::where('producto_id', $detalle->producto_id)
                                        ->where('almacen_id', $orden->almacen_origen_id)
                                        ->first();
                
                if (!$stock || $stock->stock < $detalle->cantidad) {
                    throw new \Exception("Stock insuficiente para el producto: {$detalle->producto->descripcion}");
                }
            }

            // Descontar stock del almacén origen
            foreach ($orden->detalles as $detalle) {
                $stockOrigen = ProductoAlmacen::where('producto_id', $detalle->producto_id)
                                              ->where('almacen_id', $orden->almacen_origen_id)
                                              ->first();
                $stockOrigen->stock -= $detalle->cantidad;
                $stockOrigen->save();

                // Aumentar stock del almacén destino
                $stockDestino = ProductoAlmacen::where('producto_id', $detalle->producto_id)
                                               ->where('almacen_id', $orden->almacen_destino_id)
                                               ->first();
                
                if ($stockDestino) {
                    $stockDestino->stock += $detalle->cantidad;
                    $stockDestino->save();
                } else {
                    ProductoAlmacen::create([
                        'producto_id' => $detalle->producto_id,
                        'almacen_id' => $orden->almacen_destino_id,
                        'stock' => $detalle->cantidad
                    ]);
                }
            }

            $orden->update([
                'estado' => 'APROBADO',
                'aprobado_por' => Auth::id(),
                'fecha_aprobacion' => now()
            ]);

            DB::commit();

            return redirect()->route('traslados.index')
                ->with('success', '✅ Orden de traslado aprobada y stock actualizado');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error al aprobar la orden: ' . $e->getMessage());
        }
    }

    public function anular(Request $request, $id)
    {
        try {
            $orden = OrdenTraslado::findOrFail($id);
            
            if ($orden->estado != 'PENDIENTE') {
                throw new \Exception('Solo se pueden anular órdenes pendientes');
            }

            $request->validate([
                'motivo_anulacion' => 'required|string|min:5'
            ], [
                'motivo_anulacion.required' => 'Debe especificar un motivo de anulación',
                'motivo_anulacion.min' => 'El motivo debe tener al menos 5 caracteres'
            ]);

            $orden->update([
                'estado' => 'ANULADO',
                'anulado_por' => Auth::id(),
                'fecha_anulacion' => now(),
                'motivo_anulacion' => $request->motivo_anulacion
            ]);

            return redirect()->route('traslados.index')
                ->with('success', '✅ Orden de traslado anulada exitosamente');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al anular la orden: ' . $e->getMessage());
        }
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
                'precio_venta' => floatval($producto->precio_venta), // Convertir a float
                'unidad' => $producto->unidad
            ];
        })
    ]);
}
    public function getStockProducto(Request $request)
    {
        $productoId = $request->get('producto_id');
        $almacenId = $request->get('almacen_id');
        
        $stock = ProductoAlmacen::where('producto_id', $productoId)
                                ->where('almacen_id', $almacenId)
                                ->first();
        
        return response()->json([
            'success' => true,
            'stock' => $stock ? $stock->stock : 0
        ]);
    }
}