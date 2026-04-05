<?php

namespace App\Http\Controllers\Almacen;

use App\Http\Controllers\Controller;
use App\Models\Almacen;
use Illuminate\Http\Request;

class AlmacenController extends Controller
{
    public function index()
    {
        return view('almacen.index');
    }

    public function getData(Request $request)
    {
        $almacenes = Almacen::orderBy('id', 'desc')->get();
        
        $index = 1;
        
        return response()->json([
            'data' => $almacenes->map(function($almacen) use (&$index) {
                return [
                    'correlativo' => $index++,
                    'id' => $almacen->id,
                    'descripcion' => $almacen->descripcion,
                    'establecimiento' => $almacen->establecimiento,
                    'created_at' => $almacen->created_at ? $almacen->created_at->format('d/m/Y H:i') : '-',
                    'acciones' => $this->generateActions($almacen)
                ];
            })
        ]);
    }

    private function generateActions($almacen)
    {
        return '
            <button type="button" class="btn btn-sm btn-info btn-view" 
                    data-id="' . $almacen->id . '"
                    data-descripcion="' . htmlspecialchars($almacen->descripcion) . '"
                    data-establecimiento="' . $almacen->establecimiento . '"
                    data-created_at="' . ($almacen->created_at ? $almacen->created_at->format('d/m/Y H:i') : '-') . '"
                    data-bs-toggle="modal" 
                    data-bs-target="#modalView">
                <i class="bi bi-eye"></i>
            </button>
            <button type="button" class="btn btn-sm btn-warning btn-edit" 
                    data-id="' . $almacen->id . '" 
                    data-descripcion="' . htmlspecialchars($almacen->descripcion) . '"
                    data-establecimiento="' . $almacen->establecimiento . '"
                    data-bs-toggle="modal" 
                    data-bs-target="#modalEdit">
                <i class="bi bi-pencil"></i>
            </button>
            <button type="button" class="btn btn-sm btn-danger btn-delete" 
                    data-id="' . $almacen->id . '"
                    data-descripcion="' . htmlspecialchars($almacen->descripcion) . '">
                <i class="bi bi-trash"></i>
            </button>
        ';
    }

    public function show($id)
    {
        $almacen = Almacen::findOrFail($id);
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $almacen->id,
                'descripcion' => $almacen->descripcion,
                'establecimiento' => $almacen->establecimiento,
                'created_at' => $almacen->created_at ? $almacen->created_at->format('d/m/Y H:i') : '-',
                'updated_at' => $almacen->updated_at ? $almacen->updated_at->format('d/m/Y H:i') : '-'
            ]
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'descripcion' => 'required|string',
            'establecimiento' => 'required|in:Oficina Principal'
        ], [
            'descripcion.required' => 'La descripción es obligatoria.',
            'establecimiento.required' => 'El establecimiento es obligatorio.',
            'establecimiento.in' => 'El establecimiento debe ser Oficina Principal.'
        ]);

        $almacen = Almacen::create($request->all());

        return response()->json([
            'success' => true,
            'message' => '✅ Almacén creado exitosamente',
            'data' => $almacen
        ]);
    }

    public function update(Request $request, $id)
    {
        $almacen = Almacen::findOrFail($id);

        $request->validate([
            'descripcion' => 'required|string',
            'establecimiento' => 'required|in:Oficina Principal'
        ], [
            'descripcion.required' => 'La descripción es obligatoria.',
            'establecimiento.required' => 'El establecimiento es obligatorio.',
            'establecimiento.in' => 'El establecimiento debe ser Oficina Principal.'
        ]);

        $almacen->update($request->all());

        return response()->json([
            'success' => true,
            'message' => '✅ Almacén actualizado exitosamente',
            'data' => $almacen
        ]);
    }

    public function destroy($id)
    {
        $almacen = Almacen::findOrFail($id);
        $almacen->delete();

        return response()->json([
            'success' => true,
            'message' => '✅ Almacén eliminado exitosamente'
        ]);
    }
}