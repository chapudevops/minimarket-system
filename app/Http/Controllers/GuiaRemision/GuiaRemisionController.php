<?php

namespace App\Http\Controllers\GuiaRemision;

use App\Http\Controllers\Controller;
use App\Models\GuiaRemision;
use App\Models\GuiaRemisionDetalle;
use App\Models\Producto;
use App\Models\Cliente;
use App\Models\Conductor;
use App\Models\Vehiculo;
use App\Models\Serie;
use App\Models\AperturaCaja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class GuiaRemisionController extends Controller
{
    public function index()
    {
        return view('guia-remision.index');
    }

    public function getData(Request $request)
    {
        $guias = GuiaRemision::with(['cliente'])
                             ->orderBy('id', 'desc')
                             ->get();
        
        $index = 1;
        
        return response()->json([
            'data' => $guias->map(function($guia) use (&$index) {
                return [
                    'correlativo' => $index++,
                    'id' => $guia->id,
                    'documento' => $guia->documento,
                    'fecha_emision' => $guia->fecha_emision ? $guia->fecha_emision->format('d/m/Y H:i') : '-',
                    'cliente' => $guia->cliente->nombre_razon_social ?? 'CLIENTES VARIOS',
                    'motivo' => $guia->motivo_traslado_texto,
                    'estado_sunat' => $guia->estado_sunat_badge,
                    'xml' => $guia->xml ? '<span class="badge bg-success">Descargar</span>' : '<span class="badge bg-secondary">Pendiente</span>',
                    'acciones' => $this->generateActions($guia)
                ];
            })
        ]);
    }

    private function generateActions($guia)
    {
        return '
            <button type="button" class="btn btn-sm btn-info btn-view" 
                    data-id="' . $guia->id . '"
                    data-bs-toggle="modal" 
                    data-bs-target="#modalView">
                <i class="bi bi-eye"></i>
            </button>
            <button type="button" class="btn btn-sm btn-danger btn-pdf" 
                    data-id="' . $guia->id . '">
                <i class="bi bi-file-pdf"></i>
            </button>
        ';
    }

    public function create()
    {
        $clientes = Cliente::orderBy('nombre_razon_social', 'asc')->get();
        $conductores = Conductor::orderBy('nombre', 'asc')->get();
        $vehiculos = Vehiculo::orderBy('placa', 'asc')->get();
        $productos = Producto::where('estado', 1)->orderBy('descripcion', 'asc')->get();
        
        return view('guia-remision.create', compact('clientes', 'conductores', 'vehiculos', 'productos'));
    }

    public function getSerie(Request $request)
    {
        $cajaAbierta = AperturaCaja::where('responsable_id', Auth::id())
                                   ->where('estado', 'ABIERTA')
                                   ->first();
        
        if (!$cajaAbierta) {
            return response()->json([
                'success' => false,
                'message' => 'No hay una caja abierta'
            ]);
        }
        
        $serie = Serie::where('tipo_comprobante', 'GUIA_REMISION')
                      ->where('caja_id', $cajaAbierta->id)
                      ->first();
        
        if (!$serie) {
            return response()->json([
                'success' => false,
                'message' => 'No hay serie configurada para Guías de Remisión'
            ]);
        }
        
        $numero = $serie->correlativo + 1;
        
        return response()->json([
            'success' => true,
            'serie' => $serie->serie,
            'numero' => $numero,
            'documento' => $serie->serie . '-' . str_pad($numero, 8, '0', STR_PAD_LEFT)
        ]);
    }

    public function searchProductos(Request $request)
    {
        $search = $request->get('q');
        
        $productos = Producto::where('estado', 1)
            ->where(function($query) use ($search) {
                $query->where('descripcion', 'LIKE', "%{$search}%")
                    ->orWhere('codigo_interno', 'LIKE', "%{$search}%")
                    ->orWhere('codigo_barras', 'LIKE', "%{$search}%");
            })
            ->limit(10)
            ->get();
        
        return response()->json([
            'success' => true,
            'productos' => $productos->map(function($producto) {
                return [
                    'id' => $producto->id,
                    'codigo_interno' => $producto->codigo_interno,
                    'descripcion' => $producto->descripcion,
                    'unidad' => $producto->unidad
                ];
            })
        ]);
    }

    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            $cajaAbierta = AperturaCaja::where('responsable_id', Auth::id())
                                       ->where('estado', 'ABIERTA')
                                       ->first();
            
            if (!$cajaAbierta) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay una caja abierta'
                ], 422);
            }

            $request->validate([
                'fecha_traslado' => 'required|date',
                'motivo_traslado' => 'required|in:01,02,03,04,05,06',
                'cliente_id' => 'required|exists:clientes,id',
                'peso_bruto_total' => 'required|numeric|min:0',
                'modalidad_traslado' => 'required|in:01,02',
                'ubigeo_partida' => 'required|size:6',
                'direccion_partida' => 'required',
                'ubigeo_llegada' => 'required|size:6',
                'direccion_llegada' => 'required',
                'productos' => 'required|array|min:1',
                'productos.*.id' => 'required|exists:productos,id',
                'productos.*.cantidad' => 'required|integer|min:1'
            ]);

            // Obtener serie
            $serie = Serie::where('tipo_comprobante', 'GUIA_REMISION')
                          ->where('caja_id', $cajaAbierta->id)
                          ->first();
            
            if (!$serie) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay serie configurada para Guías de Remisión'
                ], 422);
            }

            $numero = $serie->correlativo + 1;

            // Crear guía de remisión
            $guia = GuiaRemision::create([
                'serie' => $serie->serie,
                'numero' => $numero,
                'fecha_emision' => now(),
                'fecha_traslado' => $request->fecha_traslado,
                'motivo_traslado' => $request->motivo_traslado,
                'cliente_id' => $request->cliente_id,
                'peso_bruto_total' => $request->peso_bruto_total,
                'unidad_peso' => 'KGM',
                'modalidad_traslado' => $request->modalidad_traslado,
                'ubigeo_partida' => $request->ubigeo_partida,
                'direccion_partida' => $request->direccion_partida,
                'ubigeo_llegada' => $request->ubigeo_llegada,
                'direccion_llegada' => $request->direccion_llegada,
                'conductor_id' => $request->conductor_id,
                'vehiculo_id' => $request->vehiculo_id,
                'observaciones' => $request->observaciones,
                'estado_sunat' => 'PENDIENTE',
                'caja_id' => $cajaAbierta->id,
                'usuario_id' => Auth::id()
            ]);

            // Actualizar correlativo
            $serie->correlativo = $numero;
            $serie->save();

            // Crear detalles
            foreach ($request->productos as $item) {
                GuiaRemisionDetalle::create([
                    'guia_remision_id' => $guia->id,
                    'producto_id' => $item['id'],
                    'cantidad' => $item['cantidad'],
                    'unidad_medida' => 'NIU'
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => '✅ Guía de Remisión creada exitosamente',
                'data' => $guia
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la guía de remisión: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $guia = GuiaRemision::with(['cliente', 'conductor', 'vehiculo', 'usuario', 'detalles.producto'])
                            ->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $guia->id,
                'documento' => $guia->documento,
                'fecha_emision' => $guia->fecha_emision->format('d/m/Y H:i:s'),
                'fecha_traslado' => $guia->fecha_traslado->format('d/m/Y'),
                'cliente' => [
                    'nombre' => $guia->cliente->nombre_razon_social ?? 'CLIENTES VARIOS',
                    'documento' => $guia->cliente->numero_documento ?? '00000000',
                    'direccion' => $guia->cliente->direccion ?? '-'
                ],
                'motivo_traslado' => $guia->motivo_traslado_texto,
                'peso_bruto_total' => number_format($guia->peso_bruto_total, 3),
                'modalidad_traslado' => $guia->modalidad_traslado_texto,
                'ubigeo_partida' => $guia->ubigeo_partida,
                'direccion_partida' => $guia->direccion_partida,
                'ubigeo_llegada' => $guia->ubigeo_llegada,
                'direccion_llegada' => $guia->direccion_llegada,
                'conductor' => $guia->conductor ? $guia->conductor->nombre : 'No asignado',
                'vehiculo' => $guia->vehiculo ? $guia->vehiculo->placa . ' - ' . $guia->vehiculo->marca . ' ' . $guia->vehiculo->modelo : 'No asignado',
                'observaciones' => $guia->observaciones ?? '-',
                'estado_sunat_badge' => $guia->estado_sunat_badge,
                'detalles' => $guia->detalles->map(function($detalle) {
                    return [
                        'producto' => $detalle->producto->descripcion ?? '-',
                        'codigo' => $detalle->producto->codigo_interno ?? '-',
                        'cantidad' => $detalle->cantidad,
                        'unidad' => $detalle->producto->unidad ?? 'NIU'
                    ];
                }),
                'created_at' => $guia->created_at->format('d/m/Y H:i:s')
            ]
        ]);
    }

    public function generarPdf($id)
    {
        $guia = GuiaRemision::with(['cliente', 'conductor', 'vehiculo', 'detalles.producto'])
                            ->findOrFail($id);
        
        $empresa = \App\Models\Empresa::first();
        
        $pdf = Pdf::loadView('guia-remision.pdf', compact('guia', 'empresa'));
        $pdf->setPaper('a4', 'portrait');
        
        return $pdf->download('guia_remision_' . $guia->documento . '.pdf');
    }

    // Conductores
    public function getConductores()
    {
        $conductores = Conductor::orderBy('nombre', 'asc')->get();
        return response()->json(['success' => true, 'conductores' => $conductores]);
    }

    public function storeConductor(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
            'licencia' => 'required|string|max:20',
            'documento' => 'required|string|max:20',
            'telefono' => 'nullable|string|max:20'
        ]);

        $conductor = Conductor::create($request->all());
        return response()->json(['success' => true, 'conductor' => $conductor]);
    }

    // Vehículos
    public function getVehiculos()
    {
        $vehiculos = Vehiculo::orderBy('placa', 'asc')->get();
        return response()->json(['success' => true, 'vehiculos' => $vehiculos]);
    }

    public function storeVehiculo(Request $request)
    {
        $request->validate([
            'placa' => 'required|string|max:10|unique:vehiculos',
            'marca' => 'required|string|max:50',
            'modelo' => 'required|string|max:50',
            'color' => 'nullable|string|max:30'
        ]);

        $vehiculo = Vehiculo::create($request->all());
        return response()->json(['success' => true, 'vehiculo' => $vehiculo]);
    }

    // Ubigeos (simulación - puedes conectar con API de RENIEC)
    public function searchUbigeo(Request $request)
    {
        $search = $request->get('q');
        // Aquí deberías conectar con una API de ubigeos
        // Por ahora devolvemos datos de ejemplo
        $ubigeos = [
            ['codigo' => '110101', 'nombre' => 'ICA - ICA - ICA'],
            ['codigo' => '150101', 'nombre' => 'LIMA - LIMA - LIMA'],
            ['codigo' => '040101', 'nombre' => 'AREQUIPA - AREQUIPA - AREQUIPA'],
        ];
        
        $filtered = array_filter($ubigeos, function($ubigeo) use ($search) {
            return strpos($ubigeo['codigo'], $search) !== false || 
                   strpos(strtolower($ubigeo['nombre']), strtolower($search)) !== false;
        });
        
        return response()->json(['success' => true, 'ubigeos' => array_values($filtered)]);
    }
}