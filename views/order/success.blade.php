@extends('layouts.app')

@section('title', 'Order Confirmed')

@section('content')
<div class="container">
    <div class="text-center my-5">
        <h1 class="text-success">Order Confirmed!</h1>
        <p class="lead">Thank you for your order. Your order number is: #{{ $orderId }}</p>
        <p>We'll send you an email confirmation shortly.</p>
        
        <div class="mt-4">
            <a href="/products" class="btn btn-primary">Continue Shopping</a>
            <a href="/order/{{ $orderId }}" class="btn btn-outline-primary">View Order Details</a>
        </div>
    </div>
</div>
@endsection 