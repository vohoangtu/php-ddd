namespace App\Admin\Application;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use TCPDF;
use Illuminate\Database\Capsule\Manager as DB;

class ReportService
{
    public function generateSalesReport(array $params): array
    {
        $query = DB::table('orders')
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('SUM(total_amount) as revenue'),
                DB::raw('AVG(total_amount) as average_order_value')
            )
            ->where('status', '!=', 'cancelled');

        if (!empty($params['date_from'])) {
            $query->where('created_at', '>=', $params['date_from']);
        }
        if (!empty($params['date_to'])) {
            $query->where('created_at', '<=', $params['date_to']);
        }

        return $query->groupBy(DB::raw('DATE(created_at)'))
                    ->orderBy('date', 'desc')
                    ->get()
                    ->all();
    }

    public function generateInventoryReport(): array
    {
        return DB::table('products')
            ->select(
                'products.id',
                'products.name',
                'products.sku',
                'products.stock',
                'categories.name as category',
                DB::raw('(SELECT COUNT(*) FROM order_items WHERE product_id = products.id) as times_ordered'),
                DB::raw('(SELECT SUM(quantity) FROM order_items WHERE product_id = products.id) as units_sold')
            )
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->orderBy('stock', 'asc')
            ->get()
            ->all();
    }

    public function generateCustomerReport(): array
    {
        return DB::table('users')
            ->select(
                'users.id',
                'users.name',
                'users.email',
                DB::raw('COUNT(orders.id) as total_orders'),
                DB::raw('SUM(orders.total_amount) as total_spent'),
                DB::raw('MAX(orders.created_at) as last_order_date')
            )
            ->leftJoin('orders', 'orders.customer_email', '=', 'users.email')
            ->where('users.role', 'user')
            ->groupBy('users.id', 'users.name', 'users.email')
            ->orderBy('total_spent', 'desc')
            ->get()
            ->all();
    }

    public function exportToExcel(array $data, string $type): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set headers based on report type
        switch ($type) {
            case 'sales':
                $headers = ['Date', 'Total Orders', 'Revenue', 'Average Order Value'];
                break;
            case 'inventory':
                $headers = ['ID', 'Product', 'SKU', 'Stock', 'Category', 'Times Ordered', 'Units Sold'];
                break;
            case 'customers':
                $headers = ['ID', 'Name', 'Email', 'Total Orders', 'Total Spent', 'Last Order'];
                break;
        }

        // Write headers
        foreach ($headers as $col => $header) {
            $sheet->setCellValueByColumnAndRow($col + 1, 1, $header);
        }

        // Write data
        foreach ($data as $row => $item) {
            $col = 1;
            foreach ((array)$item as $value) {
                $sheet->setCellValueByColumnAndRow($col, $row + 2, $value);
                $col++;
            }
        }

        // Auto-size columns
        foreach (range('A', $sheet->getHighestColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Generate file
        $filename = 'report-' . $type . '-' . date('Y-m-d-His') . '.xlsx';
        $path = __DIR__ . '/../../../public/reports/' . $filename;
        
        $writer = new Xlsx($spreadsheet);
        $writer->save($path);

        return '/reports/' . $filename;
    }

    public function exportToPdf(array $data, string $type): string
    {
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // Set document information
        $pdf->SetCreator('Your E-commerce');
        $pdf->SetAuthor('Admin');
        $pdf->SetTitle(ucfirst($type) . ' Report');

        // Set margins
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetHeaderMargin(5);
        $pdf->SetFooterMargin(10);

        // Add a page
        $pdf->AddPage();

        // Set font
        $pdf->SetFont('helvetica', '', 11);

        // Generate content based on report type
        switch ($type) {
            case 'sales':
                $this->generateSalesPdfContent($pdf, $data);
                break;
            case 'inventory':
                $this->generateInventoryPdfContent($pdf, $data);
                break;
            case 'customers':
                $this->generateCustomersPdfContent($pdf, $data);
                break;
        }

        // Generate file
        $filename = 'report-' . $type . '-' . date('Y-m-d-His') . '.pdf';
        $path = __DIR__ . '/../../../public/reports/' . $filename;
        
        $pdf->Output($path, 'F');

        return '/reports/' . $filename;
    }

    private function generateSalesPdfContent(TCPDF $pdf, array $data): void
    {
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'Sales Report', 0, 1, 'C');
        $pdf->Ln(5);

        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(40, 7, 'Date', 1);
        $pdf->Cell(40, 7, 'Orders', 1);
        $pdf->Cell(50, 7, 'Revenue', 1);
        $pdf->Cell(50, 7, 'Avg Order Value', 1);
        $pdf->Ln();

        $pdf->SetFont('helvetica', '', 11);
        foreach ($data as $row) {
            $pdf->Cell(40, 7, $row->date, 1);
            $pdf->Cell(40, 7, $row->total_orders, 1);
            $pdf->Cell(50, 7, '$' . number_format($row->revenue, 2), 1);
            $pdf->Cell(50, 7, '$' . number_format($row->average_order_value, 2), 1);
            $pdf->Ln();
        }
    }

    private function generateInventoryPdfContent(TCPDF $pdf, array $data): void
    {
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'Inventory Report', 0, 1, 'C');
        $pdf->Ln(5);

        $pdf->SetFont('helvetica', 'B', 11);
        $headers = ['Product', 'SKU', 'Stock', 'Category', 'Units Sold'];
        $widths = [60, 30, 20, 40, 30];

        foreach ($headers as $i => $header) {
            $pdf->Cell($widths[$i], 7, $header, 1);
        }
        $pdf->Ln();

        $pdf->SetFont('helvetica', '', 11);
        foreach ($data as $row) {
            $pdf->Cell($widths[0], 7, $row->name, 1);
            $pdf->Cell($widths[1], 7, $row->sku, 1);
            $pdf->Cell($widths[2], 7, $row->stock, 1);
            $pdf->Cell($widths[3], 7, $row->category, 1);
            $pdf->Cell($widths[4], 7, $row->units_sold, 1);
            $pdf->Ln();
        }
    }

    private function generateCustomersPdfContent(TCPDF $pdf, array $data): void
    {
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'Customer Report', 0, 1, 'C');
        $pdf->Ln(5);

        $pdf->SetFont('helvetica', 'B', 11);
        $headers = ['Name', 'Email', 'Orders', 'Total Spent', 'Last Order'];
        $widths = [40, 60, 20, 35, 35];

        foreach ($headers as $i => $header) {
            $pdf->Cell($widths[$i], 7, $header, 1);
        }
        $pdf->Ln();

        $pdf->SetFont('helvetica', '', 11);
        foreach ($data as $row) {
            $pdf->Cell($widths[0], 7, $row->name, 1);
            $pdf->Cell($widths[1], 7, $row->email, 1);
            $pdf->Cell($widths[2], 7, $row->total_orders, 1);
            $pdf->Cell($widths[3], 7, '$' . number_format($row->total_spent, 2), 1);
            $pdf->Cell($widths[4], 7, date('Y-m-d', strtotime($row->last_order_date)), 1);
            $pdf->Ln();
        }
    }
} 