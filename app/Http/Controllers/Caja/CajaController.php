<?php

namespace App\Http\Controllers\Caja;

use App\Http\Controllers\Controller;
use App\Models\Caja;
use Illuminate\Http\Request;

class CajaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('caja.index');
    }

    /**
     * Get data for DataTable.
     */
    public function getData(Request $request)
    {
        // Ordenar por descripción en orden alfabético ascendente (A-Z)
        $cajas = Caja::orderBy('descripcion', 'asc')->get();
        
        // Agregar índice correlativo
        $index = 1;
        
        return response()->json([
            'data' => $cajas->map(function($caja) use (&$index) {
                return [
                    'correlativo' => $index++,
                    'id' => $caja->id,
                    'descripcion' => $caja->descripcion ?? 'Sin descripción',
                    'created_at' => $caja->created_at ? $caja->created_at->format('d/m/Y H:i') : '-',
                    'acciones' => $this->generateActions($caja)
                ];
            })
        ]);
    }

    /**
     * Generate action buttons for each row.
     */
    private function generateActions($caja)
    {
        return '
            <button type="button" class="btn btn-sm btn-warning btn-edit" 
                    data-id="' . $caja->id . '" 
                    data-descripcion="' . htmlspecialchars($caja->descripcion ?? '') . '"
                    data-bs-toggle="modal" 
                    data-bs-target="#modalEdit">
                <i class="bi bi-pencil"></i>
            </button>
            <button type="button" class="btn btn-sm btn-danger btn-delete" 
                    data-id="' . $caja->id . '"
                    data-descripcion="' . htmlspecialchars($caja->descripcion ?? 'Sin descripción') . '">
                <i class="bi bi-trash"></i>
            </button>
        ';
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'descripcion' => 'nullable|string'
        ], [
            'descripcion.string' => 'La descripción debe ser texto.'
        ]);

        $caja = Caja::create($request->all());

        return response()->json([
            'success' => true,
            'message' => '✅ Caja creada exitosamente',
            'data' => [
                'id' => $caja->id,
                'descripcion' => $caja->descripcion ?? 'Sin descripción',
                'created_at' => $caja->created_at ? $caja->created_at->format('d/m/Y H:i') : '-',
                'acciones' => $this->generateActions($caja)
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $caja = Caja::findOrFail($id);

        $request->validate([
            'descripcion' => 'nullable|string'
        ], [
            'descripcion.string' => 'La descripción debe ser texto.'
        ]);

        $caja->update($request->all());

        return response()->json([
            'success' => true,
            'message' => '✅ Caja actualizada exitosamente',
            'data' => [
                'id' => $caja->id,
                'descripcion' => $caja->descripcion ?? 'Sin descripción',
                'created_at' => $caja->created_at ? $caja->created_at->format('d/m/Y H:i') : '-',
                'acciones' => $this->generateActions($caja)
            ]
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $caja = Caja::findOrFail($id);
        $caja->delete();

        return response()->json([
            'success' => true,
            'message' => '✅ Caja eliminada exitosamente'
        ]);
    }
}