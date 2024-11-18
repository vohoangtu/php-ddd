@extends('layouts.admin')

@section('title', 'Inventory Management')

@section('content')
<div class="container-fluid">
    <!-- Low Stock Alerts -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Low Stock Alerts</h5>
        </div>
        <div class="card-body">
            @if(count($lowStockProducts) > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>SKU</th>
                                <th>Category</th>
                                <th>Current Stock</th>
                                <th>Threshold</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($lowStockProducts as $product)
                            <tr>
                                <td>{{ $product->name }}</td>
                                <td>{{ $product->sku }}</td>
                                <td>{{ $product->category_name }}</td>
                                <td>
                                    <span class="badge bg-danger">
                                        {{ $product->stock }}
                                    </span>
                                </td>
                                <td>{{ $product->low_stock_threshold }}</td>
                                <td>
                                    <a href="/admin/inventory/products/{{ $product->id }}" 
                                       class="btn btn-sm btn-primary">
                                        Manage Stock
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-3">
                    <p class="text-muted mb-0">No low stock alerts</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Quick Stock Adjustment -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Quick Stock Adjustment</h5>
        </div>
        <div class="card-body">
            <form action="/admin/inventory/adjust" method="POST">
                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Product SKU</label>
                            <input type="text" 
                                   class="form-control" 
                                   name="sku" 
                                   required>
                        </div>
                    </div>
                    <div class="col-md-4">
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
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Reason</label>
                            <select class="form-select" name="reason" required>
                                <option value="purchase">Purchase</option>
                                <option value="return">Return</option>
                                <option value="adjustment">Manual Adjustment</option>
                                <option value="damage">Damage/Loss</option>
                            </select>
                        </div>
                    </div>
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
@endsection 