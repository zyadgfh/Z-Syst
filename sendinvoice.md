# 📱 نظام إشعارات واتساب والفواتير المتقدمة (WhatsApp & Advanced Invoicing System)

## 🎯 المتطلبات الأساسية

### 1. إرسال الفاتورة عبر واتساب بعد الحفظ

**التدفق المطلوب:**

```
حفظ الفاتورة → عرض نافذة تأكيد → خيارات متعددة:
├─ ✅ طباعة الفاتورة (حراري / A4 / PDF)
├─ ✅ إرسال عبر واتساب (صورة + PDF)
├─ ✅ إرسال عبر SMS
├─ ✅ إرسال عبر Email
├─ ✅ حفظ فقط (بدون إرسال)
└─ ✅ طباعة + إرسال (مزيج)
```

**واجهة المستخدم المطلوبة:**

```typescript
// Modal يظهر بعد حفظ الفاتورة
interface InvoiceActionModal {
  isOpen: boolean;
  invoice: Invoice;
  customer: Customer;
  
  actions: {
    print: {
      enabled: boolean;
      options: {
        thermal: boolean;      // طابعة حرارية (80mm)
        a4: boolean;           // طباعة A4
        pdf: boolean;          // تحميل PDF
        autoPrint: boolean;    // طباعة تلقائية
      };
    };
    
    whatsapp: {
      enabled: boolean;
      options: {
        sendImage: boolean;    // إرسال كصورة
        sendPDF: boolean;      // إرسال كـ PDF
        sendBoth: boolean;     // إرسال الاثنين
        includeMessage: boolean; // رسالة مخصصة
        customMessage?: string;
      };
    };
    
    sms: {
      enabled: boolean;
      options: {
        sendLink: boolean;     // رابط الفاتورة
        sendSummary: boolean;  // ملخص الفاتورة
        customMessage?: string;
      };
    };
    
    email: {
      enabled: boolean;
      options: {
        attachPDF: boolean;    // إرفاق PDF
        sendLink: boolean;     // رابط الفاتورة
        customSubject?: string;
        customMessage?: string;
      };
    };
  };
  
  // إعدادات افتراضية (من إعدادات المستخدم)
  defaultActions: {
    autoPrint: boolean;
    autoWhatsApp: boolean;
    autoSMS: boolean;
    autoEmail: boolean;
  };
}
```

**تصميم الواجهة (UI/UX):**

