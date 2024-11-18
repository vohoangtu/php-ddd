<?php

namespace App\Admin\Application;

use Jenssegers\Blade\Blade;

class ReportController
{
    private ReportService $reportService;
    private Blade $blade;

    public function __construct(ReportService $reportService, Blade $blade)
    {
        $this->reportService = $reportService;
        $this->blade = $blade;
    }

    public function index(): void
    {
        echo $this->blade->make('admin.reports.index')->render();
    }

    public function generate(): void
    {
        $type = filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING);
        $format = filter_input(INPUT_GET, 'format', FILTER_SANITIZE_STRING);
        $dateFrom = filter_input(INPUT_GET, 'date_from', FILTER_SANITIZE_STRING);
        $dateTo = filter_input(INPUT_GET, 'date_to', FILTER_SANITIZE_STRING);

        // Get report data
        switch ($type) {
            case 'sales':
                $data = $this->reportService->generateSalesReport([
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo
                ]);
                break;
            case 'inventory':
                $data = $this->reportService->generateInventoryReport();
                break;
            case 'customers':
                $data = $this->reportService->generateCustomerReport();
                break;
            default:
                header('HTTP/1.1 400 Bad Request');
                echo json_encode(['error' => 'Invalid report type']);
                return;
        }

        // Export report
        try {
            $file = $format === 'pdf' 
                ? $this->reportService->exportToPdf($data, $type)
                : $this->reportService->exportToExcel($data, $type);

            echo json_encode([
                'success' => true,
                'file' => $file
            ]);
        } catch (\Exception $e) {
            header('HTTP/1.1 500 Internal Server Error');
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
} 