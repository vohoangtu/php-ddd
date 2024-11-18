@extends('layouts.admin')

@section('title', 'Reports')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Sales Report</h5>
                </div>
                <div class="card-body">
                    <form id="salesReportForm" class="report-form">
                        <input type="hidden" name="type" value="sales">
                        
                        <div class="mb-3">
                            <label class="form-label">Date Range</label>
                            <div class="input-group">
                                <input type="date" 
                                       name="date_from" 
                                       class="form-control">
                                <span class="input-group-text">to</span>
                                <input type="date" 
                                       name="date_to" 
                                       class="form-control">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Format</label>
                            <div class="btn-group w-100">
                                <input type="radio" 
                                       class="btn-check" 
                                       name="format" 
                                       value="excel" 
                                       id="sales-excel" 
                                       checked>
                                <label class="btn btn-outline-primary" for="sales-excel">
                                    Excel
                                </label>

                                <input type="radio" 
                                       class="btn-check" 
                                       name="format" 
                                       value="pdf" 
                                       id="sales-pdf">
                                <label class="btn btn-outline-primary" for="sales-pdf">
                                    PDF
                                </label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            Generate Report
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Inventory Report</h5>
                </div>
                <div class="card-body">
                    <form id="inventoryReportForm" class="report-form">
                        <input type="hidden" name="type" value="inventory">

                        <div class="mb-3">
                            <label class="form-label">Format</label>
                            <div class="btn-group w-100">
                                <input type="radio" 
                                       class="btn-check" 
                                       name="format" 
                                       value="excel" 
                                       id="inventory-excel" 
                                       checked>
                                <label class="btn btn-outline-primary" for="inventory-excel">
                                    Excel
                                </label>

                                <input type="radio" 
                                       class="btn-check" 
                                       name="format" 
                                       value="pdf" 
                                       id="inventory-pdf">
                                <label class="btn btn-outline-primary" for="inventory-pdf">
                                    PDF
                                </label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            Generate Report
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Customer Report</h5>
                </div>
                <div class="card-body">
                    <form id="customerReportForm" class="report-form">
                        <input type="hidden" name="type" value="customers">

                        <div class="mb-3">
                            <label class="form-label">Format</label>
                            <div class="btn-group w-100">
                                <input type="radio" 
                                       class="btn-check" 
                                       name="format" 
                                       value="excel" 
                                       id="customer-excel" 
                                       checked>
                                <label class="btn btn-outline-primary" for="customer-excel">
                                    Excel
                                </label>

                                <input type="radio" 
                                       class="btn-check" 
                                       name="format" 
                                       value="pdf" 
                                       id="customer-pdf">
                                <label class="btn btn-outline-primary" for="customer-pdf">
                                    PDF
                                </label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            Generate Report
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Loading Modal -->
<div class="modal fade" id="loadingModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center py-4">
                <div class="spinner-border text-primary mb-3"></div>
                <h5>Generating Report...</h5>
                <p class="text-muted mb-0">This may take a few moments</p>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('.report-form');
    const loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'));

    forms.forEach(form => {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            loadingModal.show();

            try {
                const response = await fetch('/admin/reports/generate?'
            } catch (\Exception $e) {
                header('HTTP/1.1 500 Internal Server Error');
                console.error('Error generating report:', $e->getMessage());
            }
        });
    });
});
</script>
@endsection 