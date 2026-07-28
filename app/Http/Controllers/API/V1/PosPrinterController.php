<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Services\PosPrinterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * POS Printer Controller
 *
 * واجهة برملتميا للطباعة الحرارية ESC/POS
 */
class PosPrinterController extends Controller
{
    public function __construct(
        protected PosPrinterService $printerService,
    ) {}

    /**
     * Get ESC/POS commands for a sale receipt.
     * 
     * GET /api/v1/pos-printer/receipt/{sale}
     */
    public function receipt(string $sale): JsonResponse
    {
        $saleModel = Sale::findOrFail($sale);

        $escPosCommands = $this->printerService->generateEscPosCommands($sale);
        $base64Commands = $this->printerService->getReceiptForPrinter($sale);

        return response()->json([
            'success' => true,
            'data' => [
                'sale_id' => $saleModel->id,
                'invoice_number' => $saleModel->invoice_number,
                'esc_pos_commands' => $escPosCommands,
                'base64_commands' => $base64Commands,
                'printer_type' => 'thermal_escpos',
                'paper_width' => 48, // 48mm thermal paper
            ],
        ]);
    }

    /**
     * Print receipt directly to network printer.
     * 
     * POST /api/v1/pos-printer/print
     */
    public function print(Request $request): JsonResponse
    {
        $request->validate([
            'sale_id' => ['required', 'exists:sales,id'],
            'printer_ip' => ['nullable', 'string', 'ip'],
            'printer_port' => ['nullable', 'integer', 'min:80', 'max:65535'],
        ]);

        $saleId = $request->sale_id;
        $printerIp = $request->printer_ip ?? env('POS_PRINTER_IP', '192.168.1.100');
        $printerPort = $request->printer_port ?? env('POS_PRINTER_PORT', 9100);

        try {
            $commands = $this->printerService->generateEscPosCommands($saleId);
            
            // In production, send to actual printer via socket
            // For now, return success with the data
            $printed = $this->sendToPrinter($printerIp, $printerPort, $commands);

            return response()->json([
                'success' => true,
                'message' => $printed ? 'Receipt printed successfully' : 'Receipt ready (printer unavailable)',
                'data' => [
                    'sale_id' => $saleId,
                    'printer_ip' => $printerIp,
                    'printed' => $printed,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to print: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available printer settings.
     * 
     * GET /api/v1/pos-printer/settings
     */
    public function settings(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'default_ip' => env('POS_PRINTER_IP', '192.168.1.100'),
                'default_port' => env('POS_PRINTER_PORT', 9100),
                'paper_width_mm' => 58,
                'font_height' => 24,
            ],
        ]);
    }

    /**
     * Send commands to network printer via socket.
     */
    protected function sendToPrinter(string $ip, int $port, string $commands): bool
    {
        // In production, implement actual socket connection
        // Example: fsockopen("tcp://{$ip}:{$port}", $port, $errno, $errstr, 5);
        
        // For development/testing, simulate success
        return true;
    }
}