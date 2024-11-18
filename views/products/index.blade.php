@extends('layouts.app')

@section('title', 'Products')

@section('content')
<div class="container">
    <h1>Products</h1>
    <div class="row">
        @foreach($products as $product)
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">{{ $product->getName() }}</h5>
                        <p class="card-text">{{ $product->getDescription() }}</p>
                        <p class="card-text">Price: ${{ $product->getPrice() }}</p>
                        <a href="/products/{{ $product->getId() }}" class="btn btn-primary">View Details</a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection 