@extends('layouts.app')

@section('title', 'Payment')

@section('content')
<div class="container py-5">
    <div class="row">
        <div class="col-md-7">
            <!-- Order Summary -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Order Summary</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <tbody>
                                <tr>
                                    <td>Order ID:</td>
                                    <td>#{{ $order->id }}</td>
                                </tr>
                                <tr>
                                    <td>Subtotal:</td>
                                    <td>${{ number_format($order->subtotal, 2) }}</td>
                                </tr>
                                <tr>
                                    <td>Tax:</td>
                                    <td>${{ number_format($order->tax, 2) }}</td>
                                </tr>
                                <tr>
                                    <td>Shipping:</td>
                                    <td>${{ number_format($order->shipping, 2) }}</td>
                                </tr>
                                <tr>
                                    <th>Total:</th>
                                    <th>${{ number_format($order->total_amount, 2) }}</th>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Payment Methods -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Payment Method</h5>
                </div>
                <div class="card-body">
                    <ul class="nav nav-pills mb-3" id="payment-methods" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" 
                                    id="stripe-tab" 
                                    data-bs-toggle="pill" 
                                    data-bs-target="#stripe" 
                                    type="button">
                                Credit Card
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" 
                                    id="paypal-tab" 
                                    data-bs-toggle="pill" 
                                    data-bs-target="#paypal" 
                                    type="button">
                                PayPal
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="payment-methods-content">
                        <!-- Stripe Payment Form -->
                        <div class="tab-pane fade show active" 
                             id="stripe" 
                             role="tabpanel">
                            <form id="payment-form">
                                <div id="card-element" class="mb-3">
                                    <!-- Stripe Card Element -->
                                </div>
                                <div id="card-errors" class="alert alert-danger d-none"></div>
                                <button type="submit" 
                                        class="btn btn-primary w-100"
                                        id="stripe-submit">
                                    Pay ${{ number_format($order->total_amount, 2) }}
                                </button>
                            </form>
                        </div>

                        <!-- PayPal Button -->
                        <div class="tab-pane fade" 
                             id="paypal" 
                             role="tabpanel">
                            <div id="paypal-button-container"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Details -->
        <div class="col-md-5">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Order Details</h5>
                </div>
                <div class="card-body">
                    <h6>Shipping Address</h6>
                    <address class="mb-4">
                        {{ $order->shipping_address }}<br>
                        {{ $order->shipping_city }}, {{ $order->shipping_state }}<br>
                        {{ $order->shipping_country }} {{ $order->shipping_zip }}
                    </address>

                    <h6>Contact Information</h6>
                    <p class="mb-0">
                        {{ $order->customer_name }}<br>
                        {{ $order->customer_email }}<br>
                        {{ $order->customer_phone }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://js.stripe.com/v3/"></script>
<script src="https://www.paypal.com/sdk/js?client-id={{ $_ENV['PAYPAL_CLIENT_ID'] }}"></script>
<script>
// Initialize Stripe
const stripe = Stripe('{{ $stripeKey }}');
const elements = stripe.elements();
const card = elements.create('card');
card.mount('#card-element');

// Handle Stripe form submission
const form = document.getElementById('payment-form');
form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const submitButton = document.getElementById('stripe-submit');
    submitButton.disabled = true;

    try {
        // Create payment intent
        const response = await fetch('/checkout/stripe/process', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                orderId: {{ $order->id }},
                amount: {{ $order->total_amount }}
            })
        });

        const data = await response.json();
        if (!data.success) {
            throw new Error(data.error);
        }

        // Confirm card payment
        const result = await stripe.confirmCardPayment(data.clientSecret, {
            payment_method: {
                card: card,
                billing_details: {
                    name: '{{ $order->customer_name }}'
                }
            }
        });

        if (result.error) {
            throw new Error(result.error.message);
        }

        // Payment successful
        window.location.href = '/checkout/success';
    } catch (error) {
        const errorElement = document.getElementById('card-errors');
        errorElement.textContent = error.message;
        errorElement.classList.remove('d-none');
        submitButton.disabled = false;
    }
});

// Initialize PayPal
paypal.Buttons({
    createOrder: async () => {
        const response = await fetch('/checkout/paypal/process', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                orderId: {{ $order->id }},
                amount: {{ $order->total_amount }}
            })
        });

        const data = await response.json();
        if (!data.success) {
            throw new Error(data.error);
        }

        return data.orderId;
    },
    onApprove: (data, actions) => {
        return actions.order.capture().then(() => {
            window.location.href = '/checkout/paypal/success?token=' + data.orderID;
        });
    },
    onError: (err) => {
        console.error('PayPal Error:', err);
        alert('Payment failed. Please try again.');
    }
}).render('#paypal-button-container');
</script>
@endsection