@extends('layouts.app')

@section('title', 'Order #' . $order['order']->id)

@section('content')
<div class="container">
    <h1>Order #{{ $order['order']->id }}</h1>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Order Details</h5>
                    <table class="table">
                        <tr>
                            <th>Order Date:</th>
                            <td>{{ date('F j, Y', strtotime($order['order']->created_at)) }}</td>
                        </tr>
                        <tr>
                            <th>Status:</th>
                            <td><span class="badge bg-{{ $order['order']->status == 'pending' ? 'warning' : 'success' }}">
                                {{ ucfirst($order['order']->status) }}
                            </span></td>
                        </tr>
                        <tr>
                            <th>Customer Name:</th>
                            <td>{{ $order['order']->customer_name }}</td>
                        </tr>
                        <tr>
                            <th>Email:</th>
                            <td>{{ $order['order']->customer_email }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Order Items</h5>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Quantity</th>
                                <th>Price</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order['items'] as $item)
                                <tr>
                                    <td>{{ $item->product_name }}</td>
                                    <td>{{ $item->quantity }}</td>
                                    <td>${{ number_format($item->price, 2) }}</td>
                                    <td>${{ number_format($item->price * $item->quantity, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                <td><strong>${{ number_format($order['order']->total_amount, 2) }}</strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 