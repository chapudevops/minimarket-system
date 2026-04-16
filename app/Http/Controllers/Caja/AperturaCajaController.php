<?php

namespace App\Http\Controllers\Caja;

use App\Exports\ReporteCajaExport;
use App\Http\Controllers\Controller;
use App\Models\AperturaCaja;
use App\Models\Venta;
use App\Models\Gasto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class AperturaCajaController extends Controller
{
    public function index()
    {
        return view('apertura-caja.index');
    }

    public function getData(Request $request)
    {
        $aperturas = AperturaCaja::with('responsable')->orderBy('id', 'desc')->get();
        
        $index = 1;
        
        return response()->json([
            'data' => $aperturas->map(function($apertura) use (&$index) {
                return [
                    'correlativo' => $index++,
                    'id' => $apertura->id,
                    'fecha_apertura' => $apertura->fecha_apertura->format('d/m/Y'),
                    'responsable' => $apertura->responsable->name ?? '-',
                    'monto_inicial' => 'S/ ' . number_format($apertura->monto_inicial, 2),
                    'estado' => $apertura->estado,
                    'estado_badge' => $apertura->estado_badge,
                    'created_at' => $apertura->created_at ? $apertura->created_at->format('d/m/Y H:i') : '-',
                    'acciones' => $this->generateActions($apertura)
                ];
            })
        ]);
    }

    private function generateActions($apertura)
    {
        $actions = '
            <button type="button" class="btn btn-sm btn-info btn-detalle" 
                    data-id="' . $apertura->id . '"
                    data-responsable="' . htmlspecialchars($apertura->responsable->name ?? '-') . '"
                    data-fecha="' . $apertura->fecha_apertura->format('d/m/Y') . '"
                    data-monto_inicial="' . number_format($apertura->monto_inicial, 2) . '">
                <i class="bi bi-eye"></i> Detalle
            </button>
            <button type="button" class="btn btn-sm btn-success btn-resumen" 
                    data-id="' . $apertura->id . '"
                    data-responsable="' . htmlspecialchars($apertura->responsable->name ?? '-') . '"
                    data-fecha="' . $apertura->fecha_apertura->format('d/m/Y') . '"
                    data-monto_inicial="' . number_format($apertura->monto_inicial, 2) . '">
                <i class="bi bi-graph-up"></i> Resumen
            </button>
            <button type="button" class="btn btn-sm btn-danger btn-reporte" 
                    data-id="' . $apertura->id . '">
                <i class="bi bi-file-pdf"></i> Reporte
            </button>
        ';
        
        if ($apertura->estado == 'ABIERTA') {
            $actions .= '
                <button type="button" class="btn btn-sm btn-danger btn-cerrar" 
                        data-id="' . $apertura->id . '"
                        data-responsable="' . htmlspecialchars($apertura->responsable->name ?? '-') . '"
                        data-monto_inicial="' . number_format($apertura->monto_inicial, 2) . '">
                    <i class="bi bi-x-circle"></i> Cerrar
                </button>
            ';
        }
        
        return $actions;
    }

    public function verificarCajaAbierta()
    {
        $cajaAbierta = AperturaCaja::where('estado', 'ABIERTA')
                                   ->where('responsable_id', Auth::id())
                                   ->exists();
        
        return response()->json([
            'caja_abierta' => $cajaAbierta
        ]);
    }

    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            $cajaAbierta = AperturaCaja::where('estado', 'ABIERTA')
                                       ->where('responsable_id', Auth::id())
                                       ->exists();
            
            if ($cajaAbierta) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya tienes una caja abierta. Debes cerrarla antes de abrir una nueva.'
                ], 422);
            }

            $request->validate([
                'monto_inicial' => 'required|numeric|min:0',
                'fecha_apertura' => 'required|date'
            ], [
                'monto_inicial.required' => 'El monto inicial es obligatorio.',
                'monto_inicial.numeric' => 'El monto inicial debe ser un número.',
                'monto_inicial.min' => 'El monto inicial no puede ser negativo.',
                'fecha_apertura.required' => 'La fecha de apertura es obligatoria.',
                'fecha_apertura.date' => 'La fecha de apertura no es válida.'
            ]);

            $apertura = AperturaCaja::create([
                'fecha_apertura' => $request->fecha_apertura,
                'hora_apertura' => now(),
                'responsable_id' => Auth::id(),
                'monto_inicial' => $request->monto_inicial,
                'estado' => 'ABIERTA'
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => '✅ Caja abierta exitosamente',
                'data' => $apertura
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al abrir la caja: ' . $e->getMessage()
            ], 500);
        }
    }

    public function cerrar(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $apertura = AperturaCaja::findOrFail($id);
            
            if ($apertura->estado != 'ABIERTA') {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta caja ya está cerrada.'
                ], 422);
            }

            $request->validate([
                'monto_cierre' => 'required|numeric|min:0'
            ], [
                'monto_cierre.required' => 'El monto de cierre es obligatorio.',
                'monto_cierre.numeric' => 'El monto de cierre debe ser un número.',
                'monto_cierre.min' => 'El monto de cierre no puede ser negativo.'
            ]);

            $apertura->update([
                'estado' => 'CERRADA',
                'monto_cierre' => $request->monto_cierre,
                'fecha_cierre' => now(),
                'hora_cierre' => now(),
                'responsable_cierre_id' => Auth::id()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => '✅ Caja cerrada exitosamente',
                'data' => $apertura
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al cerrar la caja: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getDetalle($id)
    {
        $apertura = AperturaCaja::findOrFail($id);
        
        // Obtener ventas desde la fecha de apertura hasta la fecha de cierre o actual
        $fechaFin = $apertura->fecha_cierre ?? now();
        $ventas = Venta::where('caja_id', $apertura->id)
                       ->whereBetween('fecha_emision', [$apertura->fecha_apertura, $fechaFin])
                       ->with('cliente')
                       ->orderBy('fecha_emision', 'desc')
                       ->get();
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $apertura->id,
                'fecha_apertura' => $apertura->fecha_apertura->format('d/m/Y'),
                'hora_apertura' => $apertura->hora_apertura ? date('H:i:s', strtotime($apertura->hora_apertura)) : '-',
                'fecha_cierre' => $apertura->fecha_cierre ? $apertura->fecha_cierre->format('d/m/Y') : '-',
                'hora_cierre' => $apertura->hora_cierre ? date('H:i:s', strtotime($apertura->hora_cierre)) : '-',
                'responsable' => $apertura->responsable->name ?? '-',
                'monto_inicial' => $apertura->monto_inicial,
                'monto_cierre' => $apertura->monto_cierre,
                'estado' => $apertura->estado,
                'ventas' => $ventas->map(function($venta) {
                    return [
                        'id' => $venta->id,
                        'fecha' => $venta->fecha_emision->format('d/m/Y'),
                        'hora' => $venta->fecha_emision->format('H:i:s'),
                        'cliente' => $venta->cliente->nombre_razon_social ?? 'CLIENTES VARIOS',
                        'documento' => $venta->documento,
                        'numero' => $venta->documento,
                        'monto' => $venta->total
                    ];
                })
            ]
        ]);
    }

    public function getResumen($id)
    {
        $apertura = AperturaCaja::findOrFail($id);
        
        $fechaFin = $apertura->fecha_cierre ?? now();
        
        // Ventas del período
        $ventas = Venta::where('caja_id', $apertura->id)
                       ->whereBetween('fecha_emision', [$apertura->fecha_apertura, $fechaFin])
                       ->get();
        
        // Gastos del período
        $gastos = Gasto::whereBetween('fecha_emision', [$apertura->fecha_apertura, $fechaFin])
                       ->get();
        
        $totalVentas = $ventas->sum('total');
        $cantidadVentas = $ventas->count();
        $totalGastos = $gastos->sum('monto');
        
        $total = ($totalVentas + $apertura->monto_inicial) - $totalGastos;
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $apertura->id,
                'fecha_apertura' => $apertura->fecha_apertura->format('d/m/Y'),
                'responsable' => $apertura->responsable->name ?? '-',
                'monto_inicial' => $apertura->monto_inicial,
                'total_ventas' => $totalVentas,
                'cantidad_ventas' => $cantidadVentas,
                'gastos' => $gastos->map(function($gasto) {
                    return [
                        'motivo' => $gasto->motivo,
                        'monto' => $gasto->monto
                    ];
                }),
                'total_gastos' => $totalGastos,
                'total' => $total
            ]
        ]);
    }

    public function generarReporte($id)
    {
        $apertura = AperturaCaja::findOrFail($id);
        $fechaFin = $apertura->fecha_cierre ?? now();
        
        $ventas = Venta::where('caja_id', $apertura->id)
                       ->whereBetween('fecha_emision', [$apertura->fecha_apertura, $fechaFin])
                       ->with('cliente')
                       ->orderBy('fecha_emision', 'desc')
                       ->get();
        
        $gastos = Gasto::whereBetween('fecha_emision', [$apertura->fecha_apertura, $fechaFin])
                       ->get();
        
        $totalVentas = $ventas->sum('total');
        $cantidadVentas = $ventas->count();
        $totalGastos = $gastos->sum('monto');
        $total = ($totalVentas + $apertura->monto_inicial) - $totalGastos;
        
        $pdf = Pdf::loadView('apertura-caja.reporte', compact('apertura', 'ventas', 'gastos', 'totalVentas', 'cantidadVentas', 'totalGastos', 'total', 'fechaFin'));
        $pdf->setPaper('a4', 'portrait');
        
        return $pdf->download('reporte_caja_' . $apertura->id . '_' . date('Ymd_His') . '.pdf');
    }
    
public function exportarExcel($id)
{
    $apertura = AperturaCaja::findOrFail($id);
    $fechaFin = $apertura->fecha_cierre ?? now();
    
    $ventas = Venta::where('caja_id', $apertura->id)
                   ->whereBetween('fecha_emision', [$apertura->fecha_apertura, $fechaFin])
                   ->with('cliente')
                   ->orderBy('fecha_emision', 'desc')
                   ->get();
    
    $gastos = Gasto::whereBetween('fecha_emision', [$apertura->fecha_apertura, $fechaFin])
                   ->get();
    
    $totalVentas = $ventas->sum('total');
    $totalGastos = $gastos->sum('monto');
    $total = ($totalVentas + $apertura->monto_inicial) - $totalGastos;
    
    $export = new ReporteCajaExport($apertura, $ventas, $gastos, $totalVentas, $totalGastos, $total);
    
    return Excel::download($export, 'reporte_caja_' . $apertura->id . '_' . date('Ymd_His') . '.xlsx');
}
}