```tsx
// InvoiceActionModal.tsx
<Modal title="تم حفظ الفاتورة بنجاح" size="lg">
  <div className="space-y-6">
    {/* معلومات الفاتورة */}
    <div className="bg-green-50 border border-green-200 rounded-lg p-4">
      <div className="flex items-center gap-3">
        <CheckCircleIcon className="w-6 h-6 text-green-600" />
        <div>
          <p className="font-semibold text-green-900">
            فاتورة #{invoice.invoice_number}
          </p>
          <p className="text-sm text-green-700">
            المبلغ الإجمالي: {formatCurrency(invoice.total_amount)}
          </p>
        </div>
      </div>
    </div>

    {/* خيارات الطباعة */}
    <div className="space-y-3">
      <h3 className="font-semibold text-gray-900 flex items-center gap-2">
        <PrinterIcon className="w-5 h-5" />
        طباعة الفاتورة
      </h3>
      <div className="grid grid-cols-2 gap-3">
        <Checkbox
          label="طابعة حرارية (80mm)"
          checked={actions.print.options.thermal}
          onChange={(v) => updateAction('print.thermal', v)}
        />
        <Checkbox
          label="طباعة A4"
          checked={actions.print.options.a4}
          onChange={(v) => updateAction('print.a4', v)}
        />
        <Checkbox
          label="تحميل PDF"
          checked={actions.print.options.pdf}
          onChange={(v) => updateAction('print.pdf', v)}
        />
        <Checkbox
          label="طباعة تلقائية"
          checked={actions.print.options.autoPrint}
          onChange={(v) => updateAction('print.autoPrint', v)}
        />
      </div>
    </div>

    {/* خيارات واتساب */}
    <div className="space-y-3">
      <h3 className="font-semibold text-gray-900 flex items-center gap-2">
        <WhatsAppIcon className="w-5 h-5 text-green-500" />
        إرسال عبر واتساب
      </h3>
      <div className="bg-gray-50 rounded-lg p-3">
        <p className="text-sm text-gray-600 mb-2">
          إلى: {customer.phone} ({customer.name})
        </p>
        <div className="grid grid-cols-2 gap-3">
          <Checkbox
            label="إرسال كصورة"
            checked={actions.whatsapp.options.sendImage}
            onChange={(v) => updateAction('whatsapp.sendImage', v)}
          />
          <Checkbox
            label="إرسال كـ PDF"
            checked={actions.whatsapp.options.sendPDF}
            onChange={(v) => updateAction('whatsapp.sendPDF', v)}
          />
        </div>
        <div className="mt-3">
          <Checkbox
            label="إضافة رسالة مخصصة"
            checked={actions.whatsapp.options.includeMessage}
            onChange={(v) => updateAction('whatsapp.includeMessage', v)}
          />
          {actions.whatsapp.options.includeMessage && (
            <Textarea
              placeholder="شكراً لتسوقكم من صيدليتنا..."
              value={actions.whatsapp.options.customMessage}
              onChange={(v) => updateAction('whatsapp.customMessage', v)}
              className="mt-2"
            />
          )}
        </div>
      </div>
    </div>

    {/* خيارات SMS */}
    <div className="space-y-3">
      <h3 className="font-semibold text-gray-900 flex items-center gap-2">
        <MessageIcon className="w-5 h-5" />
        إرسال عبر SMS
      </h3>
      <div className="grid grid-cols-2 gap-3">
        <Checkbox
          label="إرسال رابط الفاتورة"
          checked={actions.sms.options.sendLink}
          onChange={(v) => updateAction('sms.sendLink', v)}
        />
        <Checkbox
          label="إرسال ملخص الفاتورة"
          checked={actions.sms.options.sendSummary}
          onChange={(v) => updateAction('sms.sendSummary', v)}
        />
      </div>
    </div>

    {/* خيارات Email */}
    <div className="space-y-3">
      <h3 className="font-semibold text-gray-900 flex items-center gap-2">
        <EmailIcon className="w-5 h-5" />
        إرسال عبر البريد الإلكتروني
      </h3>
      <div className="grid grid-cols-2 gap-3">
        <Checkbox
          label="إرفاق PDF"
          checked={actions.email.options.attachPDF}
          onChange={(v) => updateAction('email.attachPDF', v)}
        />
        <Checkbox
          label="إرسال رابط"
          checked={actions.email.options.sendLink}
          onChange={(v) => updateAction('email.sendLink', v)}
        />
      </div>
    </div>

    {/* حفظ كافتراضي */}
    <div className="border-t pt-4">
      <Checkbox
        label="حفظ هذه الإعدادات كافتراضية"
        checked={saveAsDefault}
        onChange={setSaveAsDefault}
      />
    </div>
  </div>

  {/* أزرار الإجراءات */}
  <ModalFooter>
    <Button variant="outline" onClick={onSkip}>
      تخطي
    </Button>
    <Button 
      variant="primary" 
      onClick={onExecute}
      loading={isExecuting}
    >
      تنفيذ
    </Button>
  </ModalFooter>
</Modal>
```

---

## 🔧 البنية الخلفية (Backend Architecture)

### 1. قاعدة البيانات

