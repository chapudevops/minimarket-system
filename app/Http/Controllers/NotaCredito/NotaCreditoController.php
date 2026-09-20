<?php

namespace App\Http\Controllers\NotaCredito;

use App\Estados\EstadoVenta;
use App\Http\Controllers\Controller;
use App\Estados\EstadoDocumento;
use App\Estados\EstadoSunat;
use App\Models\NotaCredito;
use App\Models\NotaCreditoDetalle;
use App\Models\Venta;
use App\Models\Serie;
use App\Models\AperturaCaja;
use App\Ventas\RegistroDevolucion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class NotaCreditoController extends Controller
{
    public function index()
    {
        return view('nota-credito.index');
    }

    public function getData(Request $request)
    {
        $notas = NotaCredito::with(['cliente', 'usuario'])
                            ->orderBy('id', 'desc')
                            ->get();
        
        $index = 1;
        
        return response()->json([
            'data' => $notas->map(function($nota) use (&$index) {
                return [
                    'correlativo' => $index++,
                    'id' => $nota->id,
                    'documento' => $nota->documento,
                    'fecha_emision' => $nota->fecha_emision ? $nota->fecha_emision->format('d/m/Y H:i') : '-', // CORREGIDO: fecha_emision
                    'ruc_dni' => $nota->cliente->numero_documento ?? '00000000',
                    'cliente' => $nota->cliente->nombre_razon_social ?? 'CLIENTES VARIOS',
                    'total' => 'S/ ' . number_format($nota->total, 2),
                    // Estaban escritos a mano como "Pendiente": una nota ya
                    // aceptada por SUNAT seguia mostrandose como pendiente.
                    'xml' => $nota->ruta_xml
                        ? '<span class="badge bg-success">Generado</span>'
                        : '<span class="badge bg-secondary">Pendiente</span>',
                    'cdr' => $nota->ruta_cdr
                        ? '<span class="badge bg-success">Recibido</span>'
                        : '<span class="badge bg-secondary">Pendiente</span>',
                    'sunat' => EstadoSunat::badge($nota->estado_sunat),
                    'tipo_comprobante' => $nota->tipo_comprobante,
                    'estado' => $nota->estado,
                    'estado_badge' => $nota->estado_badge,
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
        $ventas = Venta::where('estado', EstadoVenta::APROBADA)
                       ->orderBy('id', 'desc')
                       ->get();
        
        return view('nota-credito.create', compact('ventas'));
    }

    public function getVenta($id)
{
    $venta = Venta::with(['cliente', 'detalles.producto'])->findOrFail($id);
    
    $detalles = [];
    foreach ($venta->detalles as $detalle) {
        $detalles[] = [
            'producto_id' => $detalle->producto_id,
            'producto_descripcion' => $detalle->producto->descripcion ?? 'Producto',
            'codigo_interno' => $detalle->producto->codigo_interno ?? '-',
            'cantidad' => $detalle->cantidad,
            'precio_unitario' => $detalle->precio_unitario,
            'almacen_id' => $detalle->almacen_id ?? 1
        ];
    }
    
    return response()->json([
        'success' => true,
        'data' => [
            'venta' => [
                'id' => $venta->id,
                'documento' => $venta->documento,
                'cliente_id' => $venta->cliente_id,
                'cliente_nombre' => $venta->cliente->nombre_razon_social ?? 'CLIENTES VARIOS',
                'total' => $venta->total,
                'fecha_emision' => $venta->fecha_emision->format('Y-m-d')
            ],
            'detalles' => $detalles
        ]
    ]);
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
        
        $serie = Serie::where('tipo_comprobante', 'NOTA_CREDITO')
                      ->where('caja_id', $cajaAbierta->caja_id)
                      ->first();
        
        if (!$serie) {
            return response()->json([
                'success' => false,
                'message' => 'No hay serie configurada para Notas de Crédito'
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
                'venta_id' => 'required|exists:ventas,id',
                'cliente_id' => 'required|exists:clientes,id',
                'motivo' => 'required|string',
                'tipo_nota' => 'required|in:ANULACION,DESCUENTO,DEVOLUCION,OTRO',
                'detalles' => 'required|array|min:1',
                'detalles.*.producto_id' => 'required|exists:productos,id',
                'detalles.*.cantidad' => 'required|integer|min:1',
                'detalles.*.precio_unitario' => 'required|numeric|min:0',
                'detalles.*.almacen_id' => 'required|exists:almacenes,id'
            ]);

            // Obtener serie
            $serie = Serie::where('tipo_comprobante', 'NOTA_CREDITO')
                          ->where('caja_id', $cajaAbierta->caja_id)
                          ->first();
            
            if (!$serie) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay serie configurada para Notas de Crédito'
                ], 422);
            }

            // Reglas de la devolucion ANTES de crear nada: que los productos
            // esten en la venta, que no se devuelva mas de lo vendido y que
            // cuenten las notas anteriores.
            $ventaOriginal = \App\Models\Venta::with('detalles')->findOrFail($request->venta_id);
            $devolucion = new RegistroDevolucion();
            $devolucion->validar($ventaOriginal, $request->detalles);

            $numero = $serie->correlativo + 1;
            
            // Calcular totales
            //
            // agregarIgv es correcto AQUI: el detalle de una nota guarda el
            // precio NETO y tanto esta cabecera como ConstructorComprobante le
            // suman el IGV encima. Desagregarlo hacia que SUNAT rechazara la
            // nota con el codigo 3280 (ver NotasElectronicasTest).
            //
            // Lo que si esta mal es el precio que llega: la UI lo copia del
            // detalle de la venta, donde YA incluye IGV, y aqui se guarda como
            // si fuera neto. Eso infla la nota un 18%. Se documenta en vez de
            // corregirlo a ciegas: tocar el precio unitario cambia el XML que
            // ya esta validado contra SUNAT.
            $subtotal = 0;
            foreach ($request->detalles as $item) {
                $subtotal += $item['cantidad'] * $item['precio_unitario'];
            }
            $importes = \App\Sunat\Monto::agregarIgv($subtotal);
            $subtotal = $importes['gravado'];
            $igv = $importes['igv'];
            $total = $importes['total'];

            // Crear nota de crédito
            $nota = NotaCredito::create([
                'tipo_comprobante' => 'NOTA_CREDITO',
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
                'caja_id' => $cajaAbierta->caja_id,
                'usuario_id' => Auth::id(),
                'estado' => EstadoDocumento::REGISTRADA,
                'estado_sunat' => EstadoSunat::NO_ENVIADO,
            ]);

            // Actualizar correlativo
            $serie->correlativo = $numero;
            $serie->save();

            // Crear detalles y devolver stock
            foreach ($request->detalles as $item) {
                NotaCreditoDetalle::create([
                    'nota_credito_id' => $nota->id,
                    'producto_id' => $item['producto_id'],
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $item['precio_unitario'],
                    'total' => $item['cantidad'] * $item['precio_unitario'],
                    'almacen_id' => $item['almacen_id']
                ]);

            }

            // El stock vuelve una sola vez, por el servicio, y despues se
            // recalcula el estado de devolucion de la venta desde las notas
            // vigentes (no se incrementa a mano: asi sigue siendo correcto si
            // mas adelante se anula una nota).
            $devolucion->restaurarStock($request->detalles);
            $devolucion->recalcular($ventaOriginal->refresh());

            \App\Models\Auditoria::registrar(
                'NOTA_CREDITO_CREADA',
                'NotaCredito',
                $nota->id,
                sprintf(
                    'Emitió la nota de crédito %s-%08d por S/ %s sobre la venta %s (%s)',
                    $nota->serie, $nota->numero, number_format($total, 2),
                    $ventaOriginal->documento, $request->tipo_nota
                )
            );

            DB::commit();

            // Igual que en el terminal: despues del commit y sin dejar que un
            // fallo al encolar tumbe la nota ya emitida.
            try {
                \App\Jobs\EnviarComprobanteASunat::dispatch($nota->id, class_basename($nota));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('No se pudo encolar el envío a SUNAT', [
                    'nota_id' => $nota->id, 'error' => $e->getMessage(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Nota de Crédito creada exitosamente',
                'data' => $nota
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Devolver de mas es un error del usuario, no del servidor.
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'No se pudo emitir la nota de crédito',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la nota de crédito: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $nota = NotaCredito::with(['cliente', 'usuario', 'caja', 'detalles.producto', 'venta'])
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
                        'producto' => $detalle->producto->descripcion ?? '-',
                        'codigo' => $detalle->producto->codigo_interno ?? '-',
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
        $nota = NotaCredito::with(['cliente', 'detalles.producto'])
                           ->findOrFail($id);
        
        $empresa = \App\Models\Empresa::first();
        
        $pdf = Pdf::loadView('nota-credito.pdf', compact('nota', 'empresa'));
        $pdf->setPaper('a4', 'portrait');
        
        return $pdf->download('nota_credito_' . $nota->documento . '.pdf');
    }
}