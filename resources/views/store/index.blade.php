@extends('layouts.master')

@section('title', 'Tienda Virtual')

@section('content')
<div class="container py-5">
    <h1 class="mb-4">Catálogo de Productos</h1>
    <div class="row">
        @forelse($productos as $product)
        <div class="col-md-4 mb-4">
            <div class="card h-100 shadow-sm">
                @if($product->imagen)
                <img src="{{ asset('storage/' . $product->imagen) }}" class="card-img-top" alt="{{ $product->nombre }}">
                @else
                <img src="{{ asset('images/no-image.png') }}" class="card-img-top" alt="Sin imagen">
                @endif
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title">{{ $product->nombre }}</h5>
                    <p class="card-text text-muted">${{ number_format($product->precio, 2) }}</p>
                    <form method="POST" action="{{ route('store.cart.add') }}" class="mt-auto">
                        @csrf
                        <input type="hidden" name="type" value="producto">
                        <input type="hidden" name="id" value="{{ $product->id }}">
                        <input type="number" name="quantity" value="1" min="1" class="form-control mb-2" style="width: 80px;">
                        <button type="submit" class="btn btn-primary w-100">Agregar al carrito</button>
                    </form>
                </div>
            </div>
        </div>
        @empty
        <p class="text-center">No hay productos disponibles en este momento.</p>
        @endforelse
    </div>
    <div class="mt-4">
        <a href="{{ route('store.cart') }}" class="btn btn-success">Ver Carrito</a>
    </div>
</div>
@endsection
