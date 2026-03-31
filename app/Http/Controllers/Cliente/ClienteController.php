<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('cliente.index');
    }

    /**
     * Get data for DataTable.
     */
    public function getData(Request $request)
    {
        $clientes = Cliente::orderBy('nombre_razon_social', 'asc')->get();
        
        $index = 1;
        
        return response()->json([
            'data' => $clientes->map(function($cliente) use (&$index) {
                return [
                    'correlativo' => $index++,
                    'id' => $cliente->id,
                    'tipo_documento' => $cliente->tipo_documento,
                    'numero_documento' => $cliente->numero_documento,
                    'nombre_razon_social' => $cliente->nombre_razon_social,
                    'telefono' => $cliente->telefono ?? '-',
                    'departamento' => $cliente->departamento ?? '-',
                    'provincia' => $cliente->provincia ?? '-',
                    'distrito' => $cliente->distrito ?? '-',
                    'estado' => $cliente->estado,
                    'estado_texto' => $cliente->estado ? 'Activo' : 'Inactivo',
                    'estado_badge' => $cliente->estado ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-danger">Inactivo</span>',
                    'created_at' => $cliente->created_at ? $cliente->created_at->format('d/m/Y H:i') : '-',
                    'acciones' => $this->generateActions($cliente)
                ];
            })
        ]);
    }

    /**
     * Generate action buttons for each row.
     */
    private function generateActions($cliente)
    {
        return '
            <button type="button" class="btn btn-sm btn-warning btn-edit" 
                    data-id="' . $cliente->id . '" 
                    data-tipo_documento="' . $cliente->tipo_documento . '"
                    data-numero_documento="' . $cliente->numero_documento . '"
                    data-nombre_razon_social="' . htmlspecialchars($cliente->nombre_razon_social) . '"
                    data-direccion="' . htmlspecialchars($cliente->direccion ?? '') . '"
                    data-telefono="' . $cliente->telefono . '"
                    data-departamento="' . $cliente->departamento . '"
                    data-provincia="' . $cliente->provincia . '"
                    data-distrito="' . $cliente->distrito . '"
                    data-estado="' . $cliente->estado . '"
                    data-bs-toggle="modal" 
                    data-bs-target="#modalEdit">
                <i class="bi bi-pencil"></i>
            </button>
            <button type="button" class="btn btn-sm btn-danger btn-delete" 
                    data-id="' . $cliente->id . '"
                    data-nombre="' . htmlspecialchars($cliente->nombre_razon_social) . '">
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
            'numero_documento' => 'required|unique:clientes,numero_documento|max:20',
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

        $cliente = Cliente::create($data);

        return response()->json([
            'success' => true,
            'message' => '✅ Cliente creado exitosamente',
            'data' => $cliente
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $cliente = Cliente::findOrFail($id);

        $request->validate([
            'tipo_documento' => 'required|in:DNI,RUC,CE',
            'numero_documento' => 'required|unique:clientes,numero_documento,' . $id . '|max:20',
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

        $cliente->update($data);

        return response()->json([
            'success' => true,
            'message' => '✅ Cliente actualizado exitosamente',
            'data' => $cliente
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $cliente = Cliente::findOrFail($id);
        $cliente->delete();

        return response()->json([
            'success' => true,
            'message' => '✅ Cliente eliminado exitosamente'
        ]);
    }
}