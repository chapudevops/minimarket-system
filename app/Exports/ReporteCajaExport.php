<?php

namespace App\Exports;

use App\Models\AperturaCaja;
use App\Models\Venta;
use App\Models\Gasto;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ReporteCajaExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths
{
    protected $apertura;
    protected $ventas;
    protected $gastos;
    protected $totalVentas;
    protected $totalGastos;
    protected $total;

    public function __construct($apertura, $ventas, $gastos, $totalVentas, $totalGastos, $total)
    {
        $this->apertura = $apertura;
        $this->ventas = $ventas;
        $this->gastos = $gastos;
        $this->totalVentas = $totalVentas;
        $this->totalGastos = $totalGastos;
        $this->total = $total;
    }

    public function array(): array
    {
        $data = [];
        
        // Título del reporte
        $data[] = ['REPORTE DE CAJA'];
        $data[] = [];
        
        // Información general
        $data[] = ['INFORMACIÓN GENERAL'];
        $data[] = ['Responsable:', $this->apertura->responsable->name ?? '-'];
        $data[] = ['Fecha Apertura:', $this->apertura->fecha_apertura->format('d/m/Y H:i:s')];
        $data[] = ['Fecha Cierre:', $this->apertura->fecha_cierre ? $this->apertura->fecha_cierre->format('d/m/Y H:i:s') : 'En curso'];
        $data[] = ['Monto Inicial:', 'S/ ' . number_format($this->apertura->monto_inicial, 2)];
        $data[] = ['Estado:', $this->apertura->estado == 'ABIERTA' ? 'Abierta' : 'Cerrada'];
        $data[] = [];
        
        // Ventas realizadas
        $data[] = ['VENTAS REALIZADAS'];
        $data[] = ['#', 'Fecha', 'Hora', 'Cliente', 'Documento', 'Monto S/'];
        
        foreach ($this->ventas as $index => $venta) {
            $data[] = [
                $index + 1,
                $venta->fecha_emision->format('d/m/Y'),
                $venta->fecha_emision->format('H:i:s'),
                $venta->cliente->nombre_razon_social ?? 'CLIENTES VARIOS',
                $venta->documento,
                number_format($venta->total, 2)
            ];
        }
        
        $data[] = [];
        $data[] = ['TOTAL VENTAS:', '', '', '', '', 'S/ ' . number_format($this->totalVentas, 2)];
        $data[] = [];
        
        // Gastos del período
        $data[] = ['GASTOS DEL PERÍODO'];
        $data[] = ['#', 'Fecha', 'Motivo', 'Monto S/'];
        
        foreach ($this->gastos as $index => $gasto) {
            $data[] = [
                $index + 1,
                $gasto->fecha_emision->format('d/m/Y'),
                $gasto->motivo,
                number_format($gasto->monto, 2)
            ];
        }
        
        $data[] = [];
        $data[] = ['TOTAL GASTOS:', '', '', 'S/ ' . number_format($this->totalGastos, 2)];
        $data[] = [];
        
        // Resumen financiero
        $data[] = ['RESUMEN FINANCIERO'];
        $data[] = ['Monto Inicial:', 'S/ ' . number_format($this->apertura->monto_inicial, 2)];
        $data[] = ['Total Ventas:', 'S/ ' . number_format($this->totalVentas, 2)];
        $data[] = ['Total Gastos:', 'S/ ' . number_format($this->totalGastos, 2)];
        $data[] = ['TOTAL GENERAL:', 'S/ ' . number_format($this->total, 2)];
        $data[] = [];
        
        // Footer
        $data[] = ['Reporte generado el ' . now()->format('d/m/Y H:i:s')];
        $data[] = ['© ' . date('Y') . ' InfinityDev - Todos los derechos reservados'];
        
        return $data;
    }

    public function headings(): array
    {
        return [];
    }

    public function styles(Worksheet $sheet)
    {
        // Estilos para el título principal
        $sheet->mergeCells('A1:F1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
                'color' => ['argb' => 'FFFFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF1E3C72'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        
        // Estilos para los títulos de sección
        $sectionRow = [4, 10, 19, 25];
        foreach ($sectionRow as $row) {
            $sheet->getStyle('A' . $row)->applyFromArray([
                'font' => [
                    'bold' => true,
                    'size' => 12,
                    'color' => ['argb' => 'FFFFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF2A5298'],
                ],
            ]);
        }
        
        // Estilos para los encabezados de tablas
        $headerRow = [5, 11, 20];
        foreach ($headerRow as $row) {
            $sheet->getStyle('A' . $row . ':F' . $row)->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['argb' => 'FFFFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF0D6EFD'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ]);
        }
        
        // Estilos para totales
        $totalVentasRow = $this->ventas->count() + 7;
        $sheet->getStyle('A' . $totalVentasRow . ':F' . $totalVentasRow)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FF198754'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFD1E7DD'],
            ],
        ]);
        
        $totalGastosRow = $this->gastos->count() + 16;
        $sheet->getStyle('A' . $totalGastosRow . ':D' . $totalGastosRow)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FFDC3545'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFF8D7DA'],
            ],
        ]);
        
        // Estilos para el total general
        $totalGeneralRow = $this->gastos->count() + 24;
        $sheet->getStyle('A' . $totalGeneralRow . ':B' . $totalGeneralRow)->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
                'color' => ['argb' => 'FFFFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF1E3C72'],
            ],
        ]);
        
        // Bordes para todas las celdas con datos
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();
        $sheet->getStyle('A1:' . $highestColumn . $highestRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FFD0D0D0'],
                ],
            ],
        ]);
        
        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,   // #
            'B' => 15,  // Fecha
            'C' => 12,  // Hora
            'D' => 40,  // Cliente / Motivo
            'E' => 20,  // Documento
            'F' => 15,  // Monto
        ];
    }
}