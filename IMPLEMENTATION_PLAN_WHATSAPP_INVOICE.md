<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>خطة تنفيذ ميزة إرسال الفاتورة عبر الواتساب</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .step {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin: 15px 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .code-block {
            background: #2d3748;
            color: #e2e8f0;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            font-family: monospace;
            margin: 10px 0;
        }
        h2 { color: #667eea; }
        h3 { color: #4a5568; }
    </style>
</head>
<body>
    <h1>خطة تنفيذ ميزة إرسال فاتورة البيع عبر الواتساب</h1>

    <div class="step">
        <h2>الخطوة 1: تثبيت مكتبة html2canvas</h2>
        <p>لتحويل الفاتورة إلى صورة:</p>
        <div class="code-block">
npm install html2canvas
        </div>
    </div>

    <div class="step">
        <h2>الخطوة 2: إنشاء خدمة تحويل الفاتورة إلى صورة</h2>
        <p>إنشاء ملف: <code>app/Services/InvoiceImageService.php</code></p>
        <div class="code-block">
<?php

namespace App\Services;

use Intervencion\ImageIntervention;
use Illuminate\Support\Facades\View;

class InvoiceImageService
{
    /**
     * Generate invoice image from sale data
     */
    public function generateInvoiceImage($sale): string
    {
        // Render invoice view to HTML
        $html = View::make('invoices.sale', compact('sale'))->render();
        
        // Convert HTML to image
        $image = ImageIntervention::htmlToImage($html);
        
        return $image->base64(); // Return base64 encoded image
    }

    /**
     * Generate invoice PDF and convert to image
     */
    public function generateInvoicePdfAndImage($sale): array
    {
        // Using barryvdh/laravel-dompdf
        $pdf = PDF::loadView('invoices.sale', compact('sale'));
        $pdfPath = storage_path("app/invoices/invoice_{$sale->id}.pdf");
        $pdf->save($pdfPath);
        
        // Convert PDF to image
        $imagePath = storage_path("app/invoices/invoice_{$sale->id}.jpg");
        // Use imagemagick or similar
        
        return [
            'pdf_path' => $pdfPath,
            'image_path' => $imagePath,
            'image_base64' => base64_encode(file_get_contents($imagePath))
        ];
    }
}
        </div>
    </div>

    <div class="step">
        <h2>الخطوة 3: إنشاء خدمة إرسال الواتساب</h2>
        <p>إنشاء ملف: <code>app/Services/WhatsAppService.php</code></p>
        <div class="code-block">
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected $apiUrl;
    protected $apiKey;
    protected $phoneNumber;

    public function __construct()
    {
        $this->apiUrl = config('services.whatsapp_api.url');
        $this->apiKey = config('services.whatsapp_api.key');
        $this->phoneNumber = config('services.whatsapp_api.phone_number');
    }

    /**
     * Send invoice image to customer via WhatsApp
     */
    public function sendInvoice(array $customer, string $imageBase64, int $invoiceId): bool
    {
        try {
            // Get customer phone number
            $customerPhone = $this->formatPhoneNumber($customer['phone']);
            
            // WhatsApp API endpoint (using Twilio or similar)
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json'
            ])->post($this->apiUrl . '/messages', [
                'from' => $this->phoneNumber,
                'to' => $customerPhone,
                'type' => 'image',
                'image' => [
                    'link' => $this->uploadImageAndGetUrl($imageBase64)
                ],
                'caption' => "فاتورة البيع رقم #{$invoiceId}\nاليكم فاتورتكم، شكراً لتعاملكم معنا"
            ]);

            return $response->successful();
            
        } catch (\Exception $e) {
            Log::error('WhatsApp Send Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Format phone number to international format
     */
    protected function formatPhoneNumber(string $phone): string
    {
        // Remove any non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Add country code if not present
        if (!str_starts_with($phone, '20')) {
            $phone = '20' . $phone;
        }
        
        return $phone;
    }

    /**
     * Upload image and get public URL
     */
    protected function uploadImageAndGetUrl(string $base64Image): string
    {
        // Upload to storage and get URL
        $imageData = base64_decode($base64Image);
        $fileName = 'invoices/' . uniqid() . '.jpg';
        
        \Storage::disk('public')->put($fileName, $imageData);
        
        return \Storage::url($fileName);
    }
}
        </div>
    </div>

    <div class="step">
        <h2>الخطوة 4: تعديل متحكم الفواتير</h2>
        <p>تعديل ملف: <code>Modules/ZSyst/App/Http/Controllers/PosSaleController.php</code></p>
        <div class="code-block">
<?php

namespace Modules\ZSyst\App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\ZSyst\App\Models\Drug;
use Modules\ZSyst\App\Models\InventoryMovement;
use Modules\ZSyst\App\Models\InventoryItem;
use Modules\ZSyst\App\Models\PosSale;
use Modules\ZSyst\App\Models\PosSaleItem;
use App\Services\WhatsAppService;
use App\Services\InvoiceImageService;

class PosSaleController
{
    protected $whatsappService;
    protected $invoiceImageService;

    public function __construct(WhatsAppService $whatsappService, InvoiceImageService $invoiceImageService)
    {
        $this->whatsappService = $whatsappService;
        $this->invoiceImageService = $invoiceImageService;
    }

    /**
     * Store sale and send to customer via WhatsApp
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:20'], // إضافة رقم الهاتف
            'send_whatsapp' => ['nullable', 'boolean'], // خيار الإرسال عبر الواتساب
            'status' => ['nullable', 'string', 'max:50'],
            'subtotal' => ['nullable', 'numeric'],
            'tax_amount' => ['nullable', 'numeric'],
            'total_amount' => ['nullable', 'numeric'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
            'items' => ['nullable', 'array'],
        ]);

        return DB::transaction(function () use ($request, $data) {
            // Create the sale
            $sale = PosSale::create($data + ['status' => $data['status'] ?? 'completed']);

            // Process items if provided
            if (isset($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $item) {
                    $drug = Drug::where('barcode', $item['barcode'])->first();
                    
                    $saleItem = PosSaleItem::create([
                        'pos_sale_id' => $sale->id,
                        'drug_id' => $drug ? $drug->id : null,
                        'barcode' => $item['barcode'],
                        'name' => $item['name'],
                        'unit_price' => $item['price'],
                        'quantity' => $item['quantity'],
                        'line_total' => $item['price'] * $item['quantity'],
                        'prescription_id' => $item['prescription_id'] ?? null,
                    ]);

                    if ($drug) {
                        $this->updateInventoryForSale($drug, $item['quantity'], $sale->id);
                    }
                }
            }

            // Load relationships for response
            $sale->load('saleItems');

            // إذا كان هناك خيار إرسال الواتساب ورقم هاتف العميل
            if ($request->boolean('send_whatsapp') && !empty($data['customer_phone'])) {
                $imageBase64 = $this->invoiceImageService->generateInvoiceImage($sale);
                $this->whatsappService->sendInvoice(
                    ['phone' => $data['customer_phone']],
                    $imageBase64,
                    $sale->id
                );
            }

            return response()->json($sale, 201);
        });
    }
}
        </div>
    </div>

    <div class="step">
        <h2>الخطوة 5: إنشاء واجهة الطباعة</h2>
        <p>إنشاء زر الطباعة في الواجهة:</p>
        <div class="code-block">
<button onclick="printInvoice()" class="btn btn-primary">
    🖨️ طباعة الفاتورة
</button>

<button onclick="sendWhatsApp()" class="btn btn-success">
    📱 إرسال عبر الواتساب
</button>

<script>
function printInvoice() {
    const printContent = document.getElementById('invoice').innerHTML;
    const originalContent = document.body.innerHTML;
    
    document.body.innerHTML = printContent;
    window.print();
    document.body.innerHTML = originalContent;
}

function sendWhatsApp() {
    // Get invoice image
    const invoiceElement = document.getElementById('invoice');
    const customerPhone = document.getElementById('customer_phone').value;
    
    html2canvas(invoiceElement).then(canvas => {
        canvas.toBlob(blob => {
            // Send to server
            const formData = new FormData();
            formData.append('invoice_image', blob);
            formData.append('customer_phone', customerPhone);
            
            fetch('/api/send-invoice-whatsapp', {
                method: 'POST',
                body: formData
            }).then(response => {
                alert('تم إرسال الفاتورة عبر الواتساب');
            });
        });
    });
}
</script>
        </div>
    </div>

    <div class="step">
        <h2>الخطوة 6: إعدادات الخدمات</h2>
        <p>إضافة إلى ملف <code>config/services.php</code>:</p>
        <div class="code-block">
'whatsapp_api' => [
    'url' => env('WHATSAPP_API_URL', 'https://graph.facebook.com/v17.0/YOUR_PHONE_NUMBER_ID'),
    'key' => env('WHATSAPP_API_KEY'),
    'phone_number' => env('WHATSAPP_PHONE_NUMBER'),
],
        </div>
        
        <p>إضافة إلى ملف <code>.env</code>:</p>
        <div class="code-block">
WHATSAPP_API_URL=https://graph.facebook.com/v17.0/YOUR_PHONE_NUMBER_ID
WHATSAPP_API_KEY=your_whatsapp_business_api_key
WHATSAPP_PHONE_NUMBER=whatsapp:+14155238886
        </div>
    </div>
</body>
</html>