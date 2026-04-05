<?php

namespace App\Http\Controllers\Producto;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use App\Models\Almacen;
use App\Models\ProductoAlmacen;
use Illuminate\Http\Request;

class ProductoController extends Controller
{
    public function index()
    {
        return view('producto.index');
    }

    public function getData(Request $request)
    {
        $productos = Producto::orderBy('descripcion', 'asc')->get();
        
        $index = 1;
        
        return response()->json([
            'data' => $productos->map(function($producto) use (&$index) {
                return [
                    'correlativo' => $index++,
                    'id' => $producto->id,
                    'codigo_interno' => $producto->codigo_interno,
                    'descripcion' => $producto->descripcion,
                    'unidad' => $producto->unidad,
                    'precio_venta' => 'S/ ' . number_format($producto->precio_venta, 2),
                    'stock_total' => $producto->stock_total,
                    'estado_texto' => $producto->estado ? 'Activo' : 'Inactivo',
                    'created_at' => $producto->created_at ? $producto->created_at->format('d/m/Y H:i') : '-',
                    'acciones' => $this->generateActions($producto)
                ];
            })
        ]);
    }

    private function generateActions($producto)
    {
        return '
            <button type="button" class="btn btn-sm btn-info btn-view" 
                    data-id="' . $producto->id . '"
                    data-bs-toggle="modal" 
                    data-bs-target="#modalView">
                <i class="bi bi-eye"></i>
            </button>
            <button type="button" class="btn btn-sm btn-warning btn-edit" 
                    data-id="' . $producto->id . '"
                    data-bs-toggle="modal" 
                    data-bs-target="#modalEdit">
                <i class="bi bi-pencil"></i>
            </button>
            <button type="button" class="btn btn-sm btn-danger btn-delete" 
                    data-id="' . $producto->id . '"
                    data-nombre="' . htmlspecialchars($producto->descripcion) . '"
                    data-bs-toggle="modal" 
                    data-bs-target="#modalDelete">
                <i class="bi bi-trash"></i>
            </button>
        ';
    }

    public function create()
    {
        $almacenes = Almacen::where('estado', 1)->orderBy('descripcion', 'asc')->get();
        return response()->json([
            'almacenes' => $almacenes
        ]);
    }

