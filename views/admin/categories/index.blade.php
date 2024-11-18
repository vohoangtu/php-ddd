@extends('layouts.admin')

@section('title', 'Category Management')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Categories</h5>
        <a href="/admin/categories/create" class="btn btn-primary">
            <i class="bi bi-plus"></i> Add Category
        </a>
    </div>
    <div class="card-body">
        @include('admin.categories.partials.category-tree', ['categories' => $categories, 'level' => 0])
    </div>
</div>

<form id="deleteForm" method="POST" style="display: none;">
</form>

@endsection

@section('scripts')
<script>
function confirmDelete(categoryId) {
    if (confirm('Are you sure you want to delete this category?')) {
        const form = document.getElementById('deleteForm');
        form.action = `/admin/categories/${categoryId}/delete`;
        form.submit();
    }
}
</script>
@endsection 