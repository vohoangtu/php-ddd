@extends('layouts.admin')

@section('title', 'Product Inventory Details')

@section('content')
<div class="container-fluid">
    <div class="row">
        <!-- Product Details -->
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Product Details</h5>
                </div>
                <div class="card-body">
                    <h4>{{ $product->name }}</h4>
                    <p class="text-muted">SKU: {{ $product->sku }}</p>
                    
                    <div class="mb-3">
                        <label class="form-label">Current Stock</label>
                        <h2>{{ $product->stock }}</h2>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Low Stock Threshold</label>
                        <p>{{ $product->low_stock_threshold }}</p>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <p>{{ $product->category_name }}</p>
                    </div>
                </div>
            </div>

            <!-- Stock Adjustment Form -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Adjust Stock</h5>
                </div>
                <div class="card-body">
                    <form action="/admin/inventory/products/{{ $product->id }}/adjust" 
                          method="POST">
                        <div class="mb-3">
                            <label class="form-label">Quantity</label>
                            <input type="number" 
                                   class="form-control" 
                                   name="quantity" 
                                   required>
                            <small class="form-text text-muted">
                                Use negative values for stock reduction
                            </small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Reason</label>
                            <select class="form-select" name="reason" required>
                                <option value="purchase">Purchase</option>
                                <option value="return">Return</option>
                                <option value="adjustment">Manual Adjustment</option>
                                <option value="damage">Damage/Loss</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Reference</label>
                            <input type="text" 
                                   class="form-control" 
                                   name="reference" 
                                   placeholder="Optional reference number">
                        </div>

                        <button type="submit" class="btn btn-primary">
                            Adjust Stock
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Stock Movement History -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Stock Movement History</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Quantity</th>
                                    <th>Reason</th>
                                    <th>Reference</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($movements as $movement)
                                <tr>
                                    <td>{{ date('Y-m-d H:i', strtotime($movement->created_at)) }}</td>
                                    <td>
                                        <span class="badge bg-{{ $movement->quantity > 0 ? 'success' : 'danger' }}">
                                            {{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }}
                                        </span>
                                    </td>
                                    <td>{{ ucfirst($movement->reason) }}</td>
                                    <td>{{ $movement->reference ?? '-' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Inventory Alerts -->
            @if(count($alerts) > 0)
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Inventory Alerts</h5>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        @foreach($alerts as $alert)
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">{{ $alert->type }}</h6>
                                <small>{{ date('Y-m-d H:i', strtotime($alert->created_at)) }}</small>
                            </div>
                            <p class="mb-1">{{ $alert->message }}</p>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection 