<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Producto;
use App\Models\Combo;
use Illuminate\Support\Facades\Session;

class StoreController extends Controller
{
    /**
     * Display the virtual store with products and combos.
     */
    public function index()
    {
        $productos = Producto::all();
        $combos = Combo::with('productos')->get(); // assuming Combo has relationship 'productos'
        return view('store.index', compact('productos', 'combos'));
    }

    /**
     * Add an item (product or combo) to the cart stored in session.
     */
    public function addToCart(Request $request)
    {
        $type = $request->input('type'); // 'producto' or 'combo'
        $id = $request->input('id');
        $quantity = $request->input('quantity', 1);

        $cart = Session::get('cart', []);
        $key = $type . '_' . $id;
        if (isset($cart[$key])) {
            $cart[$key]['quantity'] += $quantity;
        } else {
            $cart[$key] = ['type' => $type, 'id' => $id, 'quantity' => $quantity];
        }
        Session::put('cart', $cart);
        return redirect()->back()->with('status', 'Item added to cart');
    }

    /**
     * Show the current cart.
     */
    public function cart()
    {
        $cart = Session::get('cart', []);
        $items = [];
        $total = 0;
        foreach ($cart as $entry) {
            if ($entry['type'] === 'producto') {
                $product = Producto::find($entry['id']);
                if ($product) {
                    $price = $product->precio ?? 0;
                    $subtotal = $price * $entry['quantity'];
                    $total += $subtotal;
                    $items[] = ['type' => 'producto', 'model' => $product, 'quantity' => $entry['quantity'], 'subtotal' => $subtotal];
                }
            } else {
                $combo = Combo::with('productos')->find($entry['id']);
                if ($combo) {
                    $price = $combo->precio ?? 0;
                    $subtotal = $price * $entry['quantity'];
                    $total += $subtotal;
                    $items[] = ['type' => 'combo', 'model' => $combo, 'quantity' => $entry['quantity'], 'subtotal' => $subtotal];
                }
            }
        }
        return view('store.cart', compact('items', 'total'));
    }

    /**
     * Placeholder checkout implementation.
     */
    public function checkout()
    {
        // Here you would handle payment processing, order creation, etc.
        // For now we just clear the cart and show a thank‑you page.
        Session::forget('cart');
        return view('store.checkout');
    }
}
?>
