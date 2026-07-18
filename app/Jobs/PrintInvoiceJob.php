<?php

namespace App\Jobs;

use App\Models\Sale;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class PrintInvoiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Sale $sale,
        public ?string $pdfPath = null,
        public array $options = []
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // If no PDF path, generate one
            $pdfUrl = $this->pdfPath 
                ? asset('storage/' . $this->pdfPath)
                : route('sales.invoice.pdf', $this->sale->id);

            // Send to printer via ESC/POS or print node API
            // This depends on your printer setup
            $printerIp = $this->options['printer_ip'] ?? config('services.printer.ip');
            $printerPort = $this->options['printer_port'] ?? config('services.printer.port', 9100);

            if ($printerIp) {
                $this->sendToNetworkPrinter($pdfUrl, $printerIp, $printerPort);
            } else {
                // Just log for now - in real implementation, send to print node or similar
                Log::info('Invoice queued for printing', [
                    'sale_id' => $this->sale->id,
                    'pdf_path' => $pdfUrl,
                    'branch_id' => $this->sale->branch_id,
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Print invoice failed', [
                'sale_id' => $this->sale->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send to network printer
     */
    protected function sendToNetworkPrinter(string $pdfUrl, string $printerIp, int $printerPort): void
    {
        // In production, you would convert PDF to ESC/POS commands
        // or use a service like PrintNode
        
        $printNodeToken = config('services.print_node.token');
        if ($printNodeToken) {
            Http::withToken($printNodeToken)->post('https://api.printnode.com/printjobs', [
                'printerId' => $this->options['printer_id'] ?? null,
                'title' => "Invoice #{$this->sale->invoiceNumber}",
                'contentType' => 'pdf',
                'content' => base64_encode(file_get_contents($pdfUrl)),
                'source' => 'POS System',
            ]);
        }
    }
}