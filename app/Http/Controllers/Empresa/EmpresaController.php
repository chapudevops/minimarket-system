<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EmpresaController extends Controller
{
    /**
     * Display the empresa configuration (solo una empresa)
     */
    public function index()
    {
        // Obtener la primera empresa (solo debe existir una)
        $empresa = Empresa::first();
        
        // Si no existe, crear una nueva con valores por defecto
        if (!$empresa) {
            $empresa = Empresa::create([
                'ruc' => '',
                'razon_social' => '',
                'direccion' => '',
                'pais' => 'Perú',
                'departamento' => '',
                'provincia' => '',
                'distrito' => '',
                'url_api' => 'https://e-beta.sunat.gob.pe/ol-ti-itcpgem/billService',
                'email_contabilidad' => '',
                'cuenta_bancaria_detracciones' => '',
                'nombre_comercial' => '',
                'usuario_secundario' => '',
                'clave' => '',
                'clave_certificado' => '',
                'client_id' => '',
                'client_secret' => '',
                'servidor_sunat' => 'beta',
                'estado' => true
            ]);
        }
        
        return view('empresa.index', compact('empresa'));
    }

    /**
     * Update the empresa configuration with AJAX.
     */
    public function update(Request $request, $id)
    {
        try {
            $empresa = Empresa::findOrFail($id);

            $request->validate([
                'ruc' => 'required|max:11|min:11|unique:empresa,ruc,' . $id,
                'razon_social' => 'required|max:255',
                'direccion' => 'required',
                'pais' => 'required',
                'departamento' => 'required',
                'provincia' => 'required',
                'distrito' => 'required',
                'logo' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
                'certificado_pfx' => 'nullable|file|mimes:pfx|max:5120',
                'email_contabilidad' => 'nullable|email',
                'url_api' => 'nullable|url',
            ]);

            $data = $request->all();

            // Subir nuevo logo
            if ($request->hasFile('logo')) {
                // Eliminar logo anterior
                if ($empresa->logo) {
                    Storage::delete('public/empresa/' . $empresa->logo);
                }
                $logo = $request->file('logo');
                $logoName = time() . '_logo.' . $logo->getClientOriginalExtension();
                $logo->storeAs('public/empresa', $logoName);
                $data['logo'] = $logoName;
            }

            // Subir nuevo certificado
            if ($request->hasFile('certificado_pfx')) {
                if ($empresa->certificado_pfx) {
                    Storage::delete('public/empresa/certificados/' . $empresa->certificado_pfx);
                }
                $certificado = $request->file('certificado_pfx');
                $certificadoName = time() . '_certificado.' . $certificado->getClientOriginalExtension();
                $certificado->storeAs('public/empresa/certificados', $certificadoName);
                $data['certificado_pfx'] = $certificadoName;
            }

            $empresa->update($data);

            // Preparar respuesta con los datos actualizados
            return response()->json([
                'success' => true,
                'message' => 'Configuración actualizada exitosamente',
                'data' => [
                    'id' => $empresa->id,
                    'ruc' => $empresa->ruc,
                    'razon_social' => $empresa->razon_social,
                    'direccion' => $empresa->direccion,
                    'pais' => $empresa->pais,
                    'departamento' => $empresa->departamento,
                    'provincia' => $empresa->provincia,
                    'distrito' => $empresa->distrito,
                    'url_api' => $empresa->url_api,
                    'email_contabilidad' => $empresa->email_contabilidad,
                    'cuenta_bancaria_detracciones' => $empresa->cuenta_bancaria_detracciones,
                    'logo' => $empresa->logo ? asset('storage/empresa/' . $empresa->logo) : null,
                    'nombre_comercial' => $empresa->nombre_comercial,
                    'usuario_secundario' => $empresa->usuario_secundario,
                    'clave' => $empresa->clave,
                    'clave_certificado' => $empresa->clave_certificado,
                    'certificado_pfx' => $empresa->certificado_pfx,
                    'client_id' => $empresa->client_id,
                    'client_secret' => $empresa->client_secret,
                    'servidor_sunat' => $empresa->servidor_sunat,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar: ' . $e->getMessage()
            ], 500);
        }
    }
}