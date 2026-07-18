<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class InvoiceWhatsAppController extends Controller
{
    protected WhatsAppService $whatsappService;

    public function __construct(WhatsAppService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    /**
     * إرسال الفاتورة كصورة عبر الواتساب (مع توليد تلقائي)
     */
    public function sendInvoice(Request $request): JsonResponse
    {
        $data = $request->validate([
            'sale_id' => ['required', 'exists:sales,id'],
            'customer_phone' => ['sometimes', 'string', 'max:20'],
            'custom_message' => ['sometimes', 'string', 'max:500'],
            'send_pdf' => ['sometimes', 'boolean'],
        ]);

        try {
            $sale = Sale::with(['party', 'details.product'])->findOrFail($data['sale_id']);
            $customerPhone = $data['customer_phone'] ?? $sale->party->phone;

            if (!$customerPhone) {
                return response()->json([
                    'success' => false,
                    'message' => 'رقم هاتف العميل غير متوفر'
                ], 422);
            }

            // Generate or get invoice PDF
            $pdfGenerator = new \App\Services\Invoice\InvoicePDFGenerator(
                new \Spatie\Browsershot\Browsershot()
            );
            $pdfPath = $pdfGenerator->generate($sale);

            // Send based on options
            $result = [
                'success' => true,
                'responses' => [],
            ];

            // Send image
            $imageGenerator = new \App\Services\Invoice\InvoiceImageGenerator();
            $imagePath = $imageGenerator->generate($sale);

            $imageResult = $this->whatsappService->sendInvoice(
                customer: ['phone' => $customerPhone, 'name' => $sale->party->name],
                imagePath: $imagePath,
                invoiceId: $sale->id,
                caption: $data['custom_message'] ?? null
            );

            $result['responses']['image'] = $imageResult;

            // Optionally send PDF
            if ($data['send_pdf'] ?? false) {
                $pdfResult = $this->whatsappService->sendPdfDocument(
                    phone: $customerPhone,
                    pdfPath: $pdfPath,
                    invoiceId: $sale->id
                );
                $result['responses']['pdf'] = $pdfResult;
            }

            return response()->json([
                'success' => true,
                'message' => 'تم إرسال الفاتورة عبر الواتساب بنجاح',
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * إرسال الفاتورة القديمة (upload image)
     * Keep for backward compatibility
     */
    public function sendInvoiceLegacy(Request $request): JsonResponse
    {
        $data = $request->validate([
            'invoice_image' => ['required', 'file', 'image'],
            'customer_phone' => ['required', 'string', 'max:20'],
            'invoice_id' => ['nullable', 'integer'],
        ]);

        try {
            // Get the uploaded image
            $imageFile = $request->file('invoice_image');
            
            // Store the image temporarily
            $path = $imageFile->store('invoices/temp', 'public');

            // Send via WhatsApp (old method - expects base64)
            $imageContent = file_get_contents($imageFile->getRealPath());
            $base64Image = 'data:image/jpeg;base64,' . base64_encode($imageContent);
            $imageResult = $this->whatsappService->sendInvoice(
                customer: ['phone' => $data['customer_phone']],
                imagePath: $base64Image,
                invoiceId: $data['invoice_id'] ?? 0
            );

            return response()->json([
                'success' => $imageResult['success'],
                'message' => $imageResult['success'] 
                    ? 'تم إرسال الفاتورة عبر الواتساب بنجاح' 
                    : 'فشل إرسال الفاتورة عبر الواتساب',
                'data' => $imageResult,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * إرسال نص عبر الواتساب فقط
     */
    public function sendText(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:20'],
            'message' => ['required', 'string', 'max:1000'],
        ]);

        try {
            $result = $this->whatsappService->sendTextMessage(
                phone: $data['phone'],
                message: $data['message']
            );

            return response()->json([
                'success' => $result['success'],
                'message' => $result['success'] 
                    ? 'تم إرسال الرسالة بنجاح' 
                    : 'فشل إرسال الرسالة',
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage()
            ], 500);
        }
    }
}
