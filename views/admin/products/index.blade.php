@extends('layouts.admin')

@section('title', 'Products Management')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">All Products</h5>
        <a href="/admin/products/create" class="btn btn-primary">
            <i class="bi bi-plus"></i> Add New Product
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($products as $product)
                    <tr>
                        <td>{{ $product->getId() }}</td>
                        <td>
                            @if($product->getImage())
                                <img src="{{ $product->getImage() }}" alt="{{ $product->getName() }}" width="50">
                            @else
                                <span class="text-muted">No image</span>
                            @endif
                        </td>
                        <td>{{ $product->getName() }}</td>
                        <td>${{ number_format($product->getPrice(), 2) }}</td>
                        <td>
                            <span class="badge bg-{{ $product->getStock() > 10 ? 'success' : 'danger' }}">
                                {{ $product->getStock() }}
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-{{ $product->getStock() > 0 ? 'success' : 'danger' }}">
                                {{ $product->getStock() > 0 ? 'In Stock' : 'Out of Stock' }}
                            </span>
                        </td>
                        <td>
                            <div class="btn-group">
                                <a href="/admin/products/{{ $product->getId() }}/edit" 
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <button type="button" 
                                        class="btn btn-sm btn-outline-danger" 
                                        onclick="confirmDelete({{ $product->getId() }})">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<form id="deleteForm" method="POST" style="display: none;">
</form>

@endsection

@section('scripts')
<script>
function confirmDelete(productId) {
    if (confirm('Are you sure you want to delete this product?')) {
        const form = document.getElementById('deleteForm');
        form.action = `/admin/products/${productId}/delete`;
        form.submit();
    }
}
</script>
@endsection 