<?php

namespace App\Services\Invoice;

use App\Models\InvoiceNotification;
use App\Models\Sale;
use App\Models\Party;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Log;

class InvoiceNotificationService
{
    protected WhatsAppService $whatsappService;
    protected InvoicePDFGenerator $pdfGenerator;
    protected InvoiceImageGenerator $imageGenerator;

    public function __construct(
        WhatsAppService $whatsappService,
        InvoicePDFGenerator $pdfGenerator,
        InvoiceImageGenerator $imageGenerator
    ) {
        $this->whatsappService = $whatsappService;
        $this->pdfGenerator = $pdfGenerator;
        $this->imageGenerator = $imageGenerator;
    }

    /**
     * إرسال الفاتورة عبر قنوات متعددة
     *
     * @param Sale $sale الفاتورة
     * @param array $channels القنوات المطلوبة مع الخيارات
     * @return array نتائج الإرسال
     */
    public function sendInvoice(
        Sale $sale,
        array $channels,
        array $options = []
    ): array {
        $results = [];
        $customer = $sale->party;

        // توليد الملفات
        $files = $this->generateInvoiceFiles($sale, $options);

        // إرسال عبر كل قناة
        foreach ($channels as $channel => $channelOptions) {
            try {
                $result = match ($channel) {
                    'whatsapp' => $this->sendViaWhatsApp($sale, $customer, $files, $channelOptions),
                    'sms' => $this->sendViaSMS($sale, $customer, $channelOptions),
                    'email' => $this->sendViaEmail($sale, $customer, $files, $channelOptions),
                    'print' => $this->sendToPrint($sale, $channelOptions),
                    default => throw new \InvalidArgumentException("Invalid channel: {$channel}")
                };

                $results[$channel] = [
                    'success' => true,
                    'data' => $result,
                ];

            } catch (\Exception $e) {
                $results[$channel] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];

                // تسجيل الفشل
                $this->logFailedNotification($sale, $channel, $e);
            }
        }

