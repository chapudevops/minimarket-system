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
use App\Models\Almacen;
use App\Models\Caja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class TerminalController extends Controller
{
    public function index()
    {
        $cajaAbierta = AperturaCaja::where('responsable_id', Auth::id())
                                ->where('estado', 'ABIERTA')
                                ->first();
        
        if (!$cajaAbierta) {
            return redirect()->route('apertura-caja.index')
                ->with('error', 'Debe abrir una caja antes de usar el POS');
        }
        
        $usuario = Auth::user();
        $almacenId = $usuario->almacen_id;
        
        if (!$almacenId) {
            $primerAlmacen = Almacen::first();
            $almacenId = $primerAlmacen ? $primerAlmacen->id : null;
        }
        
        // SOLO TRAER LOS PRIMEROS 20 PRODUCTOS
        $productos = Producto::where('estado', 1)
                            ->orderBy('descripcion', 'asc')
                            ->paginate(20);
        
        foreach ($productos as $producto) {
            $stock = ProductoAlmacen::where('producto_id', $producto->id)
                                    ->where('almacen_id', $almacenId)
                                    ->first();
            $producto->stock_en_almacen = $stock ? $stock->stock : 0;
            $producto->stock_total = $producto->stock_en_almacen;
        }
        
        $empresa = \App\Models\Empresa::first();
        
        return view('terminal.index', compact('productos', 'cajaAbierta', 'empresa', 'almacenId'));
    }

    // NUEVO MÉTODO PARA PAGINACIÓN INFINITA
    public function getProductos(Request $request)
    {
        $search = $request->get('search', '');
        $page = $request->get('page', 1);
        $almacenId = $request->get('almacen_id') ?? Auth::user()->almacen_id;
        
        if (!$almacenId) {
            $primerAlmacen = Almacen::first();
            $almacenId = $primerAlmacen ? $primerAlmacen->id : null;
        }
        
        $query = Producto::where('estado', 1);
        
        if ($search) {
            $query->where(function($q) use ($search) {
                // El lector de codigo de barras manda el codigo completo: se
                // busca por igualdad, que si puede usar indice. El comodin
                // inicial queda solo para la descripcion, donde hace falta.
                $q->where('codigo_barras', $search)
                    ->orWhere('codigo_interno', $search)
                    ->orWhere('descripcion', 'LIKE', "%{$search}%")
                    ->orWhere('codigo_interno', 'LIKE', "{$search}%");
            });
        }
        
        $query->orderBy('descripcion', 'asc');
        $productos = $query->paginate(20, ['*'], 'page', $page);
        
        foreach ($productos as $producto) {
            $stock = ProductoAlmacen::where('producto_id', $producto->id)
                                    ->where('almacen_id', $almacenId)
                                    ->first();
            $producto->stock_en_almacen = $stock ? $stock->stock : 0;
        }
        
        return response()->json([
            'success' => true,
            'data' => $productos->map(function($producto) {
                return [
                    'id' => $producto->id,
                    'codigo_interno' => $producto->codigo_interno,
                    'descripcion' => $producto->descripcion,
                    'precio_venta' => (float)$producto->precio_venta,
                    'stock' => $producto->stock_en_almacen,
                    'foto_url' => $producto->foto_url ?? asset('build/images/default-product.png')
                ];
            }),
            'pagination' => [
                'current_page' => $productos->currentPage(),
                'last_page' => $productos->lastPage(),
                'total' => $productos->total()
            ]
        ]);
    }

    public function search(Request $request)
    {
        $search = $request->get('search');
        $page = $request->get('page', 1);
        $almacenId = $request->get('almacen_id') ?? Auth::user()->almacen_id;
        
        if (!$almacenId) {
            $primerAlmacen = Almacen::first();
            $almacenId = $primerAlmacen ? $primerAlmacen->id : null;
        }
        
        $query = Producto::where('estado', 1)
            ->where(function($query) use ($search) {
                $query->where('codigo_barras', $search)
                    ->orWhere('codigo_interno', $search)
                    ->orWhere('descripcion', 'LIKE', "%{$search}%")
                    ->orWhere('codigo_interno', 'LIKE', "{$search}%");
            });
        
        $productos = $query->orderBy('descripcion', 'asc')->paginate(20, ['*'], 'page', $page);
        
        foreach ($productos as $producto) {
            $stock = ProductoAlmacen::where('producto_id', $producto->id)
                                    ->where('almacen_id', $almacenId)
                                    ->first();
            $producto->stock_en_almacen = $stock ? $stock->stock : 0;
        }
        
        return response()->json([
            'success' => true,
            'data' => $productos->map(function($producto) {
                return [
                    'id' => $producto->id,
                    'codigo_interno' => $producto->codigo_interno,
                    'descripcion' => $producto->descripcion,
                    'precio_venta' => (float)$producto->precio_venta,
                    'stock' => $producto->stock_en_almacen,
                    'foto_url' => $producto->foto_url ?? asset('build/images/default-product.png')
                ];
            }),
            'pagination' => [
                'current_page' => $productos->currentPage(),
                'last_page' => $productos->lastPage(),
                'total' => $productos->total()
            ]
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
        
        $usuario = Auth::user();
        
        $cajaId = $usuario->caja_id;
        
        if (!$cajaId) {
            $cajaAbierta = AperturaCaja::where('responsable_id', $usuario->id)
                                       ->where('estado', 'ABIERTA')
                                       ->first();
            
            if ($cajaAbierta) {
                $cajaId = $cajaAbierta->caja_id;
            }
        }
        
        if (!$cajaId) {
            $caja = Caja::first();
            if ($caja) {
                $cajaId = $caja->id;
            }
        }
        
        if (!$cajaId) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró una caja asignada o abierta para este usuario'
            ]);
        }
        
        $serie = Serie::where('tipo_comprobante', $tipo)
                     ->where('caja_id', $cajaId)
                     ->first();
        
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

            $cajaAbierta = AperturaCaja::where('responsable_id', Auth::id())
                                       ->where('estado', 'ABIERTA')
                                       ->first();
            
            if (!$cajaAbierta) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay una caja abierta para este usuario'
                ], 422);
            }

            $usuario = Auth::user();
            $almacenId = $usuario->almacen_id;
            
            if (!$almacenId) {
                $primerAlmacen = Almacen::first();
                $almacenId = $primerAlmacen ? $primerAlmacen->id : null;
            }
            
            if (!$almacenId) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay un almacén disponible para realizar la venta'
                ], 422);
            }

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

            // Un producto sin afectacion de IGV resuelta no se vende. Si una
            // importacion dejara pasar un PENDIENTE, el comprobante saldria con
            // un IGV que nadie decidio y con la afectacion por defecto de
            // ConstructorComprobante, que es GRAVADO. Es preferible frenar la
            // venta aca que emitir mal y tener que anular ante SUNAT.
            $sinClasificar = Producto::whereIn('id', array_column($productos, 'id'))
                ->whereNotIn('operacion', \App\Sunat\Tributos::AFECTACIONES)
                ->pluck('descripcion');

            if ($sinClasificar->isNotEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Estos productos no tienen definida su afectación de IGV y no pueden venderse: '
                        . $sinClasificar->implode(', ')
                ], 422);
            }

            // Un producto del catalogo entra sin precio: precio_compra sale de
            // la lista del proveedor y precio_venta lo decide la tienda. Hasta
            // que alguien lo complete, precio_venta vale 0.00 y eso NO es
            // gratis, es "todavia no se puso a la venta". Sin este control una
            // importacion recien hecha se podria cobrar a cero.
            $sinPrecio = Producto::whereIn('id', array_column($productos, 'id'))
                ->where('precio_venta', '<=', 0)
                ->pluck('descripcion');

            if ($sinPrecio->isNotEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Estos productos todavía no tienen precio de venta y no pueden venderse: '
                        . $sinPrecio->implode(', ')
                ], 422);
            }

            // lockForUpdate retiene la fila hasta el commit: sin esto dos ventas
            // simultaneas del ultimo articulo validan las dos contra el mismo
            // stock y lo dejan negativo.
            foreach ($productos as $item) {
                $stock = ProductoAlmacen::where('producto_id', $item['id'])
                                        ->where('almacen_id', $almacenId)
                                        ->lockForUpdate()
                                        ->first();
                
                if (!$stock || $stock->stock < $item['cantidad']) {
                    $producto = Producto::find($item['id']);
                    $stockActual = $stock ? $stock->stock : 0;
                    return response()->json([
                        'success' => false,
                        'message' => "Stock insuficiente para: {$producto->descripcion}. Stock disponible: {$stockActual} unidades"
                    ], 422);
                }
            }

            // El correlativo se toma bajo bloqueo: dos ventas concurrentes en la
            // misma serie emitirian el mismo numero, y para SUNAT un correlativo
            // repetido es rechazo del comprobante.
            $serie = Serie::where('tipo_comprobante', $request->tipo_comprobante)
                         ->where('caja_id', $cajaAbierta->caja_id)
                         ->lockForUpdate()
                         ->first();
            
            if (!$serie) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay serie configurada para este tipo de comprobante'
                ], 422);
            }

            $numero = $serie->correlativo + 1;
            // El precio de gondola ya incluye IGV, asi que se desagrega.
            $importes = \App\Sunat\Monto::desagregarIgv((float) $request->total);
            $subtotal = $importes['gravado'];
            $igv = $importes['igv'];

            // Generar el código QR con la información de la venta
            $documentoCompleto = $serie->serie . '-' . str_pad($numero, 8, '0', STR_PAD_LEFT);
            $qrData = json_encode([
                'documento' => $documentoCompleto,
                'fecha' => now()->format('Y-m-d H:i:s'),
                'total' => $request->total,
                'tipo' => $request->tipo_comprobante,
                'serie' => $serie->serie,
                'numero' => $numero,
                'empresa' => \App\Models\Empresa::first()?->nombre_razon_social ?? 'Mi Empresa',
                'ruc' => \App\Models\Empresa::first()?->ruc ?? '00000000000'
            ]);
            
            // En la base se guarda el contenido del QR; la imagen se arma aparte
            // para devolverla en la respuesta.
            $qrCodeBase64 = Venta::qrComoImagen($qrData);

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
                'total' => (float)$request->total,
                'pagado' => (float)$request->pagado,
                'cambio' => (float)$request->pagado - (float)$request->total,
                'detraccion' => $request->has('detraccion'),
                'observaciones' => $request->observaciones,
                'codigo_qr' => $qrData,
                'caja_id' => $cajaAbierta->caja_id,
                'usuario_id' => Auth::id(),
                // La venta nace APROBADA siempre. Antes una venta a credito
                // nacia PENDIENTE, que se confundia con "pendiente de SUNAT":
                // eso ahora vive en estado_pago, que es lo que realmente falta.
                'estado' => \App\Estados\EstadoVenta::APROBADA,
                'estado_pago' => $request->tipo_venta == 'CREDITO'
                    ? \App\Estados\EstadoPago::PENDIENTE
                    : \App\Estados\EstadoPago::PAGADA,
                'estado_devolucion' => \App\Estados\EstadoDevolucion::SIN_DEVOLUCION,
                'estado_sunat' => \App\Estados\EstadoSunat::NO_ENVIADO
            ]);

            $serie->correlativo = $numero;
            $serie->save();

            foreach ($productos as $item) {
                VentaDetalle::create([
                    'venta_id' => $venta->id,
                    'producto_id' => $item['id'],
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $item['precio'],
                    'total' => $item['cantidad'] * $item['precio'],
                    'almacen_id' => $almacenId
                ]);

                $stock = ProductoAlmacen::where('producto_id', $item['id'])
                                        ->where('almacen_id', $almacenId)
                                        ->lockForUpdate()
                                        ->first();
                
                if ($stock) {
                    $stock->stock -= $item['cantidad'];
                    $stock->save();
                }
            }

            if ($request->tipo_venta == 'CREDITO') {
                $montoCredito = (float)$request->total - ((float)$request->pagado ?? 0);
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

            // Despues del commit, nunca dentro: si se despacha en la
            // transaccion el worker puede tomar el job antes de que la venta
            // exista.
            //
            // El try es la garantia de fondo: la venta ya esta cerrada y no
            // puede deshacerse porque falle el envio. Con QUEUE_CONNECTION=sync
            // el job corre aca mismo, y sin este catch una caida de SUNAT
            // devolveria un 500 al cajero por una venta que si se registro.
            try {
                \App\Jobs\EnviarComprobanteASunat::dispatch($venta->id);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('No se pudo encolar el envío a SUNAT', [
                    'venta_id' => $venta->id,
                    'error' => $e->getMessage(),
                ]);
                // Queda en PENDIENTE: sunat:enviar la retoma despues.
            }

            return response()->json([
                'success' => true,
                'message' => 'Venta procesada exitosamente',
                'data' => [
                    'venta_id' => $venta->id,
                    'documento' => $venta->documento,
                    'subtotal' => (float)$subtotal,
                    'igv' => (float)$igv,
                    'total' => (float)$venta->total,
                    'pagado' => (float)$venta->pagado,
                    'cambio' => (float)$venta->cambio,
                    'tipo_venta' => $venta->tipo_venta,
                    'codigo_qr' => $qrCodeBase64, // <-- ENVIAR QR EN LA RESPUESTA
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