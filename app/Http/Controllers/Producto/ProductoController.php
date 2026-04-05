<?php

namespace App\Http\Controllers\Producto;

use App\Http\Controllers\Controller;
use App\Models\Producto;
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
                    'codigo_barras' => $producto->codigo_barras ?? '-',
                    'descripcion' => $producto->descripcion,
                    'marca' => $producto->marca ?? '-',
                    'unidad' => $producto->unidad,
                    'presentacion' => $producto->presentacion ?? '-',
                    'operacion' => $producto->operacion,
                    'operacion_texto' => $producto->operacion_texto,
                    'precio_compra' => 'S/ ' . number_format($producto->precio_compra, 2),
                    'precio_venta' => 'S/ ' . number_format($producto->precio_venta, 2),
                    'stock' => $producto->stock,
                    'tipo_producto' => $producto->tipo_producto_texto,
                    'detraccion' => $producto->detraccion_texto,
                    'estado' => $producto->estado,
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
                    data-codigo_interno="' . $producto->codigo_interno . '"
                    data-codigo_barras="' . $producto->codigo_barras . '"
                    data-unidad="' . $producto->unidad . '"
                    data-descripcion="' . htmlspecialchars($producto->descripcion) . '"
                    data-marca="' . $producto->marca . '"
                    data-presentacion="' . $producto->presentacion . '"
                    data-operacion="' . $producto->operacion . '"
                    data-precio_compra="' . $producto->precio_compra . '"
                    data-precio_venta="' . $producto->precio_venta . '"
                    data-fecha_vencimiento="' . $producto->fecha_vencimiento . '"
                    data-tipo_producto="' . $producto->tipo_producto . '"
                    data-detracion="' . $producto->detraccion . '"
                    data-stock="' . $producto->stock . '"
                    data-stock_minimo="' . $producto->stock_minimo . '"
                    data-estado="' . $producto->estado . '"
                    data-bs-toggle="modal" 
                    data-bs-target="#modalEdit">
                <i class="bi bi-pencil"></i>
            </button>
            <button type="button" class="btn btn-sm btn-danger btn-delete" 
                    data-id="' . $producto->id . '"
                    data-nombre="' . htmlspecialchars($producto->descripcion) . '">
                <i class="bi bi-trash"></i>
            </button>
        ';
    }

    public function show($id)
    {
        $producto = Producto::findOrFail($id);
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
                'stock' => $producto->stock,
                'stock_minimo' => $producto->stock_minimo,
                'estado_texto' => $producto->estado ? 'Activo' : 'Inactivo',
                'created_at' => $producto->created_at ? $producto->created_at->format('d/m/Y H:i') : '-',
                'updated_at' => $producto->updated_at ? $producto->updated_at->format('d/m/Y H:i') : '-'
            ]
        ]);
    }

    public function store(Request $request)
    {
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
            'stock' => 'nullable|integer|min:0',
            'stock_minimo' => 'nullable|integer|min:0',
            'estado' => 'nullable|boolean'
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

        return response()->json([
            'success' => true,
            'message' => '✅ Producto creado exitosamente',
            'data' => $producto
        ]);
    }

    public function update(Request $request, $id)
    {
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
            'stock' => 'nullable|integer|min:0',
            'stock_minimo' => 'nullable|integer|min:0',
            'estado' => 'nullable|boolean'
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

        $producto->update($data);

        return response()->json([
            'success' => true,
            'message' => '✅ Producto actualizado exitosamente',
            'data' => $producto
        ]);
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
}