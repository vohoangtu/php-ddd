@extends('layouts.app')

@section('title', $product->getName())

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-6">
            <h1>{{ $product->getName() }}</h1>
            <p class="lead">{{ $product->getDescription() }}</p>
            <p class="h3 mb-4">${{ number_format($product->getPrice(), 2) }}</p>

            <form action="/cart/add/{{ $product->getId() }}" method="POST" class="mb-4">
                <div class="input-group" style="max-width: 200px;">
                    <input type="number" name="quantity" value="1" min="1" max="{{ $product->getStock() }}" class="form-control">
                    <button type="submit" class="btn btn-primary">Add to Cart</button>
                </div>
            </form>

            <p class="text-muted">
                Stock: {{ $product->getStock() }} units available
            </p>
        </div>
    </div>
</div>
@endsection 