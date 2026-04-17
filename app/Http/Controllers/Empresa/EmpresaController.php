<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EmpresaController extends Controller
{
    public function index()
    {
        $empresa = Empresa::first();
        
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
                'link_ubicacion' => '', // NUEVO CAMPO
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

    public function update(Request $request, $id)
    {
        try {
            $empresa = Empresa::find($id);
            
            if (!$empresa) {
                return response()->json([
                    'success' => false,
                    'message' => 'Empresa no encontrada'
                ], 404);
            }

            // Reglas de validación con mensajes en español
            $rules = [
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
                'link_ubicacion' => 'nullable|url', // NUEVA VALIDACIÓN
            ];

            // Mensajes personalizados en español
            $messages = [
                'ruc.required' => 'El campo RUC es obligatorio',
                'ruc.max' => 'El RUC debe tener máximo 11 caracteres',
                'ruc.min' => 'El RUC debe tener mínimo 11 caracteres',
                'ruc.unique' => 'Este RUC ya está registrado',
                'razon_social.required' => 'El campo Razón Social es obligatorio',
                'razon_social.max' => 'La Razón Social no puede exceder los 255 caracteres',
                'direccion.required' => 'El campo Dirección es obligatorio',
                'pais.required' => 'El campo País es obligatorio',
                'departamento.required' => 'El campo Departamento es obligatorio',
                'provincia.required' => 'El campo Provincia es obligatorio',
                'distrito.required' => 'El campo Distrito es obligatorio',
                'logo.image' => 'El archivo debe ser una imagen',
                'logo.mimes' => 'El logo debe ser un archivo de tipo: jpg, jpeg, png, gif',
                'logo.max' => 'El logo no debe pesar más de 2MB',
                'certificado_pfx.mimes' => 'El certificado debe ser un archivo .pfx',
                'certificado_pfx.max' => 'El certificado no debe pesar más de 5MB',
                'email_contabilidad.email' => 'El correo de contabilidad debe ser una dirección de email válida',
                'url_api.url' => 'La URL API debe ser una URL válida',
                'link_ubicacion.url' => 'El link de ubicación debe ser una URL válida de Google Maps',
            ];

            $request->validate($rules, $messages);

            $data = $request->all();

            // Subir nuevo logo
            if ($request->hasFile('logo')) {
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

            return response()->json([
                'success' => true,
                'message' => '✅ Configuración actualizada exitosamente',
                'data' => [
                    'id' => $empresa->id,
                    'logo' => $empresa->logo ? asset('storage/empresa/' . $empresa->logo) : null,
                    'certificado_pfx' => $empresa->certificado_pfx,
                    'link_ubicacion' => $empresa->link_ubicacion, // NUEVO
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar: ' . $e->getMessage()
            ], 500);
        }
    }
}