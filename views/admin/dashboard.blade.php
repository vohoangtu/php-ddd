@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
<!-- Sales Statistics -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h6 class="card-title">Today's Sales</h6>
                <h2>${{ number_format($stats['sales']['today'], 2) }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h6 class="card-title">Monthly Sales</h6>
                <h2>${{ number_format($stats['sales']['this_month'], 2) }}</h2>
                <small class="d-flex align-items-center">
                    @if($stats['sales']['growth'] > 0)
                        <i class="bi bi-arrow-up-circle me-1"></i>
                    @else
                        <i class="bi bi-arrow-down-circle me-1"></i>
                    @endif
                    {{ number_format(abs($stats['sales']['growth']), 1) }}% from last month
                </small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <h6 class="card-title">Total Orders</h6>
                <h2>{{ $stats['orders']['total'] }}</h2>
                <small>
                    {{ $stats['orders']['pending'] }} pending
                </small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <h6 class="card-title">Total Customers</h6>
                <h2>{{ $stats['customers']['total'] }}</h2>
                <small>
                    {{ $stats['customers']['new_this_month'] }} new this month
                </small>
            </div>
        </div>
    </div>
</div>

<!-- Sales Chart -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">Sales Analytics</h5>
    </div>
    <div class="card-body">
        <canvas id="salesChart" height="300"></canvas>
    </div>
</div>

<div class="row">
    <!-- Top Products -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Top Selling Products</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Sold</th>
                                <th>Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stats['topProducts'] as $product)
                            <tr>
                                <td>{{ $product->name }}</td>
                                <td>${{ number_format($product->price, 2) }}</td>
                                <td>{{ $product->total_sold }}</td>
                                <td>${{ number_format($product->total_revenue, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Orders -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Recent Orders</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stats['recentOrders'] as $order)
                            <tr>
                                <td>#{{ $order->id }}</td>
                                <td>{{ $order->customer_name }}</td>
                                <td>{{ $order->items_count }}</td>
                                <td>${{ number_format($order->total_amount, 2) }}</td>
                                <td>
                                    <span class="badge bg-{{ 
                                        $order->status === 'completed' ? 'success' : 
                                        ($order->status === 'pending' ? 'warning' : 
                                        ($order->status === 'cancelled' ? 'danger' : 'info')) 
                                    }}">
                                        {{ ucfirst($order->status) }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('salesChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: {!! json_encode($stats['salesChart']['labels']) !!},
            datasets: [{
                label: 'Sales ($)',
                data: {!! json_encode($stats['salesChart']['sales']) !!},
                borderColor: 'rgb(75, 192, 192)',
                tension: 0.1
            }, {
                label: 'Orders',
                data: {!! json_encode($stats['salesChart']['orders']) !!},
                borderColor: 'rgb(255, 99, 132)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            interaction: {
                intersect: false,
                mode: 'index'
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
});
</script>
@endsection 