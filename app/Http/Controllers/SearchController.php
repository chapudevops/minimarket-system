<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Producto;

class SearchController extends Controller
{
    /**
     * Return JSON results for product search.
     * Expected query parameter: q
     */
    public function index(Request $request)
    {
        $term = $request->query('q', '');
        // Simple where clause searching name and description
        $products = Producto::where('nombre', 'LIKE', "%{$term}%")
            ->orWhere('descripcion', 'LIKE', "%{$term}%")
            ->take(10)
            ->get(['id', 'nombre', 'precio', 'imagen'])
            ->toArray();
        return response()->json($products);
    }
}
