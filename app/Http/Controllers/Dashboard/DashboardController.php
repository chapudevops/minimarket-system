<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Services\DashboardService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected DashboardService $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->middleware('auth');
        $this->dashboardService = $dashboardService;
    }

    public function index()
    {
        if (request()->has('_notificaciones')) {
            $alertas = $this->dashboardService->getAlertas();

            // El badge de la campana usa 'total', no el tamaño de 'alertas':
            // la lista viene acotada y contarla mostraria el limite en vez de
            // cuantos problemas hay realmente.
            return response()->json([
                'alertas' => $alertas['items'],
                'total' => $alertas['total'],
            ]);
        }

        $fechaInicio = request()->get('fecha_inicio', date('Y-m-01'));
        $fechaFin = request()->get('fecha_fin', date('Y-m-d'));

        $metrics = $this->dashboardService->getMetrics($fechaInicio, $fechaFin);

        $data = array_merge($metrics, [
            'fechaInicio' => $fechaInicio,
            'fechaFin' => $fechaFin,
            'cajaAbierta' => $this->dashboardService->getCajaAbierta(),
            'ventasMensuales' => $this->dashboardService->getVentasMensuales(),
            'comprasMensuales' => $this->dashboardService->getComprasMensuales(),
            'productosMasVendidos' => $this->dashboardService->getProductosMasVendidos(),
            'ultimasVentas' => $this->dashboardService->getUltimasVentas(),
            'ultimosGastos' => $this->dashboardService->getUltimosGastos(),
            'ventasPorDia' => $this->dashboardService->getVentasPorDia(),
            'empresa' => $this->dashboardService->getEmpresa(),
            'alertas' => $this->dashboardService->getAlertas(),
            'ventasPorTipo' => $this->dashboardService->getVentasPorTipoComprobante(),
        ]);

        return view('dashboard.index', $data);
    }

    public function getStoreLocation()
    {
        $empresa = $this->dashboardService->getEmpresa();

        if (!$empresa) {
            return response()->json(['error' => 'No se encontró información de la empresa'], 404);
        }

        $coordenadas = $this->extractCoordinatesFromLink($empresa->link_ubicacion);

        return response()->json([
            'lat' => $coordenadas['lat'] ?? -12.046374,
            'lng' => $coordenadas['lng'] ?? -77.042793,
            'name' => $empresa->nombre_comercial ?? $empresa->razon_social,
            'link_ubicacion' => $empresa->link_ubicacion,
        ]);
    }

    private function extractCoordinatesFromLink($link)
    {
        if (empty($link)) return null;

        preg_match('/@(-?\d+\.\d+),(-?\d+\.\d+)/', $link, $matches);
        if (count($matches) >= 3) {
            return ['lat' => (float) $matches[1], 'lng' => (float) $matches[2]];
        }

        preg_match('/q=(-?\d+\.\d+),(-?\d+\.\d+)/', $link, $matches);
        if (count($matches) >= 3) {
            return ['lat' => (float) $matches[1], 'lng' => (float) $matches[2]];
        }

        preg_match('/!3d(-?\d+\.\d+)!4d(-?\d+\.\d+)/', $link, $matches);
        if (count($matches) >= 3) {
            return ['lat' => (float) $matches[1], 'lng' => (float) $matches[2]];
        }

        return null;
    }
}