```php
// Migration: create_invoice_notifications_table
Schema::create('invoice_notifications', function (Blueprint $table) {
    $table->id();
    $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
    $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
    $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // من أرسل
    
    $table->enum('channel', ['whatsapp', 'sms', 'email', 'print']);
    $table->enum('status', ['pending', 'sent', 'delivered', 'failed', 'read']);
    
    $table->string('recipient')->nullable(); // رقم الهاتف أو البريد
    $table->string('message_id')->nullable(); // معرف الرسالة من المزود
    $table->text('message_content')->nullable(); // محتوى الرسالة
    
    $table->string('file_path')->nullable(); // مسار الملف المرفق
    $table->string('file_type')->nullable(); // image, pdf
    
    $table->json('metadata')->nullable(); // بيانات إضافية
    $table->timestamp('sent_at')->nullable();
    $table->timestamp('delivered_at')->nullable();
    $table->timestamp('read_at')->nullable();
    $table->text('error_message')->nullable();
    $table->integer('retry_count')->default(0);
    
    $table->timestamps();
    
    $table->index(['sale_id', 'channel']);
    $table->index(['status', 'created_at']);
});

// Migration: add_notification_preferences_to_customers_table
Schema::table('customers', function (Blueprint $table) {
    $table->json('notification_preferences')->nullable()->after('loyalty_points');
    // {
    //   "whatsapp": true,
    //   "sms": false,
    //   "email": true,
    //   "preferred_language": "ar"
    // }
});

// Migration: add_invoice_settings_to_companies_table
Schema::table('companies', function (Blueprint $table) {
    $table->json('invoice_notification_settings')->nullable();
    // {
    //   "whatsapp_enabled": true,
    //   "whatsapp_api_provider": "twilio", // twilio, 360dialog, custom
    //   "whatsapp_api_key": "...",
    //   "whatsapp_business_number": "...",
    //   "sms_provider": "twilio",
    //   "sms_api_key": "...",
    //   "default_invoice_message": "شكراً لتسوقكم من {company_name}. فاتورتكم رقم {invoice_number} بمبلغ {total_amount}",
    //   "auto_send_invoice": true,
    //   "send_as_image": true,
    //   "send_as_pdf": false
    // }
});
```

### 2. النماذج (Models)

```php
// app/Models/InvoiceNotification.php
class InvoiceNotification extends Model
{
    protected $fillable = [
        'sale_id',
        'customer_id',
        'branch_id',
        'user_id',
        'channel',
        'status',
        'recipient',
        'message_id',
        'message_content',
        'file_path',
        'file_type',
        'metadata',
        'sent_at',
        'delivered_at',
        'read_at',
        'error_message',
        'retry_count',
    ];

    protected $casts = [
        'metadata' => 'array',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    // العلاقات
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function customer()
    {
        return $this->belongsTo(Party::class, 'customer_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeByChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }
}
```

### 3. الخدمات (Services)

