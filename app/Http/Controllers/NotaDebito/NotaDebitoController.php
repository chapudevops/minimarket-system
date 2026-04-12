<?php

namespace App\Http\Controllers\NotaDebito;

use App\Http\Controllers\Controller;
use App\Models\NotaDebito;
use App\Models\NotaDebitoDetalle;
use App\Models\Venta;
use App\Models\Serie;
use App\Models\AperturaCaja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class NotaDebitoController extends Controller
{
    public function index()
    {
        return view('nota-debito.index');
    }

    public function getData(Request $request)
    {
        $notas = NotaDebito::with(['cliente'])
                           ->orderBy('id', 'desc')
                           ->get();
        
        $index = 1;
        
        return response()->json([
            'data' => $notas->map(function($nota) use (&$index) {
                return [
                    'correlativo' => $index++,
                    'id' => $nota->id,
                    'documento' => $nota->documento,
                    'fecha_emision' => $nota->fecha_emision ? $nota->fecha_emision->format('d/m/Y H:i') : '-',
                    'cliente' => $nota->cliente->nombre_razon_social ?? 'CLIENTES VARIOS',
                    'estado_sunat' => '<span class="badge bg-warning">Pendiente</span>',
                    'xml' => '<span class="badge bg-secondary">Pendiente</span>',
                    'cdr' => '<span class="badge bg-secondary">Pendiente</span>',
                    'acciones' => $this->generateActions($nota)
                ];
            })
        ]);
    }

    private function generateActions($nota)
    {
        return '
            <button type="button" class="btn btn-sm btn-info btn-view" 
                    data-id="' . $nota->id . '"
                    data-bs-toggle="modal" 
                    data-bs-target="#modalView">
                <i class="bi bi-eye"></i>
            </button>
            <button type="button" class="btn btn-sm btn-danger btn-pdf" 
                    data-id="' . $nota->id . '">
                <i class="bi bi-file-pdf"></i>
            </button>
        ';
    }

    public function create()
    {
        $ventas = Venta::where('estado', 'COMPLETADA')
                       ->orderBy('id', 'desc')
                       ->get();
        
        $clientes = \App\Models\Cliente::orderBy('nombre_razon_social', 'asc')->get();
        
        return view('nota-debito.create', compact('ventas', 'clientes'));
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
        
        $serie = Serie::where('tipo_comprobante', 'NOTA_DEBITO')
                      ->where('caja_id', $cajaAbierta->id)
                      ->first();
        
        if (!$serie) {
            return response()->json([
                'success' => false,
                'message' => 'No hay serie configurada para Notas de Débito'
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

    public function getVenta($id)
    {
        $venta = Venta::with(['cliente'])
                      ->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => [
                'venta' => $venta,
                'cliente' => $venta->cliente
            ]
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
                'cliente_id' => 'required|exists:clientes,id',
                'motivo' => 'required|string',
                'tipo_nota' => 'required|in:INTERESES,GASTOS,OTRO',
                'detalles' => 'required|array|min:1',
                'detalles.*.concepto' => 'required|string',
                'detalles.*.cantidad' => 'required|integer|min:1',
                'detalles.*.precio_unitario' => 'required|numeric|min:0'
            ]);

            // Obtener serie
            $serie = Serie::where('tipo_comprobante', 'NOTA_DEBITO')
                          ->where('caja_id', $cajaAbierta->id)
                          ->first();
            
            if (!$serie) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay serie configurada para Notas de Débito'
                ], 422);
            }

            $numero = $serie->correlativo + 1;
            
            // Calcular totales
            $subtotal = 0;
            foreach ($request->detalles as $item) {
                $subtotal += $item['cantidad'] * $item['precio_unitario'];
            }
            $igv = $subtotal * 0.18;
            $total = $subtotal + $igv;

            // Crear nota de débito
            $nota = NotaDebito::create([
                'tipo_comprobante' => 'NOTA_DEBITO',
                'serie' => $serie->serie,
                'numero' => $numero,
                'fecha_emision' => now(),
                'cliente_id' => $request->cliente_id,
                'venta_id' => $request->venta_id,
                'motivo' => $request->motivo,
                'tipo_nota' => $request->tipo_nota,
                'subtotal' => $subtotal,
                'igv' => $igv,
                'total' => $total,
                'detraccion' => $request->has('detraccion'),
                'observaciones' => $request->observaciones,
                'caja_id' => $cajaAbierta->id,
                'usuario_id' => Auth::id(),
                'estado' => 'REGISTRADA'
            ]);

            // Actualizar correlativo
            $serie->correlativo = $numero;
            $serie->save();

            // Crear detalles
            foreach ($request->detalles as $item) {
                NotaDebitoDetalle::create([
                    'nota_debito_id' => $nota->id,
                    'concepto' => $item['concepto'],
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $item['precio_unitario'],
                    'total' => $item['cantidad'] * $item['precio_unitario']
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => '✅ Nota de Débito creada exitosamente',
                'data' => $nota
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la nota de débito: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $nota = NotaDebito::with(['cliente', 'usuario', 'caja', 'detalles', 'venta'])
                          ->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $nota->id,
                'documento' => $nota->documento,
                'fecha_emision' => $nota->fecha_emision->format('d/m/Y H:i:s'),
                'cliente' => [
                    'nombre' => $nota->cliente->nombre_razon_social ?? 'CLIENTES VARIOS',
                    'documento' => $nota->cliente->numero_documento ?? '00000000',
                    'direccion' => $nota->cliente->direccion ?? '-'
                ],
                'venta_original' => $nota->venta ? $nota->venta->documento : '-',
                'motivo' => $nota->motivo,
                'tipo_nota' => $nota->tipo_nota_texto,
                'subtotal' => number_format($nota->subtotal, 2),
                'igv' => number_format($nota->igv, 2),
                'total' => number_format($nota->total, 2),
                'detraccion' => $nota->detraccion ? 'Sí' : 'No',
                'observaciones' => $nota->observaciones ?? '-',
                'caja' => $nota->caja->descripcion ?? '-',
                'usuario' => $nota->usuario->name ?? '-',
                'estado_badge' => $nota->estado_badge,
                'detalles' => $nota->detalles->map(function($detalle) {
                    return [
                        'concepto' => $detalle->concepto,
                        'cantidad' => $detalle->cantidad,
                        'precio_unitario' => number_format($detalle->precio_unitario, 2),
                        'total' => number_format($detalle->total, 2)
                    ];
                }),
                'created_at' => $nota->created_at->format('d/m/Y H:i:s')
            ]
        ]);
    }

    public function generarPdf($id)
    {
        $nota = NotaDebito::with(['cliente', 'detalles'])
                          ->findOrFail($id);
        
        $empresa = \App\Models\Empresa::first();
        
        $pdf = Pdf::loadView('nota-debito.pdf', compact('nota', 'empresa'));
        $pdf->setPaper('a4', 'portrait');
        
        return $pdf->download('nota_debito_' . $nota->documento . '.pdf');
    }
}