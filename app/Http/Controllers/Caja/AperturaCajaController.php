<?php

namespace App\Http\Controllers\Caja;

use App\Http\Controllers\Controller;
use App\Models\AperturaCaja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AperturaCajaController extends Controller
{
  public function index()
{
    return view('apertura-caja.index');
}

public function getData(Request $request)
{
    $aperturas = AperturaCaja::with('responsable')->orderBy('id', 'desc')->get();
    
    $index = 1;
    
    return response()->json([
        'data' => $aperturas->map(function($apertura) use (&$index) {
            return [
                'correlativo' => $index++,
                'id' => $apertura->id,
                'fecha_apertura' => $apertura->fecha_apertura->format('d/m/Y'),
                'responsable' => $apertura->responsable->name ?? '-',
                'monto_inicial' => 'S/ ' . number_format($apertura->monto_inicial, 2),
                'estado' => $apertura->estado,
                'estado_badge' => $apertura->estado_badge,
                'created_at' => $apertura->created_at ? $apertura->created_at->format('d/m/Y H:i') : '-',
                'acciones' => $this->generateActions($apertura)
            ];
        })
    ]);
}

    private function generateActions($apertura)
    {
        if ($apertura->estado == 'ABIERTA') {
            return '
                <button type="button" class="btn btn-sm btn-danger btn-cerrar" 
                        data-id="' . $apertura->id . '"
                        data-responsable="' . htmlspecialchars($apertura->responsable->name ?? '-') . '"
                        data-monto_inicial="' . number_format($apertura->monto_inicial, 2) . '"
                        data-bs-toggle="modal" 
                        data-bs-target="#modalCerrar">
                    <i class="bi bi-x-circle"></i> Cerrar Caja
                </button>
            ';
        }
        return '
            <span class="text-muted">Sin acciones</span>
        ';
    }

    public function verificarCajaAbierta()
    {
        $cajaAbierta = AperturaCaja::where('estado', 'ABIERTA')
                                   ->where('responsable_id', Auth::id())
                                   ->exists();
        
        return response()->json([
            'caja_abierta' => $cajaAbierta
        ]);
    }

    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            // Verificar si ya tiene una caja abierta
            $cajaAbierta = AperturaCaja::where('estado', 'ABIERTA')
                                       ->where('responsable_id', Auth::id())
                                       ->exists();
            
            if ($cajaAbierta) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya tienes una caja abierta. Debes cerrarla antes de abrir una nueva.'
                ], 422);
            }

            $request->validate([
                'monto_inicial' => 'required|numeric|min:0',
                'fecha_apertura' => 'required|date'
            ], [
                'monto_inicial.required' => 'El monto inicial es obligatorio.',
                'monto_inicial.numeric' => 'El monto inicial debe ser un número.',
                'monto_inicial.min' => 'El monto inicial no puede ser negativo.',
                'fecha_apertura.required' => 'La fecha de apertura es obligatoria.',
                'fecha_apertura.date' => 'La fecha de apertura no es válida.'
            ]);

            $apertura = AperturaCaja::create([
                'fecha_apertura' => $request->fecha_apertura,
                'hora_apertura' => now(),
                'responsable_id' => Auth::id(),
                'monto_inicial' => $request->monto_inicial,
                'estado' => 'ABIERTA'
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => '✅ Caja abierta exitosamente',
                'data' => $apertura
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al abrir la caja: ' . $e->getMessage()
            ], 500);
        }
    }

    public function cerrar(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $apertura = AperturaCaja::findOrFail($id);
            
            if ($apertura->estado != 'ABIERTA') {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta caja ya está cerrada.'
                ], 422);
            }

            $request->validate([
                'monto_cierre' => 'required|numeric|min:0'
            ], [
                'monto_cierre.required' => 'El monto de cierre es obligatorio.',
                'monto_cierre.numeric' => 'El monto de cierre debe ser un número.',
                'monto_cierre.min' => 'El monto de cierre no puede ser negativo.'
            ]);

            $apertura->update([
                'estado' => 'CERRADA',
                'monto_cierre' => $request->monto_cierre,
                'fecha_cierre' => now(),
                'hora_cierre' => now(),
                'responsable_cierre_id' => Auth::id()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => '✅ Caja cerrada exitosamente',
                'data' => $apertura
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al cerrar la caja: ' . $e->getMessage()
            ], 500);
        }
    }
}