    public function show($id)
    {
        $producto = Producto::with('stocks.almacen')->findOrFail($id);
        
        $stocksPorAlmacen = [];
        foreach ($producto->stocks as $stock) {
            $stocksPorAlmacen[] = [
                'almacen_id' => $stock->almacen_id,
                'almacen_nombre' => $stock->almacen->descripcion,
                'stock' => $stock->stock
            ];
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $producto->id,
                'codigo_interno' => $producto->codigo_interno,
                'codigo_barras' => $producto->codigo_barras ?? '-',
                'unidad' => $producto->unidad,
                'descripcion' => $producto->descripcion,
                'marca' => $producto->marca ?? '-',
                'presentacion' => $producto->presentacion ?? '-',
                'operacion_texto' => $producto->operacion_texto,
                'precio_compra' => 'S/ ' . number_format($producto->precio_compra, 2),
                'precio_venta' => 'S/ ' . number_format($producto->precio_venta, 2),
                'fecha_vencimiento' => $producto->fecha_vencimiento ? $producto->fecha_vencimiento->format('d/m/Y') : '-',
                'tipo_producto_texto' => $producto->tipo_producto_texto,
                'detraccion_texto' => $producto->detraccion_texto,
                'stock_minimo' => $producto->stock_minimo,
                'stock_total' => $producto->stock_total,
                'stocks' => $stocksPorAlmacen,
                'estado_texto' => $producto->estado ? 'Activo' : 'Inactivo',
                'created_at' => $producto->created_at ? $producto->created_at->format('d/m/Y H:i') : '-',
                'updated_at' => $producto->updated_at ? $producto->updated_at->format('d/m/Y H:i') : '-'
            ]
        ]);
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'codigo_interno' => 'required|unique:productos,codigo_interno|max:50',
                'codigo_barras' => 'nullable|max:100',
                'unidad' => 'required|max:50',
                'descripcion' => 'required',
                'marca' => 'nullable|max:100',
                'presentacion' => 'nullable|max:100',
                'operacion' => 'required|in:GRAVADO,EXONERADO,INAFECTO',
                'precio_compra' => 'required|numeric|min:0',
                'precio_venta' => 'required|numeric|min:0',
                'fecha_vencimiento' => 'nullable|date',
                'tipo_producto' => 'required|in:PRODUCTO,SERVICIO',
                'detraccion' => 'nullable|boolean',
                'stock_minimo' => 'nullable|integer|min:0',
                'estado' => 'nullable|boolean',
                'stocks' => 'nullable|array'
            ], [
                'codigo_interno.required' => 'El código interno es obligatorio.',
                'codigo_interno.unique' => 'Este código interno ya está registrado.',
                'unidad.required' => 'La unidad es obligatoria.',
                'descripcion.required' => 'La descripción es obligatoria.',
                'operacion.required' => 'La operación es obligatoria.',
                'precio_compra.required' => 'El precio de compra es obligatorio.',
                'precio_venta.required' => 'El precio de venta es obligatorio.',
                'tipo_producto.required' => 'El tipo de producto es obligatorio.'
            ]);

            $data = $request->all();
            $data['estado'] = $request->has('estado') ? true : false;
            $data['detraccion'] = $request->has('detraccion') ? true : false;

            $producto = Producto::create($data);

            // Guardar stocks por almacén
            if ($request->has('stocks')) {
                foreach ($request->stocks as $stockData) {
                    if ($stockData['stock'] > 0) {
                        ProductoAlmacen::create([
                            'producto_id' => $producto->id,
                            'almacen_id' => $stockData['almacen_id'],
                            'stock' => $stockData['stock']
                        ]);
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => '✅ Producto creado exitosamente',
                'data' => $producto
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el producto: ' . $e->getMessage()
            ], 500);
        }
    }

    public function edit($id)
    {
        $producto = Producto::with('stocks.almacen')->findOrFail($id);
        $almacenes = Almacen::orderBy('descripcion', 'asc')->get();
        
        $stocksPorAlmacen = [];
        foreach ($almacenes as $almacen) {
            $stock = $producto->stocks->where('almacen_id', $almacen->id)->first();
            $stocksPorAlmacen[] = [
                'almacen_id' => $almacen->id,
                'almacen_nombre' => $almacen->descripcion,
                'stock' => $stock ? $stock->stock : 0
            ];
        }
        
        return response()->json([
            'success' => true,
            'producto' => $producto,
            'stocks' => $stocksPorAlmacen
        ]);
    }

    public function update(Request $request, $id)
    {
        try {
            $producto = Producto::findOrFail($id);

            $request->validate([
                'codigo_interno' => 'required|unique:productos,codigo_interno,' . $id . '|max:50',
                'codigo_barras' => 'nullable|max:100',
                'unidad' => 'required|max:50',
                'descripcion' => 'required',
                'marca' => 'nullable|max:100',
                'presentacion' => 'nullable|max:100',
                'operacion' => 'required|in:GRAVADO,EXONERADO,INAFECTO',
                'precio_compra' => 'required|numeric|min:0',
                'precio_venta' => 'required|numeric|min:0',
                'fecha_vencimiento' => 'nullable|date',
                'tipo_producto' => 'required|in:PRODUCTO,SERVICIO',
                'detraccion' => 'nullable|boolean',
                'stock_minimo' => 'nullable|integer|min:0',
                'estado' => 'nullable|boolean',
                'stocks' => 'nullable|array'
            ]);

            $data = $request->all();
            $data['estado'] = $request->has('estado') ? true : false;
            $data['detraccion'] = $request->has('detraccion') ? true : false;

            $producto->update($data);

            // Actualizar stocks por almacén
            if ($request->has('stocks')) {
                foreach ($request->stocks as $stockData) {
                    ProductoAlmacen::updateOrCreate(
                        [
                            'producto_id' => $producto->id,
                            'almacen_id' => $stockData['almacen_id']
                        ],
                        [
                            'stock' => $stockData['stock']
                        ]
                    );
                }
            }

            return response()->json([
                'success' => true,
                'message' => '✅ Producto actualizado exitosamente',
                'data' => $producto
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el producto: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $producto = Producto::findOrFail($id);
        $producto->delete();

        return response()->json([
            'success' => true,
            'message' => '✅ Producto eliminado exitosamente'
        ]);
    }

    public function getAlmacenes()
    {
        $almacenes = Almacen::orderBy('descripcion', 'asc')->get();
        return response()->json([
            'success' => true,
            'almacenes' => $almacenes
        ]);
    }
}