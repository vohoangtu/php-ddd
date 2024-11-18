@extends('layouts.app')

@section('title', 'My Wishlist')

@section('content')
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>My Wishlist</h1>
        @if(count($items) > 0)
        <button class="btn btn-outline-danger clear-wishlist">
            <i class="bi bi-trash"></i> Clear Wishlist
        </button>
        @endif
    </div>

    @if(count($items) > 0)
    <div class="row">
        @foreach($items as $item)
        <div class="col-md-4 mb-4">
            <div class="card h-100 wishlist-item" data-product-id="{{ $item->id }}">
                <!-- Product Image -->
                <img src="{{ $item->image }}" 
                     class="card-img-top" 
                     alt="{{ $item->name }}">
                
                <!-- Product Details -->
                <div class="card-body">
                    <h5 class="card-title">{{ $item->name }}</h5>
                    <p class="card-text text-muted">
                        {{ $item->category_name }}
                    </p>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="h5 mb-0">${{ number_format($item->price, 2) }}</span>
                        <span class="badge bg-{{ $item->stock > 0 ? 'success' : 'danger' }}">
                            {{ $item->stock > 0 ? 'In Stock' : 'Out of Stock' }}
                        </span>
                    </div>
                    <p class="card-text small text-muted">
                        Added {{ date('M d, Y', strtotime($item->added_at)) }}
                    </p>
                </div>

                <!-- Action Buttons -->
                <div class="card-footer bg-transparent">
                    <div class="d-grid gap-2">
                        @if($item->stock > 0)
                        <button class="btn btn-primary move-to-cart">
                            <i class="bi bi-cart-plus"></i> Add to Cart
                        </button>
                        @endif
                        <button class="btn btn-outline-danger remove-from-wishlist">
                            <i class="bi bi-heart-fill"></i> Remove
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @else
    <div class="text-center py-5">
        <i class="bi bi-heart display-1 text-muted"></i>
        <h3 class="mt-3">Your wishlist is empty</h3>
        <p class="text-muted">Browse our products and add items you love to your wishlist</p>
        <a href="/products" class="btn btn-primary">
            Browse Products
        </a>
    </div>
    @endif
</div>

<!-- Confirmation Modal -->
<div class="modal fade" id="confirmationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Action</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to proceed with this action?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    Cancel
                </button>
                <button type="button" class="btn btn-primary" id="confirmAction">
                    Confirm
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = new bootstrap.Modal(document.getElementById('confirmationModal'));
    
    // Move to cart
    document.querySelectorAll('.move-to-cart').forEach(button => {
        button.addEventListener('click', async function() {
            const item = this.closest('.wishlist-item');
            const productId = item.dataset.productId;
            
            try {
                const response = await fetch('/wishlist/move-to-cart', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ product_id: productId })
                });
                
                const data = await response.json();
                
                if (!data.success) {
                    throw new Error(data.error);
                }
                
                // Update wishlist count in header
                updateWishlistCount(data.wishlistCount);
                
                // Remove item from view
                item.remove();
                
                // Show success message
                showAlert('success', data.message);
                
                // Refresh if wishlist is empty
                if (document.querySelectorAll('.wishlist-item').length === 0) {
                    location.reload();
                }
            } catch (error) {
                showAlert('danger', error.message);
            }
        });
    });
    
    // Remove from wishlist
    document.querySelectorAll('.remove-from-wishlist').forEach(button => {
        button.addEventListener('click', function() {
            const item = this.closest('.wishlist-item');
            const productId = item.dataset.productId;
            
            const confirmBtn = document.getElementById('confirmAction');
            confirmBtn.onclick = () => {
                modal.hide();
                removeFromWishlist(productId, item);
            };
            
            modal.show();
        });
    });
    
    // Clear wishlist
    const clearButton = document.querySelector('.clear-wishlist');
    if (clearButton) {
        clearButton.addEventListener('click', function() {
            const confirmBtn = document.getElementById('confirmAction');
            confirmBtn.onclick = () => {
                modal.hide();
                clearWishlist();
            };
            
            modal.show();
        });
    }
    
    async function removeFromWishlist(productId, item) {
        try {
            const response = await fetch('/wishlist/remove', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ product_id: productId })
            });
            
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.error);
            }
            
            // Update wishlist count in header
            updateWishlistCount(data.count);
            
            // Remove item from view
            item.remove();
            
            // Show success message
            showAlert('success', data.message);
            
            // Refresh if wishlist is empty
            if (document.querySelectorAll('.wishlist-item').length === 0) {
                location.reload();
            }
        } catch (error) {
            showAlert('danger', error.message);
        }
    }
    
    async function clearWishlist() {
        try {
            const response = await fetch('/wishlist/clear', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.error);
            }
            
            location.reload();
        } catch (error) {
            showAlert('danger', error.message);
        }
    }
    
    function updateWishlistCount(count) {
        const wishlistCount = document.querySelector('.wishlist-count');
        if (wishlistCount) {
            wishlistCount.textContent = count;
            if (count === 0) {
                wishlistCount.style.display = 'none';
            }
        }
    }
    
    function showAlert(type, message) {
        const alert = document.createElement('div');
        alert.className = `alert alert-${type} alert-dismissible fade show`;
        alert.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.querySelector('.container').insertBefore(
            alert,
            document.querySelector('.container').firstChild
        );
        
        setTimeout(() => {
            alert.remove();
        }, 5000);
    }
});
</script>
@endsection