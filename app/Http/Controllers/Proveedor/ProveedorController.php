<?php

namespace App\Http\Controllers\Proveedor;

use App\Http\Controllers\Controller;
use App\Models\Proveedor;
use Illuminate\Http\Request;

class ProveedorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('proveedor.index');
    }

    /**
     * Get data for DataTable.
     */
    public function getData(Request $request)
    {
        $proveedores = Proveedor::orderBy('nombre_razon_social', 'asc')->get();
        
        $index = 1;
        
        return response()->json([
            'data' => $proveedores->map(function($proveedor) use (&$index) {
                return [
                    'correlativo' => $index++,
                    'id' => $proveedor->id,
                    'tipo_documento' => $proveedor->tipo_documento,
                    'numero_documento' => $proveedor->numero_documento,
                    'nombre_razon_social' => $proveedor->nombre_razon_social,
                    'telefono' => $proveedor->telefono ?? '-',
                    'departamento' => $proveedor->departamento ?? '-',
                    'provincia' => $proveedor->provincia ?? '-',
                    'distrito' => $proveedor->distrito ?? '-',
                    'estado' => $proveedor->estado,
                    'estado_texto' => $proveedor->estado ? 'Activo' : 'Inactivo',
                    'created_at' => $proveedor->created_at ? $proveedor->created_at->format('d/m/Y H:i') : '-',
                    'acciones' => $this->generateActions($proveedor)
                ];
            })
        ]);
    }

    /**
     * Generate action buttons for each row.
     */
    private function generateActions($proveedor)
    {
        return '
            <button type="button" class="btn btn-sm btn-warning btn-edit" 
                    data-id="' . $proveedor->id . '" 
                    data-tipo_documento="' . $proveedor->tipo_documento . '"
                    data-numero_documento="' . $proveedor->numero_documento . '"
                    data-nombre_razon_social="' . htmlspecialchars($proveedor->nombre_razon_social) . '"
                    data-direccion="' . htmlspecialchars($proveedor->direccion ?? '') . '"
                    data-telefono="' . $proveedor->telefono . '"
                    data-departamento="' . $proveedor->departamento . '"
                    data-provincia="' . $proveedor->provincia . '"
                    data-distrito="' . $proveedor->distrito . '"
                    data-estado="' . $proveedor->estado . '"
                    data-bs-toggle="modal" 
                    data-bs-target="#modalEdit">
                <i class="bi bi-pencil"></i>
            </button>
            <button type="button" class="btn btn-sm btn-danger btn-delete" 
                    data-id="' . $proveedor->id . '"
                    data-nombre="' . htmlspecialchars($proveedor->nombre_razon_social) . '">
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
            'tipo_documento' => 'required|in:DNI,RUC,CE',
            'numero_documento' => 'required|unique:proveedores,numero_documento|max:20',
            'nombre_razon_social' => 'required|max:255',
            'direccion' => 'nullable',
            'telefono' => 'nullable|max:20',
            'departamento' => 'nullable|max:100',
            'provincia' => 'nullable|max:100',
            'distrito' => 'nullable|max:100',
            'estado' => 'nullable|boolean'
        ], [
            'tipo_documento.required' => 'El tipo de documento es obligatorio.',
            'tipo_documento.in' => 'El tipo de documento no es válido.',
            'numero_documento.required' => 'El número de documento es obligatorio.',
            'numero_documento.unique' => 'Este número de documento ya está registrado.',
            'nombre_razon_social.required' => 'El nombre o razón social es obligatorio.',
            'telefono.max' => 'El teléfono no puede exceder los 20 caracteres.',
        ]);

        $data = $request->all();
        $data['estado'] = $request->has('estado') ? true : false;

        $proveedor = Proveedor::create($data);

        return response()->json([
            'success' => true,
            'message' => '✅ Proveedor creado exitosamente',
            'data' => $proveedor
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $proveedor = Proveedor::findOrFail($id);

        $request->validate([
            'tipo_documento' => 'required|in:DNI,RUC,CE',
            'numero_documento' => 'required|unique:proveedores,numero_documento,' . $id . '|max:20',
            'nombre_razon_social' => 'required|max:255',
            'direccion' => 'nullable',
            'telefono' => 'nullable|max:20',
            'departamento' => 'nullable|max:100',
            'provincia' => 'nullable|max:100',
            'distrito' => 'nullable|max:100',
            'estado' => 'nullable|boolean'
        ], [
            'tipo_documento.required' => 'El tipo de documento es obligatorio.',
            'tipo_documento.in' => 'El tipo de documento no es válido.',
            'numero_documento.required' => 'El número de documento es obligatorio.',
            'numero_documento.unique' => 'Este número de documento ya está registrado.',
            'nombre_razon_social.required' => 'El nombre o razón social es obligatorio.',
            'telefono.max' => 'El teléfono no puede exceder los 20 caracteres.',
        ]);

        $data = $request->all();
        $data['estado'] = $request->has('estado') ? true : false;

        $proveedor->update($data);

        return response()->json([
            'success' => true,
            'message' => '✅ Proveedor actualizado exitosamente',
            'data' => $proveedor
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $proveedor = Proveedor::findOrFail($id);
        $proveedor->delete();

        return response()->json([
            'success' => true,
            'message' => '✅ Proveedor eliminado exitosamente'
        ]);
    }
}