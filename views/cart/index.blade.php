@extends('layouts.app')

@section('title', 'Shopping Cart')

@section('content')
<div class="container py-5">
    <h1 class="mb-4">Shopping Cart</h1>

    @if(count($items) > 0)
    <div class="row">
        <!-- Cart Items -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    @foreach($items as $item)
                    <div class="cart-item mb-3 pb-3 border-bottom" 
                         data-product-id="{{ $item['id'] }}">
                        <div class="row align-items-center">
                            <!-- Product Image -->
                            <div class="col-md-2">
                                <img src="{{ $item['image'] }}" 
                                     alt="{{ $item['name'] }}"
                                     class="img-fluid rounded">
                            </div>

                            <!-- Product Details -->
                            <div class="col-md-4">
                                <h5 class="mb-1">{{ $item['name'] }}</h5>
                                <p class="text-muted mb-0">
                                    ${{ number_format($item['price'], 2) }}
                                </p>
                            </div>

                            <!-- Quantity -->
                            <div class="col-md-3">
                                <div class="input-group">
                                    <button class="btn btn-outline-secondary quantity-decrease" 
                                            type="button">
                                        <i class="bi bi-dash"></i>
                                    </button>
                                    <input type="number" 
                                           class="form-control text-center quantity-input" 
                                           value="{{ $item['quantity'] }}"
                                           min="1"
                                           data-price="{{ $item['price'] }}">
                                    <button class="btn btn-outline-secondary quantity-increase" 
                                            type="button">
                                        <i class="bi bi-plus"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Subtotal -->
                            <div class="col-md-2">
                                <span class="item-subtotal">
                                    ${{ number_format($item['price'] * $item['quantity'], 2) }}
                                </span>
                            </div>

                            <!-- Remove Button -->
                            <div class="col-md-1">
                                <button class="btn btn-link text-danger remove-item">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    @endforeach

                    <!-- Cart Actions -->
                    <div class="d-flex justify-content-between align-items-center">
                        <a href="/products" class="btn btn-outline-primary">
                            <i class="bi bi-arrow-left"></i> Continue Shopping
                        </a>
                        <button class="btn btn-outline-danger clear-cart">
                            <i class="bi bi-trash"></i> Clear Cart
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Summary -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Order Summary</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal</span>
                        <span id="cart-subtotal">
                            ${{ number_format($subtotal, 2) }}
                        </span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Tax</span>
                        <span id="cart-tax">
                            ${{ number_format($tax, 2) }}
                        </span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span>Shipping</span>
                        <span id="cart-shipping">
                            ${{ number_format($shipping, 2) }}
                        </span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-3">
                        <strong>Total</strong>
                        <strong id="cart-total">
                            ${{ number_format($total, 2) }}
                        </strong>
                    </div>
                    <a href="/checkout" class="btn btn-primary w-100">
                        Proceed to Checkout
                    </a>
                </div>
            </div>
        </div>
    </div>
    @else
    <div class="text-center py-5">
        <i class="bi bi-cart3 display-1 text-muted mb-3"></i>
        <h3>Your cart is empty</h3>
        <p class="text-muted">Add some products to your cart and start shopping!</p>
        <a href="/products" class="btn btn-primary">
            Start Shopping
        </a>
    </div>
    @endif
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Quantity controls
    document.querySelectorAll('.quantity-decrease').forEach(button => {
        button.addEventListener('click', function() {
            const input = this.parentElement.querySelector('.quantity-input');
            if (input.value > 1) {
                input.value = parseInt(input.value) - 1;
                updateCartItem(input);
            }
        });
    });

    document.querySelectorAll('.quantity-increase').forEach(button => {
        button.addEventListener('click', function() {
            const input = this.parentElement.querySelector('.quantity-input');
            input.value = parseInt(input.value) + 1;
            updateCartItem(input);
        });
    });

    document.querySelectorAll('.quantity-input').forEach(input => {
        input.addEventListener('change', function() {
            updateCartItem(this);
        });
    });

    // Remove item
    document.querySelectorAll('.remove-item').forEach(button => {
        button.addEventListener('click', function() {
            const cartItem = this.closest('.cart-item');
            const productId = cartItem.dataset.productId;

            if (confirm('Are you sure you want to remove this item?')) {
                removeCartItem(productId, cartItem);
            }
        });
    });

    // Clear cart
    document.querySelector('.clear-cart')?.addEventListener('click', function() {
        if (confirm('Are you sure you want to clear your cart?')) {
            clearCart();
        }
    });

    // Update cart item
    async function updateCartItem(input) {
        const cartItem = input.closest('.cart-item');
        const productId = cartItem.dataset.productId;
        const quantity = parseInt(input.value);

        try {
            const response = await fetch('/cart/update', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    product_id: productId,
                    quantity: quantity
                })
            });

            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.error);
            }

            updateCartDisplay(data);
            updateItemSubtotal(cartItem, input.dataset.price, quantity);
        } catch (error) {
            alert(error.message);
            location.reload();
        }
    }

    // Remove cart item
    async function removeCartItem(productId, cartItem) {
        try {
            const response = await fetch('/cart/remove', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    product_id: productId
                })
            });

            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.error);
            }

            cartItem.remove();
            updateCartDisplay(data);

            if (data.cartCount === 0) {
                location.reload();
            }
        } catch (error) {
            alert(error.message);
        }
    }

    // Clear cart
    async function clearCart() {
        try {
            const response = await fetch('/cart/clear', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();
            
            if (data.success) {
                location.reload();
            }
        } catch (error) {
            alert('Failed to clear cart');
        }
    }

    // Update cart display
    function updateCartDisplay(data) {
        document.getElementById('cart-subtotal').textContent = 
            '$' + data.subtotal.toFixed(2);
        document.getElementById('cart-tax').textContent = 
            '$' + data.tax.toFixed(2);
        document.getElementById('cart-shipping').textContent = 
            '$' + data.shipping.toFixed(2);
        document.getElementById('cart-total').textContent = 
            '$' + data.total.toFixed(2);
        
        // Update cart count in header if exists
        const cartCount = document.querySelector('.cart-count');
        if (cartCount) {
            cartCount.textContent = data.cartCount;
        }
    }

    // Update item subtotal
    function updateItemSubtotal(cartItem, price, quantity) {
        const subtotal = price * quantity;
        cartItem.querySelector('.item-subtotal').textContent = 
            '$' + subtotal.toFixed(2);
    }
});
</script>
@endsection