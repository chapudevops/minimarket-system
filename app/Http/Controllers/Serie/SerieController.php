<?php

namespace App\Http\Controllers\Serie;

use App\Http\Controllers\Controller;
use App\Models\Serie;
use App\Models\Caja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SerieController extends Controller
{
    public function index()
    {
        return view('serie.index');
    }

    public function getData(Request $request)
    {
        $series = Serie::with('caja')->orderBy('id', 'desc')->get();
        
        $index = 1;
        
        return response()->json([
            'data' => $series->map(function($serie) use (&$index) {
                return [
                    'correlativo' => $index++,
                    'id' => $serie->id,
                    'serie' => $serie->serie,
                    'numero_correlativo' => str_pad($serie->correlativo, 8, '0', STR_PAD_LEFT),
                    'tipo_comprobante' => $serie->tipo_comprobante_texto,
                    'caja' => $serie->caja->descripcion ?? 'No asignada',
                    'caja_id' => $serie->caja_id,
                    'created_at' => $serie->created_at ? $serie->created_at->format('d/m/Y H:i') : '-',
                    'acciones' => $this->generateActions($serie)
                ];
            })
        ]);
    }

    private function generateActions($serie)
    {
        return '
            <button type="button" class="btn btn-sm btn-warning btn-edit" 
                    data-id="' . $serie->id . '" 
                    data-serie="' . $serie->serie . '"
                    data-correlativo="' . $serie->correlativo . '"
                    data-tipo_comprobante="' . $serie->tipo_comprobante . '"
                    data-caja_id="' . $serie->caja_id . '"
                    data-bs-toggle="modal" 
                    data-bs-target="#modalEdit">
                <i class="bi bi-pencil"></i>
            </button>
            <button type="button" class="btn btn-sm btn-danger btn-delete" 
                    data-id="' . $serie->id . '"
                    data-serie="' . $serie->serie . '"
                    data-bs-toggle="modal" 
                    data-bs-target="#modalDelete">
                <i class="bi bi-trash"></i>
            </button>
        ';
    }

    public function getFormData()
    {
        $cajas = Caja::orderBy('descripcion', 'asc')->get();
        
        return response()->json([
            'cajas' => $cajas
        ]);
    }

    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            $request->validate([
                'serie' => 'required|string|max:10',
                'correlativo' => 'required|integer|min:0',
                'tipo_comprobante' => 'required|in:FACTURA,BOLETA,NOTA_CREDITO,NOTA_DEBITO',
                'caja_id' => 'required|exists:cajas,id'
            ], [
                'serie.required' => 'La serie es obligatoria.',
                'serie.max' => 'La serie no puede exceder los 10 caracteres.',
                'correlativo.required' => 'El correlativo es obligatorio.',
                'correlativo.integer' => 'El correlativo debe ser un número entero.',
                'correlativo.min' => 'El correlativo no puede ser negativo.',
                'tipo_comprobante.required' => 'El tipo de comprobante es obligatorio.',
                'tipo_comprobante.in' => 'El tipo de comprobante no es válido.',
                'caja_id.required' => 'Debe seleccionar una caja.',
                'caja_id.exists' => 'La caja seleccionada no existe.'
            ]);

            // Verificar si ya existe una serie con la misma caja
            $existe = Serie::where('serie', $request->serie)
                          ->where('caja_id', $request->caja_id)
                          ->exists();
            
            if ($existe) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya existe una serie con el mismo nombre para esta caja.'
                ], 422);
            }

            $serie = Serie::create([
                'serie' => strtoupper($request->serie),
                'correlativo' => $request->correlativo,
                'tipo_comprobante' => $request->tipo_comprobante,
                'caja_id' => $request->caja_id
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => '✅ Serie creada exitosamente',
                'data' => $serie
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la serie: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $serie = Serie::findOrFail($id);

            $request->validate([
                'serie' => 'required|string|max:10',
                'correlativo' => 'required|integer|min:0',
                'tipo_comprobante' => 'required|in:FACTURA,BOLETA,NOTA_CREDITO,NOTA_DEBITO',
                'caja_id' => 'required|exists:cajas,id'
            ], [
                'serie.required' => 'La serie es obligatoria.',
                'serie.max' => 'La serie no puede exceder los 10 caracteres.',
                'correlativo.required' => 'El correlativo es obligatorio.',
                'correlativo.integer' => 'El correlativo debe ser un número entero.',
                'correlativo.min' => 'El correlativo no puede ser negativo.',
                'tipo_comprobante.required' => 'El tipo de comprobante es obligatorio.',
                'tipo_comprobante.in' => 'El tipo de compprobante no es válido.',
                'caja_id.required' => 'Debe seleccionar una caja.',
                'caja_id.exists' => 'La caja seleccionada no existe.'
            ]);

            // Verificar si ya existe otra serie con la misma caja
            $existe = Serie::where('serie', $request->serie)
                          ->where('caja_id', $request->caja_id)
                          ->where('id', '!=', $id)
                          ->exists();
            
            if ($existe) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya existe una serie con el mismo nombre para esta caja.'
                ], 422);
            }

            $serie->update([
                'serie' => strtoupper($request->serie),
                'correlativo' => $request->correlativo,
                'tipo_comprobante' => $request->tipo_comprobante,
                'caja_id' => $request->caja_id
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => '✅ Serie actualizada exitosamente',
                'data' => $serie
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la serie: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $serie = Serie::findOrFail($id);
            $serie->delete();

            return response()->json([
                'success' => true,
                'message' => '✅ Serie eliminada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la serie: ' . $e->getMessage()
            ], 500);
        }
    }
}