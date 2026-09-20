<?php

namespace App\Http\Controllers\Combo;

use App\Http\Controllers\Controller;
use App\Models\Combo;
use App\Models\ComboDetalle;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ComboController extends Controller
{
    public function index()
    {
        return view('combo.index');
    }

    public function getData(Request $request)
    {
        $combos = Combo::with('detalles.producto')->orderBy('nombre', 'asc')->get();

        $index = 1;

        return response()->json([
            'data' => $combos->map(function($combo) use (&$index) {
                return [
                    'correlativo' => $index++,
                    'id' => $combo->id,
                    'nombre' => $combo->nombre,
                    'descripcion' => $combo->descripcion ?? '-',
                    'foto' => '<img src="' . $combo->foto_url . '" alt="' . htmlspecialchars($combo->nombre) . '" width="45" height="45" class="rounded" style="object-fit:cover;">',
                    'precio_combo' => 'S/ ' . number_format($combo->precio_combo, 2),
                    'precio_regular' => 'S/ ' . number_format($combo->precio_regular, 2),
                    'ahorro' => 'S/ ' . number_format($combo->ahorro, 2),
                    'descuento' => $combo->descuento_porcentaje . '%',
                    'productos_count' => $combo->detalles->count(),
                    'estado_texto' => $combo->estado ? 'Activo' : 'Inactivo',
                    'created_at' => $combo->created_at ? $combo->created_at->format('d/m/Y H:i') : '-',
                    'acciones' => $this->generateActions($combo)
                ];
            })
        ]);
    }

    private function generateActions($combo)
    {
        return '
            <button type="button" class="btn btn-sm btn-info btn-view" 
                    data-id="' . $combo->id . '"
                    data-bs-toggle="modal" 
                    data-bs-target="#modalView">
                <i class="bi bi-eye"></i>
            </button>
            <a href="' . route('combos.edit', $combo->id) . '" class="btn btn-sm btn-warning">
                <i class="bi bi-pencil"></i>
            </a>
            <button type="button" class="btn btn-sm btn-danger btn-delete" 
                    data-id="' . $combo->id . '"
                    data-nombre="' . htmlspecialchars($combo->nombre) . '"
                    data-bs-toggle="modal" 
                    data-bs-target="#modalDelete">
                <i class="bi bi-trash"></i>
            </button>
        ';
    }

    public function create()
    {
        return view('combo.create');
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'nombre' => 'required|max:255',
                'descripcion' => 'nullable',
                'precio_combo' => 'required|numeric|min:0',
                'estado' => 'nullable',
                'foto' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'productos' => 'required',
            ], [
                'nombre.required' => 'El nombre del combo es obligatorio.',
                'precio_combo.required' => 'El precio del combo es obligatorio.',
                'precio_combo.numeric' => 'El precio del combo debe ser un número.',
                'productos.required' => 'Debe agregar al menos un producto al combo.',
                'foto.image' => 'El archivo debe ser una imagen.',
                'foto.mimes' => 'La imagen debe ser de tipo: jpeg, png, jpg, gif.',
                'foto.max' => 'La imagen no debe pesar más de 2MB.'
            ]);

            $productosData = json_decode($request->productos, true);

            if (empty($productosData)) {
                return redirect()->back()->withInput()->with('error', 'Debe agregar al menos un producto al combo.');
            }

            DB::beginTransaction();

            // Calcular precio regular (suma de precios individuales)
            $precioRegular = 0;
            foreach ($productosData as $item) {
                $producto = Producto::find($item['producto_id']);
                if ($producto) {
                    $precioRegular += $producto->precio_venta * $item['cantidad'];
                }
            }

            $data = [
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'precio_combo' => $request->precio_combo,
                'precio_regular' => $precioRegular,
                'estado' => $request->has('estado') ? true : false
            ];

            // Subir foto
            if ($request->hasFile('foto')) {
                $foto = $request->file('foto');
                $fotoName = time() . '_' . uniqid() . '.' . $foto->getClientOriginalExtension();
                $foto->storeAs('public/combos', $fotoName);
                $data['foto'] = $fotoName;
            }

            $combo = Combo::create($data);

            // Guardar detalles del combo
            foreach ($productosData as $item) {
                ComboDetalle::create([
                    'combo_id' => $combo->id,
                    'producto_id' => $item['producto_id'],
                    'cantidad' => $item['cantidad']
                ]);
            }

            DB::commit();

            return redirect()->route('combos.index')->with('success', 'Combo creado exitosamente');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', 'Error al crear el combo: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $combo = Combo::with('detalles.producto')->findOrFail($id);

        $productos = $combo->detalles->map(function($detalle) {
            return [
                'producto_id' => $detalle->producto_id,
                'descripcion' => $detalle->producto->descripcion,
                'codigo_interno' => $detalle->producto->codigo_interno,
                'precio_unitario' => 'S/ ' . number_format($detalle->producto->precio_venta, 2),
                'cantidad' => $detalle->cantidad,
                'subtotal' => 'S/ ' . number_format($detalle->producto->precio_venta * $detalle->cantidad, 2)
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $combo->id,
                'nombre' => $combo->nombre,
                'descripcion' => $combo->descripcion ?? '-',
                'foto_url' => $combo->foto_url,
                'precio_combo' => 'S/ ' . number_format($combo->precio_combo, 2),
                'precio_regular' => 'S/ ' . number_format($combo->precio_regular, 2),
                'ahorro' => 'S/ ' . number_format($combo->ahorro, 2),
                'descuento_porcentaje' => $combo->descuento_porcentaje . '%',
                'estado_texto' => $combo->estado ? 'Activo' : 'Inactivo',
                'productos' => $productos,
                'created_at' => $combo->created_at ? $combo->created_at->format('d/m/Y H:i') : '-',
                'updated_at' => $combo->updated_at ? $combo->updated_at->format('d/m/Y H:i') : '-'
            ]
        ]);
    }

    public function edit($id)
    {
        $combo = Combo::with('detalles.producto')->findOrFail($id);

        $productosDelCombo = $combo->detalles->map(function($detalle) {
            return [
                'producto_id' => $detalle->producto_id,
                'descripcion' => $detalle->producto->descripcion,
                'codigo_interno' => $detalle->producto->codigo_interno,
                'precio_venta' => (float) $detalle->producto->precio_venta,
                'cantidad' => $detalle->cantidad
            ];
        });

        return view('combo.edit', [
            'combo' => $combo,
            'productosDelCombo' => $productosDelCombo
        ]);
    }

    public function update(Request $request, $id)
    {
        try {
            $combo = Combo::findOrFail($id);

            $request->validate([
                'nombre' => 'required|max:255',
                'descripcion' => 'nullable',
                'precio_combo' => 'required|numeric|min:0',
                'estado' => 'nullable',
                'foto' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'productos' => 'required',
            ]);

            $productosData = json_decode($request->productos, true);

            if (empty($productosData)) {
                return redirect()->back()->withInput()->with('error', 'Debe agregar al menos un producto al combo.');
            }

            DB::beginTransaction();

            // Calcular precio regular
            $precioRegular = 0;
            foreach ($productosData as $item) {
                $producto = Producto::find($item['producto_id']);
                if ($producto) {
                    $precioRegular += $producto->precio_venta * $item['cantidad'];
                }
            }

            $data = [
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'precio_combo' => $request->precio_combo,
                'precio_regular' => $precioRegular,
                'estado' => $request->has('estado') ? true : false
            ];

            // Subir foto nueva
            if ($request->hasFile('foto')) {
                // Eliminar foto anterior si existe
                if ($combo->foto) {
                    Storage::delete('public/combos/' . $combo->foto);
                }
                $foto = $request->file('foto');
                $fotoName = time() . '_' . uniqid() . '.' . $foto->getClientOriginalExtension();
                $foto->storeAs('public/combos', $fotoName);
                $data['foto'] = $fotoName;
            }

            $combo->update($data);

            // Eliminar detalles anteriores y crear nuevos
            $combo->detalles()->delete();

            foreach ($productosData as $item) {
                ComboDetalle::create([
                    'combo_id' => $combo->id,
                    'producto_id' => $item['producto_id'],
                    'cantidad' => $item['cantidad']
                ]);
            }

            DB::commit();

            return redirect()->route('combos.index')->with('success', 'Combo actualizado exitosamente');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', 'Error al actualizar el combo: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $combo = Combo::findOrFail($id);

        // Eliminar foto si existe
        if ($combo->foto) {
            Storage::delete('public/combos/' . $combo->foto);
        }

        $combo->delete();

        return response()->json([
            'success' => true,
            'message' => 'Combo eliminado exitosamente'
        ]);
    }

    public function searchProductos(Request $request)
    {
        $search = $request->get('q', '');

        $productos = Producto::where('estado', 1)
            ->where(function($query) use ($search) {
                $query->where('descripcion', 'LIKE', "%{$search}%")
                      ->orWhere('codigo_interno', 'LIKE', "%{$search}%")
                      ->orWhere('codigo_barras', 'LIKE', "%{$search}%");
            })
            ->orderBy('descripcion', 'asc')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'productos' => $productos->map(function($p) {
                return [
                    'id' => $p->id,
                    'codigo_interno' => $p->codigo_interno,
                    'descripcion' => $p->descripcion,
                    'precio_venta' => (float) $p->precio_venta,
                    'unidad' => $p->unidad
                ];
            })
        ]);
    }

    // Combos para la Terminal POS
    public function getCombosTerminal(Request $request)
    {
        $almacenId = $request->get('almacen_id', 1);

        $combos = Combo::with('detalles.producto')
                       ->where('estado', 1)
                       ->orderBy('nombre', 'asc')
                       ->get();

        return response()->json([
            'success' => true,
            'data' => $combos->map(function($combo) use ($almacenId) {
                return [
                    'id' => $combo->id,
                    'nombre' => $combo->nombre,
                    'descripcion' => $combo->descripcion,
                    'foto_url' => $combo->foto_url,
                    'precio_combo' => (float) $combo->precio_combo,
                    'precio_regular' => (float) $combo->precio_regular,
                    'ahorro' => (float) $combo->ahorro,
                    'descuento_porcentaje' => $combo->descuento_porcentaje,
                    'stock' => $combo->getStockComboEnAlmacen($almacenId),
                    'productos' => $combo->detalles->map(function($d) {
                        return [
                            'producto_id' => $d->producto_id,
                            'descripcion' => $d->producto->descripcion,
                            'cantidad' => $d->cantidad
                        ];
                    }),
                    'es_combo' => true
                ];
            })
        ]);
    }
}