```php
// app/Services/Invoice/InvoiceNotificationService.php
class InvoiceNotificationService
{
    private WhatsAppService $whatsappService;
    private SMSService $smsService;
    private EmailService $emailService;
    private InvoicePDFGenerator $pdfGenerator;
    private InvoiceImageGenerator $imageGenerator;

    public function __construct(
        WhatsAppService $whatsappService,
        SMSService $smsService,
        EmailService $emailService,
        InvoicePDFGenerator $pdfGenerator,
        InvoiceImageGenerator $imageGenerator
    ) {
        $this->whatsappService = $whatsappService;
        $this->smsService = $smsService;
        $this->emailService = $emailService;
        $this->pdfGenerator = $pdfGenerator;
        $this->imageGenerator = $imageGenerator;
    }

    /**
     * إرسال الفاتورة عبر قنوات متعددة
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
        Party $customer,
        array $files,
        array $options
    ): array {
        $company = $sale->company;
        $settings = $company->invoice_notification_settings ?? [];

        // بناء الرسالة
        $message = $this->buildInvoiceMessage($sale, $options['customMessage'] ?? null);

        // إرسال الملفات
        $sentFiles = [];

        if ($options['sendImage'] ?? $settings['send_as_image'] ?? true) {
            $imagePath = $files['image'];
            $this->whatsappService->sendInvoice([
                'phone' => $customer->phone,
                'name' => $customer->name
            ], $imagePath, $sale->id, $message);
            $sentFiles[] = 'image';
        }

        if ($options['sendPDF'] ?? $settings['send_as_pdf'] ?? false) {
            $pdfPath = $files['pdf'];
            $this->whatsappService->sendPdfDocument($customer->phone, $pdfPath, $sale->id);
            $sentFiles[] = 'pdf';
        }

        // تسجيل الإشعار
        $notification = InvoiceNotification::create([
            'sale_id' => $sale->id,
            'customer_id' => $customer->id,
            'branch_id' => $sale->branch_id,
            'user_id' => auth()->id(),
            'channel' => 'whatsapp',
            'status' => 'sent',
            'recipient' => $customer->phone,
            'message_content' => $message,
            'file_type' => implode(',', $sentFiles),
            'sent_at' => now(),
        ]);

        return [
            'notification_id' => $notification->id,
            'files_sent' => $sentFiles,
        ];
    }

    /**
     * إرسال عبر SMS
     */
    private function sendViaSMS(
        Sale $sale,
        Party $customer,
        array $options
    ): array {
        $message = $this->buildSMSMessage($sale, $options);

        $response = $this->smsService->send(
            recipient: $customer->phone,
            message: $message
        );

        $notification = InvoiceNotification::create([
            'sale_id' => $sale->id,
            'customer_id' => $customer->id,
            'branch_id' => $sale->branch_id,
            'user_id' => auth()->id(),
            'channel' => 'sms',
            'status' => 'sent',
            'recipient' => $customer->phone,
            'message_content' => $message,
            'message_id' => $response['message_id'] ?? null,
            'sent_at' => now(),
        ]);

        return [
            'notification_id' => $notification->id,
            'message_id' => $response['message_id'] ?? null,
        ];
    }

    /**
     * إرسال عبر Email
     */
    private function sendViaEmail(
        Sale $sale,
        Party $customer,
        array $files,
        array $options
    ): array {
        $subject = $options['customSubject'] ?? "فاتورتك رقم {$sale->invoiceNumber}";
        $message = $this->buildEmailMessage($sale, $options);

        $attachments = [];
        if ($options['attachPDF'] ?? true) {
            $attachments[] = [
                'path' => $files['pdf'],
                'name' => "invoice-{$sale->invoiceNumber}.pdf",
            ];
        }

        $this->emailService->send(
            to: $customer->email,
            subject: $subject,
            body: $message,
            attachments: $attachments
        );

        $notification = InvoiceNotification::create([
            'sale_id' => $sale->id,
            'customer_id' => $customer->id,
            'branch_id' => $sale->branch_id,
            'user_id' => auth()->id(),
            'channel' => 'email',
            'status' => 'sent',
            'recipient' => $customer->email,
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

        // إرسال إلى الطابعة (عبر Queue)
        PrintInvoiceJob::dispatch($sale, $pdfPath, $options);

        return [
            'pdf_path' => $pdfPath,
            'queued' => true,
        ];
    }

    /**
     * توليد ملفات الفاتورة
     */
    private function generateInvoiceFiles(Sale $sale, array $options): array
    {
        $files = [];

        if ($options['needImage'] ?? true) {
            $files['image'] = $this->imageGenerator->generate($sale);
        }

        if ($options['needPDF'] ?? true) {
            $files['pdf'] = $this->pdfGenerator->generate($sale);
        }

        return $files;
    }

    /**
     * بناء رسالة الفاتورة
     */
    private function buildInvoiceMessage(Sale $sale, ?string $customMessage = null): string
    {
        if ($customMessage) {
            return $this->replacePlaceholders($customMessage, $sale);
        }

        $company = $sale->company;
        $settings = $company->invoice_notification_settings ?? [];
        $template = $settings['default_invoice_message']
            ?? "شكراً لتسوقكم من {company_name}.\n\nفاتورتكم رقم {invoice_number}\nالمبلغ الإجمالي: {total_amount}\n\nنتمنى لكم الصحة والعافية.";

        return $this->replacePlaceholders($template, $sale);
    }

    /**
     * بناء رسالة SMS
     */
    private function buildSMSMessage(Sale $sale, array $options): string
    {
        $template = "فاتورة #{$sale->invoiceNumber} بمبلغ {$sale->totalAmount} ج.م";
        return $this->replacePlaceholders($template, $sale);
    }

    /**
     * بناء رسالة Email
     */
    private function buildEmailMessage(Sale $sale, array $options): string
    {
        return view('emails.invoice', compact('sale'))->render();
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
            'user_id' => auth()->id(),
            'channel' => $channel,
            'status' => 'failed',
            'error_message' => $e->getMessage(),
        ]);
    }
}
```

### 4. WhatsApp Service

```php
// app/Services/WhatsApp/WhatsAppService.php - موجود مسبقاً ويجب تحسينه
```

### 5. Invoice PDF & Image Generators