        return $results;
    }

    /**
     * إرسال عبر واتساب
     */
    private function sendViaWhatsApp(
        Sale $sale,
        ?Party $customer,
        array $files,
        array $options
    ): array {
        if (!$customer || !$customer->phone) {
            throw new \Exception('Customer phone number not available');
        }

        $company = $sale->company;
        $settings = $company->invoice_notification_settings ?? [];

        // بناء الرسالة
        $message = $this->buildInvoiceMessage($sale, $options['customMessage'] ?? null, $settings);

        // إرسال الملفات
        $sentFiles = [];
        $messageIds = [];

        // إرسال كصورة
        if (($options['sendImage'] ?? $settings['send_as_image'] ?? false) && isset($files['image'])) {
            $imageResult = $this->whatsappService->sendInvoice(
                customer: ['phone' => $customer->phone, 'name' => $customer->name],
                imagePath: $files['image'],
                invoiceId: $sale->id,
                caption: $message
            );

            if ($imageResult['success']) {
                $sentFiles[] = 'image';
                $messageIds[] = $imageResult['message_id'];
            } else {
                throw new \Exception('Failed to send image: ' . ($imageResult['error'] ?? 'Unknown error'));
            }
        }

        // إرسال كـ PDF
        if (($options['sendPDF'] ?? $settings['send_as_pdf'] ?? false) && isset($files['pdf'])) {
            $pdfResult = $this->whatsappService->sendPdfDocument(
                phone: $customer->phone,
                pdfPath: $files['pdf'],
                invoiceId: $sale->id
            );

            if ($pdfResult['success']) {
                $sentFiles[] = 'pdf';
                $messageIds[] = $pdfResult['message_id'];
            } else {
                throw new \Exception('Failed to send PDF: ' . ($pdfResult['error'] ?? 'Unknown error'));
            }
        }

        // تسجيل الإشعار
        $notification = InvoiceNotification::create([
            'sale_id' => $sale->id,
            'customer_id' => $customer->id,
            'branch_id' => $sale->branch_id,
            'user_id' => auth()->id() ?? 1,
            'channel' => 'whatsapp',
            'status' => 'sent',
            'recipient' => $customer->phone,
            'message_content' => $message,
            'file_type' => implode(',', $sentFiles),
            'message_id' => implode(',', array_filter($messageIds)),
            'sent_at' => now(),
        ]);

        return [
            'notification_id' => $notification->id,
            'files_sent' => $sentFiles,
            'message_ids' => $messageIds,
        ];
    }

    /**
     * إرسال عبر SMS (مؤقتاً غير مفعل - يحتاج مزود SMS)
     */
    private function sendViaSMS(
        Sale $sale,
        ?Party $customer,
        array $options
    ): array {
        // مؤقتاً - تسجيل العملية فقط
        // لتمكين SMS، أضف مكتبة SMS مثل Twilio أو مزود آخر

        $message = $this->buildSMSMessage($sale, $options);

        $notification = InvoiceNotification::create([
            'sale_id' => $sale->id,
            'customer_id' => $customer->id,
            'branch_id' => $sale->branch_id,
            'user_id' => auth()->id() ?? 1,
            'channel' => 'sms',
            'status' => 'pending', // تم التنفيذ مؤقتاً
            'recipient' => $customer->phone ?? '',
            'message_content' => $message,
            'sent_at' => now(),
        ]);

        return [
            'notification_id' => $notification->id,
            'message' => $message,
        ];
    }

    /**
     * إرسال عبر Email (مؤقتاً غير مفعل - يحتاج إعدادات البريد)
     */
    private function sendViaEmail(
        Sale $sale,
        ?Party $customer,
        array $files,
        array $options
    ): array {
        // مؤقتاً - تسجيل العملية فقط
        // لتمكين Email، استخدم Mail facade

        $message = $this->buildEmailMessage($sale, $options);

        $notification = InvoiceNotification::create([
            'sale_id' => $sale->id,
            'customer_id' => $customer->id,
            'branch_id' => $sale->branch_id,
            'user_id' => auth()->id() ?? 1,
            'channel' => 'email',
            'status' => 'pending', // تم التنفيذ مؤقتاً
            'recipient' => $customer->email ?? '',
            'message_content' => $message,
            'file_path' => $files['pdf'] ?? null,
            'sent_at' => now(),
        ]);

        return [
            'notification_id' => $notification->id,
        ];
    }

    /**
     * إرسال للطباعة
     */
    private function sendToPrint(Sale $sale, array $options): array
    {
        // توليد PDF للطباعة
        $pdfPath = $this->pdfGenerator->generateForPrint($sale, $options);

        // تسجيل الإشعار
        $notification = InvoiceNotification::create([
            'sale_id' => $sale->id,
            'customer_id' => $sale->party_id,
            'branch_id' => $sale->branch_id,
            'user_id' => auth()->id() ?? 1,
            'channel' => 'print',
            'status' => 'pending',
            'file_path' => $pdfPath,
            'sent_at' => now(),
        ]);

        return [
            'notification_id' => $notification->id,
            'pdf_path' => $pdfPath,
        ];
    }

    /**
     * توليد ملفات الفاتورة
     */
    private function generateInvoiceFiles(Sale $sale, array $options): array
    {
        $files = [];

        if ($options['needImage'] ?? true) {
            try {
                $files['image'] = $this->imageGenerator->generate($sale);
            } catch (\Exception $e) {
                Log::warning('Could not generate invoice image: ' . $e->getMessage());
                $files['image'] = null;
            }
        }

        if ($options['needPDF'] ?? true) {
            try {
                $files['pdf'] = $this->pdfGenerator->generate($sale);
            } catch (\Exception $e) {
                Log::warning('Could not generate invoice PDF: ' . $e->getMessage());
                $files['pdf'] = null;
            }
        }

        return $files;
    }

    /**
     * بناء رسالة الفاتورة
     */
    private function buildInvoiceMessage(Sale $sale, ?string $customMessage = null, array $settings = []): string
    {
        if ($customMessage) {
            return $this->replacePlaceholders($customMessage, $sale);
        }

        $template = $settings['default_invoice_message']
            ?? "مرحباً {customer_name}،\n\nفاتورتك رقم {invoice_number} بمبلغ {total_amount}.\n\nشكراً لتعاملك معنا!";

        return $this->replacePlaceholders($template, $sale);
    }

    /**
     * بناء رسالة SMS
     */
    private function buildSMSMessage(Sale $sale, array $options): string
    {
        $template = "فاتورة #{$sale->invoiceNumber} بمبلغ {$sale->totalAmount}";
        return $this->replacePlaceholders($template, $sale);
    }

    /**
     * بناء رسالة Email
     */
    private function buildEmailMessage(Sale $sale, array $options): string
    {
        $subject = $options['customSubject'] ?? "فاتورتك رقم {$sale->invoiceNumber}";
        return "الموضوع: {$subject}\n\nالمبلغ الإجمالي: {$sale->totalAmount}";
    }

    /**
     * استبدال المتغيرات في الرسالة
     */
    private function replacePlaceholders(string $template, Sale $sale): string
    {
        $replacements = [
            '{company_name}' => $sale->company->name ?? 'الشركة',
            '{invoice_number}' => $sale->invoiceNumber,
            '{total_amount}' => number_format($sale->totalAmount, 2),
            '{customer_name}' => $sale->party->name ?? 'عميلنا العزيز',
            '{date}' => $sale->created_at->format('Y-m-d'),
            '{time}' => $sale->created_at->format('H:i'),
            '{branch_name}' => $sale->branch->name ?? '',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    /**
     * تسجيل الإشعار الفاشل
     */
    private function logFailedNotification(Sale $sale, string $channel, \Exception $e): void
    {
        InvoiceNotification::create([
            'sale_id' => $sale->id,
            'customer_id' => $sale->party_id,
            'branch_id' => $sale->branch_id,
            'user_id' => auth()->id() ?? 1,
            'channel' => $channel,
            'status' => 'failed',
            'error_message' => $e->getMessage(),
        ]);
    }

    /**
     * إعادة إرسال الإشعار الفاشل
     */
    public function resendFailed(InvoiceNotification $notification): array
    {
        if (!$notification->canRetry()) {
            return [
                'success' => false,
                'error' => 'Cannot retry this notification',
            ];
        }

        $sale = $notification->sale;
        $customer = $sale->party;
        $options = [];

        // إعادة الإرسال حسب القناة
        switch ($notification->channel) {
            case 'whatsapp':
                $files = $this->generateInvoiceFiles($sale, $options);
                $result = $this->sendViaWhatsApp($sale, $customer, $files, [
                    'sendImage' => strpos($notification->file_type, 'image') !== false,
                    'sendPDF' => strpos($notification->file_type, 'pdf') !== false,
                    'customMessage' => $notification->message_content,
                ]);

                // تحديث الإشعار
                $notification->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                    'retry_count' => $notification->retry_count + 1,
                    'error_message' => null,
                ]);

                return $result;

            default:
                return [
                    'success' => false,
                    'error' => 'Retry not implemented for this channel',
                ];
        }
    }
}