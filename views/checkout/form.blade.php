@extends('layouts.app')

@section('title', 'Checkout')

@section('content')
<div class="container">
    <h1>Checkout</h1>

    @if(isset($_SESSION['error']))
        <div class="alert alert-danger">
            {{ $_SESSION['error'] }}
            @php unset($_SESSION['error']); @endphp
        </div>
    @endif

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Order Details</h5>
                    <form action="/checkout" method="POST">
                        <div class="mb-3">
                            <label for="customer_name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="customer_name" name="customer_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="customer_email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="customer_email" name="customer_email" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Place Order</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Order Summary</h5>
                    <table class="table">
                        <tbody>
                            @foreach($items as $item)
                                <tr>
                                    <td>{{ $item['product']->getName() }}</td>
                                    <td>x{{ $item['quantity'] }}</td>
                                    <td>${{ number_format($item['subtotal'], 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="2"><strong>Total:</strong></td>
                                <td><strong>${{ number_format($total, 2) }}</strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 