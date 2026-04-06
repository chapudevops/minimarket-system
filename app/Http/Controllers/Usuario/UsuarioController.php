<?php

namespace App\Http\Controllers\Usuario;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Caja;
use App\Models\Almacen;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UsuarioController extends Controller
{
    public function index()
    {
        return view('usuario.index');
    }

    public function getData(Request $request)
{
    $usuarios = User::with(['caja', 'almacen', 'roles'])->orderBy('id', 'desc')->get();
    
    $index = 1;
    
    return response()->json([
        'data' => $usuarios->map(function($usuario) use (&$index) {
            return [
                'correlativo' => $index++,
                'id' => $usuario->id,
                'name' => $usuario->name,
                'email' => $usuario->email,
                'caja' => $usuario->caja ? $usuario->caja->descripcion : 'No asignada', // Cambiado a descripcion
                'almacen' => $usuario->almacen ? $usuario->almacen->descripcion : 'No asignado',
                'roles' => $usuario->roles->pluck('nombre')->implode(', '),
                'estado' => $usuario->estado,
                'estado_badge' => $usuario->estado_badge,
                'created_at' => $usuario->created_at ? $usuario->created_at->format('d/m/Y H:i') : '-',
                'acciones' => $this->generateActions($usuario)
            ];
        })
    ]);
}

    private function generateActions($usuario)
    {
        $disabled = $usuario->id == auth()->id() ? 'disabled' : '';
        
        return '
            <button type="button" class="btn btn-sm btn-info btn-view" 
                    data-id="' . $usuario->id . '"
                    data-bs-toggle="modal" 
                    data-bs-target="#modalView">
                <i class="bi bi-eye"></i>
            </button>
            <button type="button" class="btn btn-sm btn-warning btn-edit" 
                    data-id="' . $usuario->id . '"
                    data-bs-toggle="modal" 
                    data-bs-target="#modalEdit">
                <i class="bi bi-pencil"></i>
            </button>
            <button type="button" class="btn btn-sm btn-danger btn-delete" 
                    data-id="' . $usuario->id . '"
                    data-nombre="' . htmlspecialchars($usuario->name) . '"
                    ' . $disabled . '>
                <i class="bi bi-trash"></i>
            </button>
            <button type="button" class="btn btn-sm btn-' . ($usuario->estado ? 'danger' : 'success') . ' btn-toggle-status" 
                    data-id="' . $usuario->id . '"
                    data-nombre="' . htmlspecialchars($usuario->name) . '"
                    data-estado="' . ($usuario->estado ? 'Activo' : 'Inactivo') . '"
                    data-nuevo-estado="' . ($usuario->estado ? 'desactivar' : 'activar') . '"
                    ' . ($usuario->id == auth()->id() ? 'disabled' : '') . '>
                <i class="bi bi-' . ($usuario->estado ? 'x-circle' : 'check-circle') . '"></i>
            </button>
        ';
    }

  public function getFormData()
{
    // Usar 'descripcion' en lugar de 'nombre' y quitar where('estado')
    $cajas = Caja::orderBy('descripcion', 'asc')->get();
    $almacenes = Almacen::orderBy('descripcion', 'asc')->get();
    $roles = Role::orderBy('nombre', 'asc')->get();
    
    return response()->json([
        'cajas' => $cajas,
        'almacenes' => $almacenes,
        'roles' => $roles
    ]);
}

    public function show($id)
    {
        $usuario = User::with(['caja', 'almacen', 'roles'])->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $usuario->id,
                'name' => $usuario->name,
                'email' => $usuario->email,
                'caja' => $usuario->caja ? $usuario->caja->nombre : 'No asignada',
                'almacen' => $usuario->almacen ? $usuario->almacen->descripcion : 'No asignado',
                'roles' => $usuario->roles->pluck('nombre')->implode(', '),
                'estado_badge' => $usuario->estado_badge,
                'created_at' => $usuario->created_at ? $usuario->created_at->format('d/m/Y H:i') : '-',
                'updated_at' => $usuario->updated_at ? $usuario->updated_at->format('d/m/Y H:i') : '-'
            ]
        ]);
    }

    public function edit($id)
    {
        $usuario = User::with(['caja', 'almacen', 'roles'])->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $usuario->id,
                'name' => $usuario->name,
                'email' => $usuario->email,
                'caja_id' => $usuario->caja_id,
                'almacen_id' => $usuario->almacen_id,
                'roles' => $usuario->roles->pluck('id'),
                'estado' => $usuario->estado
            ]
        ]);
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:6',
                'caja_id' => 'nullable|exists:cajas,id',
                'almacen_id' => 'nullable|exists:almacenes,id',
                'roles' => 'required|array|min:1',
                'roles.*' => 'exists:roles,id',
                'estado' => 'nullable|boolean'
            ], [
                'name.required' => 'El nombre es obligatorio.',
                'email.required' => 'El correo electrónico es obligatorio.',
                'email.unique' => 'Este correo ya está registrado.',
                'password.required' => 'La contraseña es obligatoria.',
                'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
                'roles.required' => 'Debe asignar al menos un rol.',
                'roles.min' => 'Debe asignar al menos un rol.'
            ]);

            DB::beginTransaction();

            $usuario = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'caja_id' => $request->caja_id,
                'almacen_id' => $request->almacen_id,
                'estado' => $request->has('estado')
            ]);

            $usuario->roles()->sync($request->roles);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => '✅ Usuario creado exitosamente',
                'data' => $usuario
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el usuario: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $usuario = User::findOrFail($id);

            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email,' . $id,
                'password' => 'nullable|string|min:6',
                'caja_id' => 'nullable|exists:cajas,id',
                'almacen_id' => 'nullable|exists:almacenes,id',
                'roles' => 'required|array|min:1',
                'roles.*' => 'exists:roles,id',
                'estado' => 'nullable|boolean'
            ], [
                'name.required' => 'El nombre es obligatorio.',
                'email.required' => 'El correo electrónico es obligatorio.',
                'email.unique' => 'Este correo ya está registrado.',
                'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
                'roles.required' => 'Debe asignar al menos un rol.',
                'roles.min' => 'Debe asignar al menos un rol.'
            ]);

            DB::beginTransaction();

            $data = [
                'name' => $request->name,
                'email' => $request->email,
                'caja_id' => $request->caja_id,
                'almacen_id' => $request->almacen_id,
                'estado' => $request->has('estado')
            ];

            if ($request->filled('password')) {
                $data['password'] = Hash::make($request->password);
            }

            $usuario->update($data);
            $usuario->roles()->sync($request->roles);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => '✅ Usuario actualizado exitosamente',
                'data' => $usuario
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el usuario: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $usuario = User::findOrFail($id);
            
            if ($usuario->id == auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No puedes eliminar tu propio usuario'
                ], 400);
            }
            
            $usuario->delete();

            return response()->json([
                'success' => true,
                'message' => '✅ Usuario eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el usuario: ' . $e->getMessage()
            ], 500);
        }
    }

    public function toggleStatus($id)
    {
        try {
            $usuario = User::findOrFail($id);
            
            if ($usuario->id == auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No puedes cambiar tu propio estado'
                ], 400);
            }
            
            $usuario->estado = !$usuario->estado;
            $usuario->save();

            $mensaje = $usuario->estado ? 'activado' : 'desactivado';
            
            return response()->json([
                'success' => true,
                'message' => "✅ Usuario {$mensaje} exitosamente",
                'estado' => $usuario->estado
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar el estado del usuario'
            ], 500);
        }
    }
}