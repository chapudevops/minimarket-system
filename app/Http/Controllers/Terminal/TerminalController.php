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
                    'stock_total' => $producto->stock_total
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
    
    // Obtener la caja del usuario autenticado
    $usuario = Auth::user();
    
    // Primero verificar si el usuario tiene una caja asignada
    $cajaId = $usuario->caja_id;
    
    // Si no tiene caja asignada, buscar si tiene una caja abierta
    if (!$cajaId) {
        $cajaAbierta = AperturaCaja::where('responsable_id', $usuario->id)
                                   ->where('estado', 'ABIERTA')
                                   ->first();
        
        if ($cajaAbierta) {
            $cajaId = $cajaAbierta->id;
        }
    }
    
    // Si aún no hay caja, buscar la primera caja activa
    if (!$cajaId) {
        $caja = Caja::where('estado', 1)->first();
        if ($caja) {
            $cajaId = $caja->id;
        }
    }
    
    // Debug - ver qué caja se está usando
    \Log::info('Buscando serie:', [
        'tipo' => $tipo, 
        'caja_id' => $cajaId,
        'usuario_id' => $usuario->id,
        'usuario_caja_id' => $usuario->caja_id
    ]);
    
    if (!$cajaId) {
        return response()->json([
            'success' => false,
            'message' => 'No se encontró una caja asignada o abierta para este usuario'
        ]);
    }
    
    $serie = Serie::where('tipo_comprobante', $tipo)
                 ->where('caja_id', $cajaId)
                 ->first();
    
    // Debug - ver si encontró algo
    \Log::info('Serie encontrada:', ['serie' => $serie]);
    
    if (!$serie) {
        return response()->json([
            'success' => false,
            'message' => "No hay serie configurada para {$tipo} en la caja ID: {$cajaId}"
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

   public function procesarPago(Request $request)
{
    try {
        DB::beginTransaction();

        // Verificar caja abierta
        $cajaAbierta = AperturaCaja::where('responsable_id', Auth::id())
                                   ->where('estado', 'ABIERTA')
                                   ->first();
        
        if (!$cajaAbierta) {
            return response()->json([
                'success' => false,
                'message' => 'No hay una caja abierta para este usuario'
            ], 422);
        }

        // Decodificar productos desde JSON
        $productos = json_decode($request->productos_json, true);
        
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

        // Validar stock para cada producto
        foreach ($productos as $item) {
            $stock = ProductoAlmacen::where('producto_id', $item['id'])
                                    ->where('almacen_id', $item['almacen_id'])
                                    ->first();
            
            if (!$stock || $stock->stock < $item['cantidad']) {
                $producto = Producto::find($item['id']);
                return response()->json([
                    'success' => false,
                    'message' => "Stock insuficiente para: {$producto->descripcion}"
                ], 422);
            }
        }

        // Obtener serie y número
        $serie = Serie::where('tipo_comprobante', $request->tipo_comprobante)
                     ->where('caja_id', $cajaAbierta->id)
                     ->first();
        
        if (!$serie) {
            return response()->json([
                'success' => false,
                'message' => 'No hay serie configurada para este tipo de comprobante'
            ], 422);
        }

        $numero = $serie->correlativo + 1;
        $subtotal = $request->total / 1.18;
        $igv = $request->total - $subtotal;

        // Crear venta
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

        // Actualizar correlativo de la serie
        $serie->correlativo = $numero;
        $serie->save();

        // Crear detalles y actualizar stock
        foreach ($productos as $item) {
            VentaDetalle::create([
                'venta_id' => $venta->id,
                'producto_id' => $item['id'],
                'cantidad' => $item['cantidad'],
                'precio_unitario' => $item['precio'],
                'total' => $item['cantidad'] * $item['precio'],
                'almacen_id' => $item['almacen_id']
            ]);

            // Descontar stock
            $stock = ProductoAlmacen::where('producto_id', $item['id'])
                                    ->where('almacen_id', $item['almacen_id'])
                                    ->first();
            $stock->stock -= $item['cantidad'];
            $stock->save();
        }

        // Si es crédito, generar cuotas
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
        }

        DB::commit();

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