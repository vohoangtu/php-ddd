@extends('layouts.app')

@section('title', 'Shopping Cart')

@section('content')
<div class="container">
    <h1>Shopping Cart</h1>

    @if(count($items) > 0)
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Subtotal</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                        <tr>
                            <td>{{ $item['product']->getName() }}</td>
                            <td>${{ number_format($item['product']->getPrice(), 2) }}</td>
                            <td>
                                <form action="/cart/update/{{ $item['product']->getId() }}" method="POST" class="d-flex" style="max-width: 150px;">
                                    <input type="number" name="quantity" value="{{ $item['quantity'] }}" min="1" class="form-control me-2">
                                    <button type="submit" class="btn btn-sm btn-secondary">Update</button>
                                </form>
                            </td>
                            <td>${{ number_format($item['subtotal'], 2) }}</td>
                            <td>
                                <form action="/cart/remove/{{ $item['product']->getId() }}" method="POST" class="d-inline">
                                    <button type="submit" class="btn btn-sm btn-danger">Remove</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-end"><strong>Total:</strong></td>
                        <td><strong>${{ number_format($total, 2) }}</strong></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="d-flex justify-content-between mt-4">
            <a href="/products" class="btn btn-secondary">Continue Shopping</a>
            <a href="/checkout" class="btn btn-primary">Proceed to Checkout</a>
        </div>
    @else
        <div class="alert alert-info">
            Your cart is empty. <a href="/products">Continue shopping</a>
        </div>
    @endif
</div>
@endsection 