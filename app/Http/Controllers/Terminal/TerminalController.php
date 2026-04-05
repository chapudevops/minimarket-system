<?php

namespace App\Http\Controllers\Terminal;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use Illuminate\Http\Request;

class TerminalController extends Controller
{
    /**
     * Display the POS terminal.
     */
    public function index()
    {
        $productos = Producto::where('estado', 1)
                            ->orderBy('descripcion', 'asc')
                            ->get();
        return view('terminal.index', compact('productos'));
    }

    /**
     * Search products for POS.
     */
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
                    'stock' => $producto->stock
                ];
            })
        ]);
    }
}