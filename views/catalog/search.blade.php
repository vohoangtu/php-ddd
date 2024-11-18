@extends('layouts.app')

@section('title', 'Search Products')

@section('content')
<div class="container py-4">
    <div class="row">
        <!-- Filters Sidebar -->
        <div class="col-md-3">
            <div class="card mb-3">
                <div class="card-body">
                    <form action="/search" method="GET" id="filterForm">
                        <!-- Search Input -->
                        <div class="mb-3">
                            <label class="form-label">Search</label>
                            <input type="text" 
                                   name="q" 
                                   class="form-control" 
                                   value="{{ $params['q'] ?? '' }}"
                                   placeholder="Search products...">
                        </div>

                        <!-- Categories -->
                        <div class="mb-3">
                            <label class="form-label">Categories</label>
                            @foreach($filters['categories'] as $category)
                            <div class="form-check">
                                <input class="form-check-input" 
                                       type="radio" 
                                       name="category" 
                                       value="{{ $category->id }}"
                                       {{ ($params['category'] ?? '') == $category->id ? 'checked' : '' }}
                                       id="category{{ $category->id }}">
                                <label class="form-check-label" for="category{{ $category->id }}">
                                    {{ $category->name }} ({{ $category->product_count }})
                                </label>
                            </div>
                            @endforeach
                        </div>

                        <!-- Price Range -->
                        <div class="mb-3">
                            <label class="form-label">Price Range</label>
                            <div class="row">
                                <div class="col-6">
                                    <input type="number" 
                                           name="price_min" 
                                           class="form-control" 
                                           placeholder="Min"
                                           value="{{ $params['price_min'] ?? '' }}"
                                           min="{{ $filters['price_range']['min'] }}"
                                           max="{{ $filters['price_range']['max'] }}">
                                </div>
                                <div class="col-6">
                                    <input type="number" 
                                           name="price_max" 
                                           class="form-control" 
                                           placeholder="Max"
                                           value="{{ $params['price_max'] ?? '' }}"
                                           min="{{ $filters['price_range']['min'] }}"
                                           max="{{ $filters['price_range']['max'] }}">
                                </div>
                            </div>
                        </div>

                        <!-- Availability -->
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       name="in_stock" 
                                       value="1"
                                       {{ ($params['in_stock'] ?? '') ? 'checked' : '' }}
                                       id="inStock">
                                <label class="form-check-label" for="inStock">
                                    In Stock Only
                                </label>
                            </div>
                        </div>

                        <!-- Sort Options -->
                        <div class="mb-3">
                            <label class="form-label">Sort By</label>
                            <select name="sort" class="form-select">
                                <option value="created_at" {{ ($params['sort'] ?? '') == 'created_at' ? 'selected' : '' }}>
                                    Newest First
                                </option>
                                <option value="price" {{ ($params['sort'] ?? '') == 'price' ? 'selected' : '' }}>
                                    Price
                                </option>
                                <option value="name" {{ ($params['sort'] ?? '') == 'name' ? 'selected' : '' }}>
                                    Name
                                </option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            Apply Filters
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Products Grid -->
        <div class="col-md-9">
            <!-- Results Summary -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    Showing {{ $results['pagination']['per_page'] }} of 
                    {{ $results['pagination']['total'] }} products
                </div>
                <div class="btn-group">
                    <button type="button" 
                            class="btn btn-outline-secondary" 
                            onclick="setView('grid')">
                        <i class="bi bi-grid"></i>
                    </button>
                    <button type="button" 
                            class="btn btn-outline-secondary" 
                            onclick="setView('list')">
                        <i class="bi bi-list"></i>
                    </button>
                </div>
            </div>

            <!-- Products -->
            <div class="row row-cols-1 row-cols-md-3 g-4" id="productsContainer">
                @foreach($results['items'] as $product)
                <div class="col">
                    <div class="card h-100">
                        @if($product->image)
                            <img src="{{ $product->image }}" 
                                 class="card-img-top" 
                                 alt="{{ $product->name }}">
                        @endif
                        <div class="card-body">
                            <h5 class="card-title">{{ $product->name }}</h5>
                            <p class="card-text text-muted">
                                {{ $product->category_name }}
                            </p>
                            <p class="card-text">
                                ${{ number_format($product->price, 2) }}
                            </p>
                            <button class="btn btn-primary" 
                                    onclick="addToCart({{ $product->id }})">
                                Add to Cart
                            </button>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Pagination -->
            @if($results['pagination']['last_page'] > 1)
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    @for($i = 1; $i <= $results['pagination']['last_page']; $i++)
                    <li class="page-item {{ ($results['pagination']['current_page'] == $i) ? 'active' : '' }}">
                        <a class="page-link" 
                           href="{{ http_build_query(array_merge($params, ['page' => $i])) }}">
                            {{ $i }}
                        </a>
                    </li>
                    @endfor
                </ul>
            </nav>
            @endif
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
// View Toggle
function setView(type) {
    const container = document.getElementById('productsContainer');
    if (type === 'list') {
        container.classList.remove('row-cols-md-3');
        container.classList.add('row-cols-md-1');
    } else {
        container.classList.remove('row-cols-md-1');
        container.classList.add('row-cols-md-3');
    }
    localStorage.setItem('productView', type);
}

// Initialize view from localStorage
document.addEventListener('DOMContentLoaded', function() {
    const savedView = localStorage.getItem('productView');
    if (savedView) {
        setView(savedView);
    }
});

// Dynamic price range validation
const minPrice = document.querySelector('input[name="price_min"]');
const maxPrice = document.querySelector('input[name="price_max"]');

minPrice.addEventListener('change', function() {
    maxPrice.min = this.value;
});

maxPrice.addEventListener('change', function() {
    minPrice.max = this.value;
});

// Form auto-submit on certain changes
const autoSubmitElements = document.querySelectorAll('input[type="radio"], select[name="sort"]');
autoSubmitElements.forEach(element => {
    element.addEventListener('change', function() {
        document.getElementById('filterForm').submit();
    });
});
</script>
@endsection 