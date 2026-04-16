<?php

namespace App\Http\Controllers\Terminal;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use App\Models\Cliente;
use App\Models\Serie;
use App\Models\Venta;
use App\Models\VentaDetalle;
use App\Models\VentaCuota;
use App\Models\ProductoAlmacen;
use App\Models\AperturaCaja;
use App\Models\Caja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TerminalController extends Controller
{
    public function index()
    {
        // Verificar si hay caja abierta
        $cajaAbierta = AperturaCaja::where('responsable_id', Auth::id())
                                   ->where('estado', 'ABIERTA')
                                   ->first();
        
        \Log::info('=== INDEX POS ===');
        \Log::info('Usuario ID: ' . Auth::id());
        \Log::info('Caja abierta encontrada:', ['caja' => $cajaAbierta]);
        
        if (!$cajaAbierta) {
            return redirect()->route('apertura-caja.index')
                ->with('error', 'Debe abrir una caja antes de usar el POS');
        }
        
        // Obtener productos con stock
        $productos = Producto::where('estado', 1)
                            ->orderBy('descripcion', 'asc')
                            ->get();
        
        // Obtener información de la empresa
        $empresa = \App\Models\Empresa::first();
        
        return view('terminal.index', compact('productos', 'cajaAbierta', 'empresa'));
    }

    public function search(Request $request)
    {
        $search = $request->get('search');
        
        $productos = Producto::where('estado', 1)
            ->where(function($query) use ($search) {
                $query->where('descripcion', 'LIKE', "%{$search}%")
                    ->orWhere('codigo_interno', 'LIKE', "%{$search}%")
                    ->orWhere('codigo_barras', 'LIKE', "%{$search}%");
            })
            ->orderBy('descripcion', 'asc')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $productos->map(function($producto) {
                return [
                    'id' => $producto->id,
                    'codigo_interno' => $producto->codigo_interno,
                    'descripcion' => $producto->descripcion,
                    'precio_venta' => $producto->precio_venta,
                    'stock_total' => $producto->stock_total,
                    'foto' => $producto->foto_url
                ];
            })
        ]);
    }

    public function getStock(Request $request)
    {
        $productoId = $request->get('producto_id');
        $almacenId = $request->get('almacen_id');
        
        $stock = ProductoAlmacen::where('producto_id', $productoId)
                                ->where('almacen_id', $almacenId)
                                ->first();
        
        return response()->json([
            'success' => true,
            'stock' => $stock ? $stock->stock : 0
        ]);
    }

    public function searchClientes(Request $request)
    {
        $search = $request->get('q');
        
        $clientes = Cliente::where(function($query) use ($search) {
                $query->where('numero_documento', 'LIKE', "%{$search}%")
                    ->orWhere('nombre_razon_social', 'LIKE', "%{$search}%");
            })
            ->limit(10)
            ->get();
        
        return response()->json([
            'success' => true,
            'clientes' => $clientes
        ]);
    }

    public function getSeries(Request $request)
    {
        $tipo = $request->get('tipo');
        
        \Log::info('=== GET SERIES ===');
        \Log::info('Tipo comprobante solicitado: ' . $tipo);
        
        $usuario = Auth::user();
        \Log::info('Usuario ID: ' . $usuario->id);
        \Log::info('Usuario caja_id asignado: ' . ($usuario->caja_id ?? 'NULL'));
        
        $cajaId = $usuario->caja_id;
        
        if (!$cajaId) {
            \Log::info('Usuario sin caja asignada, buscando caja abierta...');
            $cajaAbierta = AperturaCaja::where('responsable_id', $usuario->id)
                                       ->where('estado', 'ABIERTA')
                                       ->first();
            
            \Log::info('Caja abierta encontrada:', ['caja' => $cajaAbierta]);
            
            if ($cajaAbierta) {
                $cajaId = $cajaAbierta->id;
                \Log::info('Usando caja abierta ID: ' . $cajaId);
            }
        }
        
        if (!$cajaId) {
            \Log::info('Buscando primera caja disponible...');
            $caja = Caja::first();
            if ($caja) {
                $cajaId = $caja->id;
                \Log::info('Usando primera caja ID: ' . $cajaId);
            }
        }
        
        if (!$cajaId) {
            \Log::error('No se encontró ninguna caja para el usuario');
            return response()->json([
                'success' => false,
                'message' => 'No se encontró una caja asignada o abierta para este usuario'
            ]);
        }
        
        \Log::info('Buscando serie con:', [
            'tipo_comprobante' => $tipo,
            'caja_id' => $cajaId
        ]);
        
        $serie = Serie::where('tipo_comprobante', $tipo)
                     ->where('caja_id', $cajaId)
                     ->first();
        
        \Log::info('Resultado de búsqueda de serie:', ['serie' => $serie]);
        
        if (!$serie) {
            $todasSeries = Serie::all();
            \Log::info('Todas las series en la BD:', ['series' => $todasSeries->toArray()]);
            
            return response()->json([
                'success' => false,
                'message' => "No hay serie configurada para {$tipo} en la caja ID: {$cajaId}"
            ]);
        }
        
        $numero = $serie->correlativo + 1;
        \Log::info('Serie encontrada - Serie: ' . $serie->serie . ', Nuevo número: ' . $numero);
        
        return response()->json([
            'success' => true,
            'serie' => $serie->serie,
            'numero' => $numero,
            'documento' => $serie->serie . '-' . str_pad($numero, 8, '0', STR_PAD_LEFT)
        ]);
    }

    public function procesarPago(Request $request)
    {
        try {
            \Log::info('=== PROCESAR PAGO ===');
            \Log::info('Datos recibidos:', $request->all());
            
            DB::beginTransaction();

            $cajaAbierta = AperturaCaja::where('responsable_id', Auth::id())
                                       ->where('estado', 'ABIERTA')
                                       ->first();
            
            \Log::info('Caja abierta:', ['caja' => $cajaAbierta]);
            
            if (!$cajaAbierta) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay una caja abierta para este usuario'
                ], 422);
            }

            $productos = json_decode($request->productos_json, true);
            
            \Log::info('Productos decodificados:', ['productos' => $productos]);
            
            if (!$productos || empty($productos)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay productos en la venta'
                ], 422);
            }

            $request->validate([
                'tipo_comprobante' => 'required|in:BOLETA,FACTURA,NOTA',
                'cliente_id' => 'nullable|exists:clientes,id',
                'tipo_venta' => 'required|in:CONTADO,CREDITO',
                'forma_pago' => 'required|in:EFECTIVO,YAPE,TRANSFERENCIA,TARJETA',
                'total' => 'required|numeric|min:0',
                'pagado' => 'required|numeric|min:0',
                'detraccion' => 'nullable|boolean',
                'observaciones' => 'nullable'
            ]);

            foreach ($productos as $item) {
                $stock = ProductoAlmacen::where('producto_id', $item['id'])
                                        ->where('almacen_id', $item['almacen_id'])
                                        ->first();
                
                \Log::info('Validando stock producto ID: ' . $item['id'], [
                    'stock_encontrado' => $stock ? $stock->stock : 0,
                    'cantidad_solicitada' => $item['cantidad']
                ]);
                
                if (!$stock || $stock->stock < $item['cantidad']) {
                    $producto = Producto::find($item['id']);
                    return response()->json([
                        'success' => false,
                        'message' => "Stock insuficiente para: {$producto->descripcion}"
                    ], 422);
                }
            }

            \Log::info('Buscando serie para:', [
                'tipo_comprobante' => $request->tipo_comprobante,
                'caja_id' => $cajaAbierta->id
            ]);
            
            $serie = Serie::where('tipo_comprobante', $request->tipo_comprobante)
                         ->where('caja_id', $cajaAbierta->id)
                         ->first();
            
            \Log::info('Serie encontrada:', ['serie' => $serie]);
            
            if (!$serie) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay serie configurada para este tipo de comprobante'
                ], 422);
            }

            $numero = $serie->correlativo + 1;
            $subtotal = $request->total / 1.18;
            $igv = $request->total - $subtotal;
            
            \Log::info('Calculando totales:', [
                'total' => $request->total,
                'subtotal' => $subtotal,
                'igv' => $igv,
                'serie' => $serie->serie,
                'numero' => $numero
            ]);

            $venta = Venta::create([
                'tipo_comprobante' => $request->tipo_comprobante,
                'serie' => $serie->serie,
                'numero' => $numero,
                'fecha_emision' => now(),
                'cliente_id' => $request->cliente_id,
                'tipo_venta' => $request->tipo_venta,
                'forma_pago' => $request->forma_pago,
                'subtotal' => $subtotal,
                'igv' => $igv,
                'total' => $request->total,
                'pagado' => $request->pagado,
                'cambio' => $request->pagado - $request->total,
                'detraccion' => $request->has('detraccion'),
                'observaciones' => $request->observaciones,
                'caja_id' => $cajaAbierta->id,
                'usuario_id' => Auth::id(),
                'estado' => $request->tipo_venta == 'CREDITO' ? 'PENDIENTE' : 'COMPLETADA'
            ]);
            
            \Log::info('Venta creada ID: ' . $venta->id);

            $serie->correlativo = $numero;
            $serie->save();
            \Log::info('Serie actualizada, nuevo correlativo: ' . $numero);

            foreach ($productos as $item) {
                VentaDetalle::create([
                    'venta_id' => $venta->id,
                    'producto_id' => $item['id'],
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $item['precio'],
                    'total' => $item['cantidad'] * $item['precio'],
                    'almacen_id' => $item['almacen_id']
                ]);
                \Log::info('Detalle creado para producto ID: ' . $item['id']);

                $stock = ProductoAlmacen::where('producto_id', $item['id'])
                                        ->where('almacen_id', $item['almacen_id'])
                                        ->first();
                $stock->stock -= $item['cantidad'];
                $stock->save();
                \Log::info('Stock actualizado para producto ID: ' . $item['id'] . ', nuevo stock: ' . $stock->stock);
            }

            if ($request->tipo_venta == 'CREDITO') {
                $montoCredito = $request->total - ($request->pagado ?? 0);
                $numeroCuotas = $request->numero_cuotas ?? 1;
                $montoCuota = $montoCredito / $numeroCuotas;
                
                for ($i = 1; $i <= $numeroCuotas; $i++) {
                    VentaCuota::create([
                        'venta_id' => $venta->id,
                        'numero_cuota' => $i,
                        'fecha_vencimiento' => now()->addMonths($i),
                        'monto' => $montoCuota,
                        'estado' => 'PENDIENTE'
                    ]);
                }
                \Log::info('Cuotas generadas para crédito: ' . $numeroCuotas);
            }

            DB::commit();
            \Log::info('=== VENTA COMPLETADA EXITOSAMENTE ===');

            return response()->json([
                'success' => true,
                'message' => 'Venta procesada exitosamente',
                'data' => [
                    'venta_id' => $venta->id,
                    'documento' => $venta->documento,
                    'total' => $venta->total,
                    'pagado' => $venta->pagado,
                    'cambio' => $venta->cambio,
                    'tipo_venta' => $venta->tipo_venta,
                    'cuotas' => $venta->tipo_venta == 'CREDITO' ? $venta->cuotas : null
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('=== ERROR EN PROCESAR PAGO ===');
            \Log::error('Mensaje: ' . $e->getMessage());
            \Log::error('Línea: ' . $e->getLine());
            \Log::error('Archivo: ' . $e->getFile());
            \Log::error('Traza: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la venta: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function getVenta($id)
    {
        $venta = Venta::with(['cliente', 'detalles.producto', 'cuotas'])->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $venta
        ]);
    }
}