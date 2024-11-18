@extends('layouts.admin')

@section('title', 'Order Details #' . $order['order']->id)

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Order Items</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order['items'] as $item)
                            <tr>
                                <td>
                                    {{ $item->product_name }}
                                </td>
                                <td>${{ number_format($item->price, 2) }}</td>
                                <td>{{ $item->quantity }}</td>
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

    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Order Information</h5>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Order ID:</dt>
                    <dd class="col-sm-8">#{{ $order['order']->id }}</dd>

                    <dt class="col-sm-4">Status:</dt>
                    <dd class="col-sm-8">
                        <form action="/admin/orders/{{ $order['order']->id }}/status" method="POST" class="d-flex gap-2">
                            <select name="status" class="form-select form-select-sm">
                                <option value="pending" {{ $order['order']->status === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="processing" {{ $order['order']->status === 'processing' ? 'selected' : '' }}>Processing</option>
                                <option value="completed" {{ $order['order']->status === 'completed' ? 'selected' : '' }}>Completed</option>
                                <option value="cancelled" {{ $order['order']->status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            </select>
                            <button type="submit" class="btn btn-sm btn-primary">Update Status</button>
                        </form>
                    </dd>
                </dl>
            </div>
        </div>
    </div>
</div>
@endsection 