<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class InvoiceWhatsAppController extends Controller
{
    protected $whatsappService;

    public function __construct(WhatsAppService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    /**
     * إرسال الفاتورة كصورة عبر الواتساب
     */
    public function sendInvoice(Request $request): JsonResponse
    {
        $data = $request->validate([
            'invoice_image' => ['required', 'file', 'image'],
            'customer_phone' => ['required', 'string', 'max:20'],
            'invoice_id' => ['nullable', 'integer'],
        ]);

        try {
            // Get the uploaded image
            $imageFile = $request->file('invoice_image');
            $imageBase64 = base64_encode(file_get_contents($imageFile->getRealPath()));
            
            // Send via WhatsApp
            $success = $this->whatsappService->sendInvoice(
                ['phone' => $data['customer_phone']],
                'data:image/jpeg;base64,' . $imageBase64,
                $data['invoice_id'] ?? 0
            );

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم إرسال الفاتورة عبر الواتساب بنجاح'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'فشل إرسال الفاتورة عبر الواتساب'
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage()
            ], 500);
        }
    }
}