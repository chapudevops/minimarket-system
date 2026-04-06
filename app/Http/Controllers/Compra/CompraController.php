<?php

namespace App\Http\Controllers\Compra;

use App\Http\Controllers\Controller;
use App\Models\Compra;
use App\Models\CompraDetalle;
use App\Models\Proveedor;
use App\Models\Almacen;
use App\Models\Producto;
use App\Models\ProductoAlmacen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;


class CompraController extends Controller
{
    public function index()
    {
        $compras = Compra::with(['proveedor', 'almacen', 'usuario'])
                        ->orderBy('id', 'desc')
                        ->paginate(10);
        return view('compra.index', compact('compras'));
    }

    public function create()
    {
        $proveedores = Proveedor::where('estado', 1)->orderBy('nombre_razon_social', 'asc')->get();
        $almacenes = Almacen::orderBy('descripcion', 'asc')->get();
        $productos = Producto::where('estado', 1)->orderBy('descripcion', 'asc')->get();
        
        // Obtener siguiente número de compra
        $ultimaCompra = Compra::orderBy('id', 'desc')->first();
        
        
        return view('compra.create', compact('proveedores', 'almacenes', 'productos'));
    }

    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            $request->validate([
                'tipo_comprobante' => 'required',
                'serie' => 'required',
                'numero' => 'required|integer',
                'fecha_emision' => 'required|date',
                'fecha_vencimiento' => 'required|date|after_or_equal:fecha_emision',
                'proveedor_id' => 'required|exists:proveedores,id',
                'almacen_id' => 'required|exists:almacenes,id',
                'tipo_cambio' => 'required|numeric|min:0',
                'tipo_pago' => 'required',
                'productos' => 'required|array|min:1',
                'productos.*.producto_id' => 'required|exists:productos,id',
                'productos.*.cantidad' => 'required|integer|min:1',
                'productos.*.precio_unitario' => 'required|numeric|min:0',
                'observaciones' => 'nullable'
            ]);

            // Calcular totales
            $subtotal = 0;
            foreach ($request->productos as $item) {
                $subtotal += $item['cantidad'] * $item['precio_unitario'];
            }
            $igv = $subtotal * 0.18;
            $total = $subtotal + $igv;

            // Crear compra
            $compra = Compra::create([
                'tipo_comprobante' => $request->tipo_comprobante,
                'serie' => $request->serie,
                'numero' => $request->numero,
                'fecha_emision' => $request->fecha_emision,
                'fecha_vencimiento' => $request->fecha_vencimiento,
                'proveedor_id' => $request->proveedor_id,
                'almacen_id' => $request->almacen_id,
                'tipo_cambio' => $request->tipo_cambio,
                'tipo_pago' => $request->tipo_pago,
                'subtotal' => $subtotal,
                'igv' => $igv,
                'total' => $total,
                'estado' => 'REGISTRADA',
                'usuario_id' => Auth::id(),
                'observaciones' => $request->observaciones
            ]);

            // Crear detalles y actualizar stock
            foreach ($request->productos as $item) {
                CompraDetalle::create([
                    'compra_id' => $compra->id,
                    'producto_id' => $item['producto_id'],
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $item['precio_unitario'],
                    'total' => $item['cantidad'] * $item['precio_unitario']
                ]);

                // Actualizar stock en el almacén
                $stock = ProductoAlmacen::where('producto_id', $item['producto_id'])
                                        ->where('almacen_id', $request->almacen_id)
                                        ->first();
                
                if ($stock) {
                    $stock->stock += $item['cantidad'];
                    $stock->save();
                } else {
                    ProductoAlmacen::create([
                        'producto_id' => $item['producto_id'],
                        'almacen_id' => $request->almacen_id,
                        'stock' => $item['cantidad']
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('compras.index')
                ->with('success', '✅ Compra registrada exitosamente');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error al registrar la compra: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function show($id)
    {
        $compra = Compra::with(['proveedor', 'almacen', 'usuario', 'detalles.producto'])
                        ->findOrFail($id);
        return view('compra.show', compact('compra'));
    }

    public function anular($id)
    {
        try {
            DB::beginTransaction();

            $compra = Compra::findOrFail($id);
            
            if ($compra->estado != 'REGISTRADA') {
                throw new \Exception('Solo se pueden anular compras registradas');
            }

            // Devolver stock al almacén
            foreach ($compra->detalles as $detalle) {
                $stock = ProductoAlmacen::where('producto_id', $detalle->producto_id)
                                        ->where('almacen_id', $compra->almacen_id)
                                        ->first();
                
                if ($stock) {
                    $stock->stock -= $detalle->cantidad;
                    $stock->save();
                }
            }

            $compra->update([
                'estado' => 'ANULADA'
            ]);

            DB::commit();

            return redirect()->route('compras.index')
                ->with('success', '✅ Compra anulada exitosamente');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error al anular la compra: ' . $e->getMessage());
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
                    'precio_compra' => floatval($producto->precio_compra),
                    'unidad' => $producto->unidad
                ];
            })
        ]);
    }


    public function generarPdf($id)
{
    $compra = Compra::with(['proveedor', 'almacen', 'usuario', 'detalles.producto'])
                    ->findOrFail($id);
    
    $pdf = Pdf::loadView('compra.pdf', compact('compra'));
    
    // Configurar papel
    $pdf->setPaper('a4', 'portrait');
    
    // Descargar el PDF
    return $pdf->download('compra_' . $compra->documento . '.pdf');
}

public function imprimir($id)
{
    $compra = Compra::with(['proveedor', 'almacen', 'usuario', 'detalles.producto'])
                    ->findOrFail($id);
    
    $pdf = Pdf::loadView('compra.pdf', compact('compra'));
    
    // Configurar papel
    $pdf->setPaper('a4', 'portrait');
    
    // Mostrar en el navegador para imprimir
    return $pdf->stream('compra_' . $compra->documento . '.pdf');
}
}