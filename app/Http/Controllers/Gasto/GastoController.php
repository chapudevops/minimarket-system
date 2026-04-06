<?php

namespace App\Http\Controllers\Gasto;

use App\Http\Controllers\Controller;
use App\Models\Gasto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GastoController extends Controller
{
    public function index()
    {
        return view('gasto.index');
    }

    public function getData(Request $request)
    {
        $gastos = Gasto::with('usuario')->orderBy('id', 'desc')->get();
        
        $index = 1;
        
        return response()->json([
            'data' => $gastos->map(function($gasto) use (&$index) {
                return [
                    'correlativo' => $index++,
                    'id' => $gasto->id,
                    'fecha_emision' => $gasto->fecha_emision->format('d/m/Y'),
                    'cuenta' => $gasto->cuenta,
                    'motivo' => $gasto->motivo,
                    'detalle' => $gasto->detalle ?? '-',
                    'monto' => 'S/ ' . number_format($gasto->monto, 2),
                    'usuario' => $gasto->usuario->name ?? '-',
                    'created_at' => $gasto->created_at ? $gasto->created_at->format('d/m/Y H:i') : '-',
                    'acciones' => $this->generateActions($gasto)
                ];
            })
        ]);
    }

    private function generateActions($gasto)
    {
        return '
            <button type="button" class="btn btn-sm btn-danger btn-delete" 
                    data-id="' . $gasto->id . '"
                    data-motivo="' . htmlspecialchars($gasto->motivo) . '"
                    data-bs-toggle="modal" 
                    data-bs-target="#modalDelete">
                <i class="bi bi-trash"></i>
            </button>
        ';
    }

    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            $request->validate([
                'fecha_emision' => 'required|date',
                'motivo' => 'required|string|max:255',
                'cuenta' => 'required|string|max:100',
                'monto' => 'required|numeric|min:0',
                'detalle' => 'nullable|string'
            ], [
                'fecha_emision.required' => 'La fecha de emisión es obligatoria.',
                'fecha_emision.date' => 'La fecha de emisión no es válida.',
                'motivo.required' => 'El motivo es obligatorio.',
                'motivo.max' => 'El motivo no puede exceder los 255 caracteres.',
                'cuenta.required' => 'La cuenta es obligatoria.',
                'cuenta.max' => 'La cuenta no puede exceder los 100 caracteres.',
                'monto.required' => 'El monto es obligatorio.',
                'monto.numeric' => 'El monto debe ser un número.',
                'monto.min' => 'El monto no puede ser negativo.'
            ]);

            $gasto = Gasto::create([
                'fecha_emision' => $request->fecha_emision,
                'motivo' => $request->motivo,
                'cuenta' => $request->cuenta,
                'monto' => $request->monto,
                'detalle' => $request->detalle,
                'usuario_id' => Auth::id()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => '✅ Gasto registrado exitosamente',
                'data' => $gasto
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar el gasto: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $gasto = Gasto::findOrFail($id);
            $gasto->delete();

            return response()->json([
                'success' => true,
                'message' => '✅ Gasto eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el gasto: ' . $e->getMessage()
            ], 500);
        }
    }
}