```php
// app/Services/Invoice/InvoicePDFGenerator.php
class InvoicePDFGenerator
{
    public function generate(Sale $sale): string
    {
        $html = view('invoices.sale-print', [
            'sale' => $sale,
        ])->render();

        $filename = "invoices/invoice-{$sale->invoiceNumber}.pdf";
        $path = storage_path("app/public/{$filename}");

        if (!file_exists(storage_path('app/public/invoices'))) {
            mkdir(storage_path('app/public/invoices'), 0755, true);
        }

        // استخدام DOMPDF إذا كان متاحاً
        if (class_exists(\Barryvdh\DomPDF\Facade::class)) {
            \Barryvdh\DomPDF\Facade::loadHTML($html)->save($path);
        } else {
            file_put_contents($path, $html);
        }

        return $filename;
    }

    public function generateForPrint(Sale $sale, array $options): string
    {
        return $this->generate($sale);
    }
}

// app/Services/Invoice/InvoiceImageGenerator.php - موجود مسبقاً ويجب تحسينه
```

---

## 🎁 الميزات الإضافية المقترحة

### 1. QR Code على الفاتورة

```php
// إضافة QR Code للفاتورة للتحقق
class InvoiceQRCodeGenerator
{
    public function generate(Sale $sale): string
    {
        $url = route('invoices.verify', ['id' => $sale->id, 'token' => Str::random(32)]);

        $qrCode = QrCode::format('png')
            ->size(300)
            ->margin(10)
            ->generate($url);

        $filename = "invoices/qr-{$sale->invoiceNumber}.png";
        Storage::disk('public')->put($filename, $qrCode);

        return $filename;
    }
}
```

### 2. رابط تتبع الفاتورة

```php
// صفحة عامة لتتبع الفاتورة
// Route::get('/invoices/{id}/track/{token}', [InvoiceController::class, 'track'])->name('invoices.track');
```

### 3. سجل الإشعارات المرسلة

```php
// صفحة عرض سجل الإشعارات
// Route::get('/sales/{sale}/notifications', [InvoiceNotificationController::class, 'index']);
```

### 4. إعدادات الإشعارات لكل عميل

```php
// صفحة إعدادات الإشعارات للعميل
// Route::get('/customers/{customer}/notification-preferences', [CustomerNotificationController::class, 'edit']);
```

### 5. قوالب الرسائل المخصصة

```php
// Migration: create_message_templates_table
Schema::create('message_templates', function (Blueprint $table) {
    $table->id();
    $table->foreignId('company_id')->constrained()->cascadeOnDelete();
    $table->string('name');
    $table->enum('channel', ['whatsapp', 'sms', 'email']);
    $table->text('template');
    $table->json('variables')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
```

### 6. إشعارات تلقائية

```php
// Job: إرسال الفاتورة تلقائياً بعد الحفظ
class AutoSendInvoiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Sale $sale,
        public array $channels
    ) {}

    public function handle(InvoiceNotificationService $service): void
    {
        $service->sendInvoice($this->sale, $this->channels);
    }
}
```

### 7. إحصائيات الإشعارات

```php
// Dashboard widget: إحصائيات الإشعارات
class NotificationStatsWidget
{
    public function render(): array
    {
        $stats = InvoiceNotification::query()
            ->select('channel', 'status', DB::raw('count(*) as count'))
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('channel', 'status')
            ->get();

        return [
            'total_sent' => $stats->where('status', 'sent')->sum('count'),
            'total_delivered' => $stats->where('status', 'delivered')->sum('count'),
            'total_failed' => $stats->where('status', 'failed')->count(),
            'by_channel' => $stats->groupBy('channel')->map->sum('count'),
        ];
    }
}
```

---

## 📋 ملخص الملفات المطلوب إنشاؤها/تعديلها

### ملفات جديدة:
1. `app/Models/InvoiceNotification.php` - نموذج الإشعارات
2. `app/Services/Invoice/InvoiceNotificationService.php` - الخدمة الرئيسية
3. `app/Http/Controllers/API/InvoiceNotificationController.php` - وحدة التحكم
4. `app/Http/Controllers/API/MessageTemplateController.php` - قوالب الرسائل
5. `app/Jobs/AutoSendInvoiceJob.php` - Job للإرسال التلقائي
6. `app/Jobs/PrintInvoiceJob.php` - Job للطباعة
7. Migrations للمخططات

### ملفات معدلة:
1. `app/Services/WhatsAppService.php` - تحسين لإرسال PDF
2. `app/Services/InvoiceImageService.php` - تحسين لتوليد الصور
3. `app/Models/Party.php` - إضافة علاقة الإشعارات
4. `app/Models/Sale.php` - إضافة علاقة الإشعارات

---

**تم تنفيذ هذا القسم بالكامل.**