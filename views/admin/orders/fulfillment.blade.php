@extends('layouts.admin')

@section('title', 'Order Fulfillment')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <!-- Order Details -->
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        Order #{{ $order->id }}
                        <span class="badge bg-{{ $order->status_color }} float-end">
                            {{ $order->status }}
                        </span>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Customer</label>
                        <p class="mb-0">{{ $order->customer_name }}</p>
                        <small class="text-muted">{{ $order->customer_email }}</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Shipping Address</label>
                        <address class="mb-0">
                            {{ $order->shipping_address }}<br>
                            {{ $order->shipping_city }}, {{ $order->shipping_state }}<br>
                            {{ $order->shipping_country }} {{ $order->shipping_zip }}
                        </address>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Order Total</label>
                        <p class="mb-0">${{ number_format($order->total_amount, 2) }}</p>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Payment Status</label>
                        <p class="mb-0">
                            <span class="badge bg-success">Paid</span>
                            {{ $order->paid_at }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Order Timeline -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Order Timeline</h5>
                </div>
                <div class="card-body p-0">
                    <div class="timeline">
                        @foreach($timeline as $event)
                        <div class="timeline-item">
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <h6 class="mb-0">{{ $event->status }}</h6>
                                <small class="text-muted">
                                    {{ date('M d, Y H:i', strtotime($event->created_at)) }}
                                </small>
                                @if($event->note)
                                <p class="mb-0 mt-2">{{ $event->note }}</p>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Items & Fulfillment -->
        <div class="col-md-8">
            <!-- Order Items -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Order Items</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>SKU</th>
                                    <th>Quantity</th>
                                    <th>Price</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($items as $item)
                                <tr>
                                    <td>{{ $item->product_name }}</td>
                                    <td>{{ $item->sku }}</td>
                                    <td>{{ $item->quantity }}</td>
                                    <td>${{ number_format($item->price, 2) }}</td>
                                    <td>${{ number_format($item->price * $item->quantity, 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Fulfillment Actions -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Fulfillment</h5>
                </div>
                <div class="card-body">
                    @if($order->status === 'paid')
                    <form action="/admin/orders/{{ $order->id }}/process" 
                          method="POST" 
                          class="mb-4">
                        <button type="submit" class="btn btn-primary">
                            Process Order
                        </button>
                    </form>
                    @endif

                    @if($order->status === 'processing')
                    <form action="/admin/orders/{{ $order->id }}/ship" 
                          method="POST" 
                          class="mb-4">
                        <div class="row">
                            <div class="col-md-5">
                                <div class="mb-3">
                                    <label class="form-label">Shipping Carrier</label>
                                    <select name="carrier" class="form-select" required>
                                        <option value="">Select carrier...</option>
                                        <option value="fedex">FedEx</option>
                                        <option value="ups">UPS</option>
                                        <option value="usps">USPS</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="mb-3">
                                    <label class="form-label">Tracking Number</label>
                                    <input type="text" 
                                           name="tracking_number" 
                                           class="form-control" 
                                           required>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="mb-3">
                                    <label class="form-label">&nbsp;</label>
                                    <button type="submit" class="btn btn-primary w-100">
                                        Ship
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                    @endif

                    @if($order->status === 'shipped')
                    <form action="/admin/orders/{{ $order->id }}/complete" 
                          method="POST">
                        <button type="submit" class="btn btn-success">
                            Mark as Completed
                        </button>
                    </form>
                    @endif

                    @if($shipment)
                    <div class="mt-4">
                        <h6>Shipment Details</h6>
                        <p class="mb-0">
                            Carrier: {{ ucfirst($shipment->carrier) }}<br>
                            Tracking Number: {{ $shipment->tracking_number }}<br>
                            Shipped Date: {{ date('M d, Y', strtotime($shipment->shipping_date)) }}
                        </p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
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
    const forms = document.querySelectorAll('form');
    const modal = new bootstrap.Modal(document.getElementById('confirmationModal'));
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const confirmBtn = document.getElementById('confirmAction');
            confirmBtn.onclick = () => {
                modal.hide();
                submitForm(form);
            };
            
            modal.show();
        });
    });
    
    async function submitForm(form) {
        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new FormData(form)
            });
            
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.error);
            }
            
            location.reload();
        } catch (error) {
            alert(error.message);
        }
    }
});
</script>
@endsection