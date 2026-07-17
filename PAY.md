# 🎭 الدور (Role / Persona)

أنت **مهندس برمجيات أول متخصص في أنظمة الدفع (Principal Payment Systems Engineer)** وخبير في:
- **بوابات الدفع المصرية (Egyptian Payment Gateways)**: Paymob, Fawry, Tap, HyperPay, Accept
- **محافظ الهاتف المحمول (Mobile Money)**: Vodafone Cash, Orange Cash, Etisalat Cash, We Pay
- **شبكة الدفع الفوري (IPN)**: InstaPay (Instant Payment Network — البنك المركزي المصري)
- **أنظمة الفوترة SaaS**: اشتراكات متكررة، فواتير، تسويات
- **Laravel 11+** مع Queues, Events, Webhooks, Idempotency
- **Next.js 14+** مع React, TypeScript, TailwindCSS
- **الأمان والامتثال**: PCI-DSS, PSD2-like, Egyptian Central Bank regulations
- **Webhooks reliability**: Retries, Idempotency, Signature verification

أنت تعمل بمعايير **Stripe, Paymob, Fawry** من حيث الموثوقية والأمان.

---

# 📋 سياق المشروع (Project Context)

اسم المشروع: **Z-Syst Pharmacy Management SaaS**
التقنية: **Laravel 11 (Backend) + Next.js 14 (Frontend)**
الحالة الحالية: نظام SaaS لإدارة الصيدليات يستهدف السوق المصري/الشرق الأوسط.

## 🎯 الهدف:
إضافة **نظام دفع إلكتروني متكامل** يدعم:
1. ✅ **Vodafone Cash** (محفظة محمول)
2. ✅ **Orange Cash** (محفظة محمول)
3. ✅ **Etisalat Cash** (محفظة محمول)
4. ✅ **InstaPay** (شبكة الدفع الفوري — IPN)

## 📌 حالات الاستخدام (Use Cases):

### أ) مدفوعات العملاء (B2C) — في نقطة البيع (POS):
- عميل يدفع فاتورة شراء من الصيدلية عبر فودافون كاش/أورانج كاش/اتصالات كاش/إنستاباي
- يجب أن تكون العملية فورية (< 5 ثواني)
- يجب تحديث المخزون والمبيعات تلقائياً

### ب) اشتراكات SaaS (B2B) — أصحاب الصيدليات:
- صاحب الصيدلية يدفع اشتراكه الشهري/السنوي للمنصة
- دفع متكرر (Recurring billing)
- فشل الدفع → تعليق الحساب + إشعارات

### ج) الدفع الجزئي (Partial Payment):
- عميل يدفع جزءاً نقداً + جزءاً عبر المحفظة
- عميل يدفع جزءاً بالتأمين + جزءاً بالمحفظة

---

# 🏗️ البنية التقنية المقترحة (Recommended Architecture)

## 🥇 الخيار الأول (الموصى به): **Paymob** كـ Payment Aggregator

**لماذا Paymob؟**
- ✅ يدعم كل المحافظ (Vodafone Cash, Orange Cash, Etisalat Cash, We Pay)
- ✅ يدعم InstaPay
- ✅ يدعم البطاقات (Visa, Mastercard, Meeza)
- ✅ يدعم الدفع المتكرر (Recurring)
- ✅ وثائق عربية + دعم فني مصري
- ✅ رسوم معقولة (~2.75% + 2 EGP لكل عملية)
- ✅ لوحة تحكم قوية + تقارير
- ✅ Webhooks موثوقة
- ✅ متوافق مع البنك المركزي المصري

**البدائل (إذا لم يكن Payboy مناسباً):**
- **Fawry**: منتشر جداً في مصر، لكن API أقدم
- **Tap Payments**: يدعم الخليج + مصر
- **HyperPay**: قوي تقنياً لكن أغلى
- **Accept (by Paymob)**: نسخة مبسطة

## 🥈 الخيار الثاني: التكامل المباشر مع كل مزود
**غير موصى به** — معقد جداً، كل API مختلف، صيانة عالية.

---

# 📦 الوحدات المطلوبة (Required Modules)

## 🔴 الوحدة 1: Payment Gateway Abstraction Layer (طبقة التجريد)

> 📌 **حرج**: يجب أن تكون البنية مرنة لتغيير البوابة لاحقاً دون إعادة كتابة الكود.

### البنية (Architecture):

```
app/
├── Services/
│   └── Payment/
│       ├── PaymentGatewayInterface.php          (Contract)
│       ├── PaymobGateway.php                    (Paymob implementation)
│       ├── FawryGateway.php                     (Fawry implementation - future)
│       ├── PaymentGatewayFactory.php            (Factory pattern)
│       ├── PaymentService.php                   (Orchestrator)
│       ├── PaymentMethodResolver.php            (يحدد الطريقة بناءً على السياق)
│       └── DTOs/
│           ├── PaymentRequestDTO.php
│           ├── PaymentResponseDTO.php
│           ├── RefundRequestDTO.php
│           └── WebhookPayloadDTO.php
├── Models/
│   ├── Payment.php                              (المعاملات)
│   ├── PaymentMethod.php                        (طرق الدفع المفعلة)
│   ├── PaymentGatewayLog.php                    (سجل API calls)
│   ├── PaymentRefund.php                        (المستردات)
│   └── PaymentReconciliation.php                (التسويات)
├── Events/
│   ├── PaymentCreated.php
│   ├── PaymentSucceeded.php
│   ├── PaymentFailed.php
│   ├── PaymentRefunded.php
│   └── PaymentWebhookReceived.php
├── Listeners/
│   ├── UpdateSaleStatusOnPayment.php
│   ├── SendPaymentReceiptNotification.php
│   ├── UpdateSubscriptionOnPayment.php
│   └── LogPaymentActivity.php
├── Jobs/
│   ├── ProcessPaymentWebhook.php
│   ├── VerifyPaymentStatus.php
│   ├── RetryFailedPayment.php
│   └── ReconcilePayments.php
├── Exceptions/
│   ├── PaymentGatewayException.php
│   ├── InsufficientBalanceException.php
│   ├── PaymentDeclinedException.php
│   └── IdempotencyException.php
└── Http/
    ├── Controllers/
    │   ├── PaymentController.php                (API endpoints)
    │   └── PaymentWebhookController.php         (Webhooks handler)
    └── Requests/
        ├── InitiatePaymentRequest.php
        ├── VerifyPaymentRequest.php
        └── RefundPaymentRequest.php
```

### PaymentGatewayInterface (Contract):

```php
interface PaymentGatewayInterface
{
    public function initiatePayment(PaymentRequestDTO $request): PaymentResponseDTO;
    public function verifyPayment(string $transactionId): PaymentResponseDTO;
    public function refund(RefundRequestDTO $request): PaymentResponseDTO;
    public function handleWebhook(array $payload): WebhookPayloadDTO;
    public function verifyWebhookSignature(string $signature, string $payload): bool;
    public function getSupportedMethods(): array;
    public function isAvailable(): bool;
}
```

---

## 🔴 الوحدة 2: قاعدة البيانات (Database Schema)

### جدول `payments`:

```php
Schema::create('payments', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique();
    
    // Polymorphic relation (sale, subscription, invoice, etc.)
    $table->morphs('payable');
    
    // Gateway info
    $table->string('gateway');                    // paymob, fawry, etc.
    $table->string('gateway_transaction_id')->nullable()->unique();
    $table->string('gateway_order_id')->nullable();
    $table->string('merchant_reference')->unique(); // Idempotency key
    
    // Payment method
    $table->string('payment_method');             // vodafone_cash, orange_cash, etisalat_cash, instapay, card, cash
    $table->string('payment_method_details')->nullable(); // JSON: phone number, etc.
    
    // Amounts
    $table->decimal('amount', 12, 2);
    $table->decimal('fees', 12, 2)->default(0);
    $table->decimal('net_amount', 12, 2);
    $table->string('currency', 3)->default('EGP');
    
    // Status
    $table->enum('status', [
        'pending', 'initiated', 'processing', 
        'succeeded', 'failed', 'declined', 
        'refunded', 'partially_refunded', 
        'expired', 'cancelled'
    ])->default('pending');
    
    $table->enum('payment_type', ['one_time', 'recurring', 'installment'])->default('one_time');
    
    // Customer info
    $table->foreignId('customer_id')->nullable()->constrained('customers');
    $table->foreignId('user_id')->nullable()->constrained('users');
    $table->foreignId('branch_id')->nullable()->constrained('branches');
    $table->foreignId('company_id')->constrained('companies');
    
    // Metadata
    $table->json('metadata')->nullable();
    $table->json('gateway_response')->nullable();
    $table->text('failure_reason')->nullable();
    $table->integer('retry_count')->default(0);
    
    // Timestamps
    $table->timestamp('initiated_at')->nullable();
    $table->timestamp('succeeded_at')->nullable();
    $table->timestamp('failed_at')->nullable();
    $table->timestamp('refunded_at')->nullable();
    $table->timestamps();
    $table->softDeletes();
    
    // Indexes
    $table->index(['company_id', 'status']);
    $table->index(['payable_type', 'payable_id']);
    $table->index('created_at');
});
```

### جدول `payment_methods`:

```php
Schema::create('payment_methods', function (Blueprint $table) {
    $table->id();
    $table->foreignId('company_id')->constrained('companies');
    $table->string('name');                         // Vodafone Cash
    $table->string('code')->unique();               // vodafone_cash
    $table->string('gateway');                      // paymob
    $table->string('gateway_integration_id');       // Paymob integration ID
    $table->boolean('is_active')->default(true);
    $table->boolean('is_pos_enabled')->default(true);  // متاح في POS
    $table->boolean('is_online_enabled')->default(true); // متاح أونلاين
    $table->decimal('min_amount', 12, 2)->default(0);
    $table->decimal('max_amount', 12, 2)->default(50000);
    $table->json('configuration')->nullable();      // Gateway-specific config
    $table->integer('sort_order')->default(0);
    $table->timestamps();
});
```

### جدول `payment_gateway_logs`:

```php
Schema::create('payment_gateway_logs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('payment_id')->nullable()->constrained('payments');
    $table->string('gateway');
    $table->string('endpoint');
    $table->string('method');                       // POST, GET
    $table->json('request_headers')->nullable();
    $table->json('request_body')->nullable();
    $table->json('response_headers')->nullable();
    $table->json('response_body')->nullable();
    $table->integer('response_status');
    $table->float('response_time_ms');
    $table->boolean('success');
    $table->text('error_message')->nullable();
    $table->string('ip_address')->nullable();
    $table->timestamps();
});
```

### جدول `payment_refunds`:

```php
Schema::create('payment_refunds', function (Blueprint $table) {
    $table->id();
    $table->foreignId('payment_id')->constrained('payments');
    $table->string('refund_number')->unique();
    $table->decimal('amount', 12, 2);
    $table->enum('status', ['pending', 'processing', 'succeeded', 'failed'])->default('pending');
    $table->string('gateway_refund_id')->nullable();
    $table->text('reason')->nullable();
    $table->foreignId('requested_by')->constrained('users');
    $table->foreignId('approved_by')->nullable()->constrained('users');
    $table->timestamp('refunded_at')->nullable();
    $table->timestamps();
});
```

---

## 🔴 الوحدة 3: PaymentService (المنسق الرئيسي)

### PaymentService.php:

```php
class PaymentService
{
    public function __construct(
        private PaymentGatewayFactory $gatewayFactory,
        private PaymentRepository $repository,
        private Dispatcher $events
    ) {}

    /**
     * بدء عملية دفع جديدة
     * Idempotent — نفس merchant_reference يعيد نفس النتيجة
     */
    public function initiatePayment(PaymentRequestDTO $request): PaymentResponseDTO
    {
        // 1. Idempotency check
        $existing = $this->repository->findByMerchantReference($request->merchantReference);
        if ($existing) {
            return PaymentResponseDTO::fromPayment($existing);
        }

        // 2. Validate payment method is active for this company
        $this->validatePaymentMethod($request->companyId, $request->paymentMethod);

        // 3. Validate amount limits
        $this->validateAmountLimits($request);

        // 4. Create payment record (pending)
        $payment = $this->repository->create([
            'uuid' => Str::uuid(),
            'payable_type' => $request->payableType,
            'payable_id' => $request->payableId,
            'gateway' => $request->gateway,
            'merchant_reference' => $request->merchantReference,
            'payment_method' => $request->paymentMethod,
            'payment_method_details' => $request->paymentMethodDetails,
            'amount' => $request->amount,
            'currency' => $request->currency,
            'status' => 'initiated',
            'company_id' => $request->companyId,
            'branch_id' => $request->branchId,
            'customer_id' => $request->customerId,
            'user_id' => $request->userId,
            'initiated_at' => now(),
        ]);

        // 5. Call gateway
        try {
            $gateway = $this->gatewayFactory->make($request->gateway);
            $response = $gateway->initiatePayment($request);

            $payment->update([
                'gateway_transaction_id' => $response->transactionId,
                'gateway_order_id' => $response->orderId,
                'status' => 'processing',
                'gateway_response' => $response->raw,
            ]);

            event(new PaymentCreated($payment));

            return PaymentResponseDTO::fromPayment($payment);

        } catch (PaymentGatewayException $e) {
            $payment->update([
                'status' => 'failed',
                'failure_reason' => $e->getMessage(),
                'failed_at' => now(),
            ]);

            event(new PaymentFailed($payment, $e));
            throw $e;
        }
    }

    /**
     * التحقق من حالة الدفع (Polling)
     */
    public function verifyPayment(string $paymentUuid): PaymentResponseDTO
    {
        $payment = $this->repository->findByUuid($paymentUuid);
        
        if (!$payment) {
            throw new PaymentNotFoundException();
        }

        if ($payment->isFinalStatus()) {
            return PaymentResponseDTO::fromPayment($payment);
        }

        $gateway = $this->gatewayFactory->make($payment->gateway);
        $response = $gateway->verifyPayment($payment->gateway_transaction_id);

        $this->handleGatewayResponse($payment, $response);

        return PaymentResponseDTO::fromPayment($payment->fresh());
    }

    /**
     * معالجة Webhook من البوابة
     */
    public function handleWebhook(string $gateway, array $payload): void
    {
        $gatewayInstance = $this->gatewayFactory->make($gateway);

        // 1. Verify signature
        $signature = request()->header('X-HMAC-Signature');
        if (!$gatewayInstance->verifyWebhookSignature($signature, json_encode($payload))) {
            throw new PaymentGatewayException('Invalid webhook signature');
        }

        // 2. Parse payload
        $dto = $gatewayInstance->handleWebhook($payload);

        // 3. Find payment
        $payment = $this->repository->findByGatewayTransactionId($dto->transactionId);
        if (!$payment) {
            Log::warning('Webhook received for unknown payment', ['transaction_id' => $dto->transactionId]);
            return;
        }

        // 4. Idempotency — لا معالجة مرتين
        if ($payment->status === $dto->status) {
            return;
        }

        // 5. Update payment
        $this->updatePaymentFromWebhook($payment, $dto);

        // 6. Fire events
        event(new PaymentWebhookReceived($payment, $dto));
        
        if ($dto->status === 'succeeded') {
            event(new PaymentSucceeded($payment));
        } elseif ($dto->status === 'failed') {
            event(new PaymentFailed($payment));
        }
    }

    /**
     * استرداد مبلغ
     */
    public function refund(RefundRequestDTO $request): PaymentResponseDTO
    {
        $payment = $this->repository->findByUuid($request->paymentUuid);

        if (!$payment->canBeRefunded()) {
            throw new PaymentException('Payment cannot be refunded');
        }

        if ($request->amount > $payment->refundableAmount()) {
            throw new PaymentException('Refund amount exceeds refundable amount');
        }

        $refund = PaymentRefund::create([
            'payment_id' => $payment->id,
            'refund_number' => $this->generateRefundNumber(),
            'amount' => $request->amount,
            'status' => 'pending',
            'reason' => $request->reason,
            'requested_by' => $request->requestedBy,
        ]);

        try {
            $gateway = $this->gatewayFactory->make($payment->gateway);
            $response = $gateway->refund($request);

            $refund->update([
                'status' => 'succeeded',
                'gateway_refund_id' => $response->refundId,
                'refunded_at' => now(),
            ]);

            $payment->updateStatus(
                $request->amount === $payment->amount ? 'refunded' : 'partially_refunded'
            );

            event(new PaymentRefunded($payment, $refund));

            return PaymentResponseDTO::fromPayment($payment->fresh());

        } catch (PaymentGatewayException $e) {
            $refund->update([
                'status' => 'failed',
            ]);
            throw $e;
        }
    }
}
```

---

## 🔴 الوحدة 4: Paymob Gateway Implementation

### PaymobGateway.php:

```php
class PaymobGateway implements PaymentGatewayInterface
{
    private const API_BASE = 'https://accept.paymob.com/api';
    
    public function __construct(
        private string $apiKey,
        private int $merchantId,
        private string $hmacSecret,
        private array $integrationIds  // [vodafone => 123, orange => 124, ...]
    ) {}

    public function initiatePayment(PaymentRequestDTO $request): PaymentResponseDTO
    {
        // 1. Auth — احصل على token
        $authToken = $this->authenticate();

        // 2. Create order
        $order = $this->createOrder($authToken, $request);

        // 3. Get payment key
        $paymentKey = $this->getPaymentKey($authToken, $order['id'], $request);

        // 4. Return response
        return new PaymentResponseDTO(
            transactionId: $order['id'],
            orderId: $order['id'],
            status: 'processing',
            redirectUrl: null,  // Mobile wallets don't redirect
            rawData: $paymentKey,
        );
    }

    public function verifyPayment(string $transactionId): PaymentResponseDTO
    {
        $authToken = $this->authenticate();
        $transaction = $this->getTransaction($authToken, $transactionId);

        return new PaymentResponseDTO(
            transactionId: $transaction['id'],
            orderId: $transaction['order']['id'],
            status: $this->mapStatus($transaction['success'], $transaction['data']),
            rawData: $transaction,
        );
    }

    public function handleWebhook(array $payload): WebhookPayloadDTO
    {
        return new WebhookPayloadDTO(
            transactionId: $payload['obj']['id'],
            orderId: $payload['obj']['order']['id'],
            status: $this->mapStatus($payload['obj']['success'], $payload['obj']['data']),
            amount: $payload['obj']['amount_cents'] / 100,
            currency: $payload['obj']['currency'],
            failureReason: $payload['obj']['data']['message'] ?? null,
            raw: $payload,
        );
    }

    public function verifyWebhookSignature(string $signature, string $payload): bool
    {
        $computedHmac = hash_hmac('sha512', $payload, $this->hmacSecret);
        return hash_equals($computedHmac, $signature);
    }

    public function getSupportedMethods(): array
    {
        return [
            'vodafone_cash' => ['name' => 'Vodafone Cash', 'integration_id' => $this->integrationIds['vodafone']],
            'orange_cash' => ['name' => 'Orange Cash', 'integration_id' => $this->integrationIds['orange']],
            'etisalat_cash' => ['name' => 'Etisalat Cash', 'integration_id' => $this->integrationIds['etisalat']],
            'we_pay' => ['name' => 'We Pay', 'integration_id' => $this->integrationIds['we']],
            'instapay' => ['name' => 'InstaPay', 'integration_id' => $this->integrationIds['instapay']],
        ];
    }

    // ... باقي الدوال الخاصة (authenticate, createOrder, getPaymentKey, etc.)
}
```

---

## 🔴 الوحدة 5: Webhooks Controller

### PaymentWebhookController.php:

```php
class PaymentWebhookController extends Controller
{
    public function __construct(
        private PaymentService $paymentService
    ) {}

    public function handle(string $gateway, Request $request)
    {
        // 1. Log the raw request (للـ debugging و audit)
        PaymentGatewayLog::create([
            'gateway' => $gateway,
            'endpoint' => $request->fullUrl(),
            'method' => $request->method(),
            'request_headers' => $request->headers->all(),
            'request_body' => $request->all(),
            'response_status' => 200,
            'success' => true,
            'ip_address' => $request->ip(),
        ]);

        // 2. Process in queue (لا تحجب البوابة)
        ProcessPaymentWebhook::dispatch($gateway, $request->all())
            ->onQueue('payments-webhooks')
            ->retry([30, 60, 300]); // Retry after 30s, 1m, 5m

        // 3. Return 200 immediately (البوابة تتوقع استجابة سريعة)
        return response()->json(['status' => 'received'], 200);
    }
}
```

### ProcessPaymentWebhook Job:

```php
class ProcessPaymentWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;
    public $backoff = [30, 60, 300, 900]; // 30s, 1m, 5m, 15m
    public $maxExceptions = 3;

    public function __construct(
        public string $gateway,
        public array $payload
    ) {}

    public function handle(PaymentService $paymentService): void
    {
        $paymentService->handleWebhook($this->gateway, $this->payload);
    }

    public function failed(Throwable $exception): void
    {
        // أرسل تنبيهاً للفريق
        Notification::route('slack', config('services.slack.webhook'))
            ->notify(new PaymentWebhookFailed($this->gateway, $this->payload, $exception));
        
        Log::critical('Payment webhook processing failed', [
            'gateway' => $this->gateway,
            'payload' => $this->payload,
            'error' => $exception->getMessage(),
        ]);
    }
}
```

---

## 🔴 الوحدة 6: Routes & Endpoints

### routes/api.php:

```php
Route::prefix('v1')->middleware(['auth:sanctum', 'tenant'])->group(function () {
    
    // Payment endpoints
    Route::prefix('payments')->group(function () {
        Route::post('/initiate', [PaymentController::class, 'initiate']);
        Route::post('/verify', [PaymentController::class, 'verify']);
        Route::post('/refund', [PaymentController::class, 'refund']);
        Route::get('/{uuid}', [PaymentController::class, 'show']);
        Route::get('/', [PaymentController::class, 'index']);
        Route::get('/methods', [PaymentController::class, 'availableMethods']);
    });

    // POS-specific payment endpoints
    Route::prefix('pos/payments')->group(function () {
        Route::post('/quick-pay', [PosPaymentController::class, 'quickPay']);
        Route::post('/split-payment', [PosPaymentController::class, 'splitPayment']);
    });
});

// Webhooks (بدون مصادقة — يتم التحقق عبر HMAC)
Route::prefix('webhooks/payments')->group(function () {
    Route::post('/{gateway}', [PaymentWebhookController::class, 'handle'])
        ->middleware('throttle:webhooks');
});
```

---

## 🔴 الوحدة 7: الواجهة الأمامية (Frontend)

### مكونات React المطلوبة:

```
components/
├── payments/
│   ├── PaymentMethodSelector.tsx       (اختيار طريقة الدفع)
│   ├── MobileWalletForm.tsx            (إدخال رقم المحفظة)
│   ├── InstaPayForm.tsx                (QR code + reference number)
│   ├── PaymentStatusIndicator.tsx      (pending/processing/success/failed)
│   ├── PaymentReceipt.tsx              (إيصال الدفع)
│   ├── RefundModal.tsx                 (نافذة الاسترداد)
│   ├── PaymentHistory.tsx              (سجل المعاملات)
│   └── SplitPaymentModal.tsx           (الدفع الجزئي)
├── pos/
│   ├── PosPaymentScreen.tsx            (شاشة الدفع في POS)
│   └── QuickPayButton.tsx              (زر الدفع السريع)
└── settings/
    ├── PaymentMethodsSettings.tsx      (إعدادات طرق الدفع)
    └── GatewayConfiguration.tsx        (إعدادات البوابة)
```

### MobileWalletForm.tsx:

```tsx
'use client';

import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { toast } from 'sonner';
import { Loader2, CheckCircle2, XCircle, Phone } from 'lucide-react';

const mobileWalletSchema = z.object({
  phoneNumber: z
    .string()
    .regex(/^01[0-9]{9}$/, 'رقم هاتف غير صحيح')
    .refine((phone) => {
      const prefix = phone.substring(0, 4);
      const validPrefixes = {
        vodafone_cash: ['0100', '0101', '0102', '0106', '0109'],
        orange_cash: ['0111', '0112', '0114', '0110', '0115'],
        etisalat_cash: ['0120', '0121', '0122', '0123', '0128'],
        we_pay: ['0150', '0151', '0155', '0156'],
      };
      return validPrefixes[paymentMethod]?.some(p => phone.startsWith(p));
    }, 'رقم الهاتف لا يتطابق مع المحفظة المختارة'),
  amount: z.number().min(1).max(50000),
  paymentMethod: z.enum(['vodafone_cash', 'orange_cash', 'etisalat_cash', 'we_pay']),
});

type FormData = z.infer<typeof mobileWalletSchema>;

interface MobileWalletFormProps {
  saleId: string;
  amount: number;
  paymentMethod: 'vodafone_cash' | 'orange_cash' | 'etisalat_cash' | 'we_pay';
  onSuccess: (payment: Payment) => void;
  onError: (error: Error) => void;
}

export function MobileWalletForm({ saleId, amount, paymentMethod, onSuccess, onError }: MobileWalletFormProps) {
  const [status, setStatus] = useState<'idle' | 'initiating' | 'awaiting_confirmation' | 'success' | 'failed'>('idle');
  const [paymentUuid, setPaymentUuid] = useState<string | null>(null);
  const queryClient = useQueryClient();

  const form = useForm<FormData>({
    resolver: zodResolver(mobileWalletSchema),
    defaultValues: { amount, paymentMethod },
  });

  const initiateMutation = useMutation({
    mutationFn: async (data: FormData) => {
      const response = await fetch('/api/v1/payments/initiate', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          payable_type: 'sale',
          payable_id: saleId,
          payment_method: data.paymentMethod,
          payment_method_details: { phone_number: data.phoneNumber },
          amount: data.amount,
          currency: 'EGP',
        }),
      });
      if (!response.ok) throw new Error('Failed to initiate payment');
      return response.json();
    },
    onSuccess: (data) => {
      setPaymentUuid(data.uuid);
      setStatus('awaiting_confirmation');
      startPolling(data.uuid);
      toast.info('تم إرسال طلب الدفع إلى محفظتك. يرجى التأكيد من تطبيق المحفظة.');
    },
    onError: (error) => {
      setStatus('failed');
      toast.error('فشل في بدء عملية الدفع');
      onError(error);
    },
  });

  // Polling — تحقق من حالة الدفع كل 3 ثواني
  const startPolling = (uuid: string) => {
    const interval = setInterval(async () => {
      try {
        const response = await fetch(`/api/v1/payments/${uuid}/status`);
        const data = await response.json();
        
        if (data.status === 'succeeded') {
          clearInterval(interval);
          setStatus('success');
          toast.success('تم الدفع بنجاح!');
          queryClient.invalidateQueries(['sale', saleId]);
          onSuccess(data);
        } else if (['failed', 'declined', 'expired'].includes(data.status)) {
          clearInterval(interval);
          setStatus('failed');
          toast.error(data.failure_reason || 'فشل الدفع');
          onError(new Error(data.failure_reason));
        }
      } catch (error) {
        console.error('Polling error:', error);
      }
    }, 3000);

    // Timeout after 5 minutes
    setTimeout(() => {
      clearInterval(interval);
      if (status === 'awaiting_confirmation') {
        setStatus('failed');
        toast.error('انتهت مهلة العملية. يرجى المحاولة مرة أخرى.');
      }
    }, 5 * 60 * 1000);
  };

  const onSubmit = (data: FormData) => {
    setStatus('initiating');
    initiateMutation.mutate(data);
  };

  const methodLabels = {
    vodafone_cash: { name: 'Vodafone Cash', color: 'red', icon: '📱' },
    orange_cash: { name: 'Orange Cash', color: 'orange', icon: '📱' },
    etisalat_cash: { name: 'Etisalat Cash', color: 'green', icon: '📱' },
    we_pay: { name: 'We Pay', color: 'purple', icon: '📱' },
  };

  const method = methodLabels[paymentMethod];

  return (
    <div className="space-y-6" dir="rtl">
      {/* Header */}
      <div className={`bg-${method.color}-50 border border-${method.color}-200 rounded-lg p-4`}>
        <div className="flex items-center gap-3">
          <span className="text-3xl">{method.icon}</span>
          <div>
            <h3 className="font-bold text-lg">{method.name}</h3>
            <p className="text-sm text-gray-600">الدفع عبر المحفظة الإلكترونية</p>
          </div>
        </div>
      </div>

      {/* Form */}
      <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-4">
        <div>
          <label className="block text-sm font-medium mb-2">
            رقم الهاتف <Phone className="inline w-4 h-4" />
          </label>
          <input
            type="tel"
            {...form.register('phoneNumber')}
            placeholder="01XXXXXXXXX"
            disabled={status !== 'idle'}
            className="w-full px-4 py-3 border rounded-lg text-left dir-ltr focus:ring-2 focus:ring-primary"
            dir="ltr"
          />
          {form.formState.errors.phoneNumber && (
            <p className="text-red-500 text-sm mt-1">{form.formState.errors.phoneNumber.message}</p>
          )}
        </div>

        <div>
          <label className="block text-sm font-medium mb-2">المبلغ</label>
          <div className="px-4 py-3 bg-gray-50 rounded-lg font-bold text-xl">
            {amount.toFixed(2)} جنيه مصري
          </div>
        </div>

        <button
          type="submit"
          disabled={status !== 'idle' || !form.formState.isValid}
          className="w-full py-4 bg-primary text-white rounded-lg font-bold hover:bg-primary/90 disabled:opacity-50"
        >
          {status === 'initiating' ? (
            <>
              <Loader2 className="inline w-5 h-5 animate-spin ml-2" />
              جاري بدء العملية...
            </>
          ) : (
            `ادفع ${amount.toFixed(2)} ج.م عبر ${method.name}`
          )}
        </button>
      </form>

      {/* Status */}
      {status === 'awaiting_confirmation' && (
        <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
          <div className="flex items-center gap-3">
            <Loader2 className="w-6 h-6 animate-spin text-blue-600" />
            <div>
              <p className="font-bold">في انتظار تأكيدك...</p>
              <p className="text-sm text-gray-600">
                يرجى فتح تطبيق {method.name} وتأكيد عملية الدفع
              </p>
            </div>
          </div>
        </div>
      )}

      {status === 'success' && (
        <div className="bg-green-50 border border-green-200 rounded-lg p-4">
          <div className="flex items-center gap-3">
            <CheckCircle2 className="w-6 h-6 text-green-600" />
            <div>
              <p className="font-bold text-green-900">تم الدفع بنجاح!</p>
              <p className="text-sm text-green-700">رقم العملية: {paymentUuid}</p>
            </div>
          </div>
        </div>
      )}

      {status === 'failed' && (
        <div className="bg-red-50 border border-red-200 rounded-lg p-4">
          <div className="flex items-center gap-3">
            <XCircle className="w-6 h-6 text-red-600" />
            <div>
              <p className="font-bold text-red-900">فشل الدفع</p>
              <button
                onClick={() => setStatus('idle')}
                className="text-sm text-red-700 underline"
              >
                حاول مرة أخرى
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
```

### InstaPayForm.tsx (QR Code):

```tsx
'use client';

import { QRCodeSVG } from 'qrcode.react';
import { Copy, Check } from 'lucide-react';

export function InstaPayForm({ amount, referenceNumber, qrData }: { 
  amount: number; 
  referenceNumber: string;
  qrData: string;
}) {
  const [copied, setCopied] = useState(false);

  const copyReference = () => {
    navigator.clipboard.writeText(referenceNumber);
    setCopied(true);
    setTimeout(() => setCopied(false), 2000);
  };

  return (
    <div className="space-y-6 text-center" dir="rtl">
      <div className="bg-gradient-to-br from-blue-500 to-purple-600 text-white rounded-xl p-6">
        <h3 className="text-2xl font-bold mb-2">InstaPay</h3>
        <p className="text-sm opacity-90">ادفع فوراً عبر تطبيق InstaPay</p>
      </div>

      {/* QR Code */}
      <div className="bg-white p-6 rounded-xl shadow-lg inline-block">
        <QRCodeSVG value={qrData} size={256} level="H" />
      </div>

      {/* Reference Number */}
      <div className="bg-gray-50 rounded-lg p-4">
        <p className="text-sm text-gray-600 mb-2">رقم المرجع</p>
        <div className="flex items-center justify-center gap-2">
          <code className="text-2xl font-mono font-bold tracking-wider">
            {referenceNumber}
          </code>
          <button
            onClick={copyReference}
            className="p-2 hover:bg-gray-200 rounded"
          >
            {copied ? <Check className="w-5 h-5 text-green-600" /> : <Copy className="w-5 h-5" />}
          </button>
        </div>
      </div>

      {/* Instructions */}
      <div className="bg-blue-50 rounded-lg p-4 text-right">
        <h4 className="font-bold mb-2">خطوات الدفع:</h4>
        <ol className="space-y-2 text-sm">
          <li>1. افتح تطبيق InstaPay على هاتفك</li>
          <li>2. امسح رمز QR أو أدخل رقم المرجع</li>
          <li>3. تأكد من المبلغ: <strong>{amount.toFixed(2)} ج.م</strong></li>
          <li>4. أكد العملية</li>
        </ol>
      </div>

      {/* Amount */}
      <div className="text-3xl font-bold text-primary">
        {amount.toFixed(2)} جنيه مصري
      </div>
    </div>
  );
}
```

---

## 🔴 الوحدة 8: التكامل مع المبيعات (Sales Integration)

### في SaleService.php:

```php
class SaleService
{
    public function completeSale(CompleteSaleDTO $dto): Sale
    {
        return DB::transaction(function () use ($dto) {
            // 1. Create sale (pending payment)
            $sale = $this->createSale($dto);

            // 2. If payment method is digital, initiate payment
            if ($dto->paymentMethod->isDigital()) {
                $payment = $this->paymentService->initiatePayment(
                    PaymentRequestDTO::fromSale($sale, $dto)
                );

                $sale->update([
                    'payment_id' => $payment->id,
                    'payment_status' => 'pending',
                ]);

                // Sale is NOT finalized until payment succeeds
                // Webhook will finalize it
            } else {
                // Cash/card — finalize immediately
                $this->finalizeSale($sale);
            }

            return $sale;
        });
    }
}
```

### Listener — PaymentSucceeded:

```php
class FinalizeSaleOnPaymentSuccess
{
    public function handle(PaymentSucceeded $event): void
    {
        $payment = $event->payment;

        if ($payment->payable_type !== Sale::class) {
            return;
        }

        $sale = $payment->payable;

        DB::transaction(function () use ($sale) {
            // 1. Deduct stock
            foreach ($sale->items as $item) {
                $this->inventoryService->deductStock(
                    productId: $item->product_id,
                    branchId: $sale->branch_id,
                    quantity: $item->quantity,
                    reference: $sale
                );
            }

            // 2. Update sale status
            $sale->update([
                'payment_status' => 'paid',
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            // 3. Generate invoice
            $this->invoiceService->generate($sale);

            // 4. Send receipt (SMS/Email/WhatsApp)
            $this->notificationService->sendSaleReceipt($sale);
        });
    }
}
```

---

## 🔒 متطلبات الأمان (Security Requirements)

### 1. **PCI-DSS Compliance**:
- ❌ **لا تخزن بيانات البطاقات** أبداً في قاعدة بياناتك
- ✅ استخدم **Tokenization** من البوابة
- ✅ استخدم **Hosted Payment Pages** للمدفوعات عبر البطاقة

### 2. **Webhook Security**:
```php
// Verify HMAC signature
public function verifyWebhookSignature(string $signature, string $payload): bool
{
    $computedHmac = hash_hmac('sha512', $payload, $this->hmacSecret);
    return hash_equals($computedHmac, $signature); // Timing-safe comparison
}
```

### 3. **Idempotency**:
- كل طلب دفع يجب أن يحتوي على `merchant_reference` فريد
- نفس المرجع = نفس النتيجة (لا دفع مزدوج)

### 4. **Rate Limiting**:
```php
// في RouteServiceProvider
RateLimiter::for('payments', function (Request $request) {
    return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
});

RateLimiter::for('webhooks', function (Request $request) {
    return Limit::perMinute(1000)->by($request->ip());
});
```

### 5. **Encryption**:
- تشفير الحقول الحساسة (phone numbers) في قاعدة البيانات
- استخدام Laravel Encrypted Casting

### 6. **Audit Logs**:
- سجل كل عملية دفع في `activity_logs`
- سجل كل تغيير في حالة الدفع

---

## 🧪 الاختبارات (Testing)

### Unit Tests:

```php
class PaymentServiceTest extends TestCase
{
    public function test_initiates_payment_successfully()
    {
        $gateway = Mockery::mock(PaymentGatewayInterface::class);
        $gateway->shouldReceive('initiatePayment')
            ->once()
            ->andReturn(new PaymentResponseDTO(
                transactionId: 'txn_123',
                status: 'processing'
            ));

        $service = new PaymentService($gateway, ...);
        $result = $service->initiatePayment($request);

        $this->assertEquals('processing', $result->status);
        $this->assertDatabaseHas('payments', [
            'gateway_transaction_id' => 'txn_123',
        ]);
    }

    public function test_idempotency_prevents_duplicate_payments()
    {
        $existingPayment = Payment::factory()->create([
            'merchant_reference' => 'ref_123',
        ]);

        $result = $service->initiatePayment($request->withMerchantReference('ref_123'));

        $this->assertEquals($existingPayment->id, $result->id);
        $this->assertEquals(1, Payment::where('merchant_reference', 'ref_123')->count());
    }

    public function test_webhook_signature_verification()
    {
        $payload = json_encode(['transaction_id' => 'txn_123']);
        $signature = hash_hmac('sha512', $payload, 'secret');

        $this->assertTrue($gateway->verifyWebhookSignature($signature, $payload));
        $this->assertFalse($gateway->verifyWebhookSignature('invalid', $payload));
    }
}
```

### Feature Tests:

```php
class PaymentApiTest extends TestCase
{
    public function test_can_initiate_vodafone_cash_payment()
    {
        $user = $this->createUserWithPermission('create-payments');

        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments/initiate', [
                'payable_type' => 'sale',
                'payable_id' => $sale->id,
                'payment_method' => 'vodafone_cash',
                'payment_method_details' => ['phone_number' => '01012345678'],
                'amount' => 150.00,
                'currency' => 'EGP',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'processing',
                'payment_method' => 'vodafone_cash',
            ]);
    }

    public function test_webhook_updates_payment_status()
    {
        $payment = Payment::factory()->create(['status' => 'processing']);

        $payload = [
            'obj' => [
                'id' => $payment->gateway_transaction_id,
                'success' => true,
                'amount_cents' => 15000,
                'currency' => 'EGP',
            ],
        ];

        $signature = hash_hmac('sha512', json_encode($payload), config('services.paymob.hmac_secret'));

        $response = $this->postJson('/api/webhooks/payments/paymob', $payload, [
            'X-HMAC-Signature' => $signature,
        ]);

        $response->assertStatus(200);
        
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'succeeded',
        ]);
    }
}
```

---

## 📊 التقارير والتسوية (Reporting & Reconciliation)

### PaymentReconciliation Job (يومي):

```php
class DailyPaymentReconciliation implements ShouldQueue
{
    public function handle(): void
    {
        $yesterday = now()->subDay()->toDateString();
        
        foreach (Company::all() as $company) {
            // 1. Get all payments from yesterday
            $localPayments = Payment::where('company_id', $company->id)
                ->whereDate('created_at', $yesterday)
                ->whereIn('status', ['succeeded', 'refunded', 'partially_refunded'])
                ->get();

            // 2. Fetch from gateway
            $gateway = app(PaymentGatewayFactory::class)->make('paymob');
            $remoteTransactions = $gateway->getTransactionsReport($yesterday);

            // 3. Compare
            $discrepancies = $this->findDiscrepancies($localPayments, $remoteTransactions);

            // 4. Log discrepancies
            foreach ($discrepancies as $discrepancy) {
                PaymentReconciliation::create([
                    'company_id' => $company->id,
                    'date' => $yesterday,
                    'discrepancy_type' => $discrepancy->type,
                    'payment_id' => $discrepancy->paymentId,
                    'local_amount' => $discrepancy->localAmount,
                    'remote_amount' => $discrepancy->remoteAmount,
                    'status' => 'pending_review',
                ]);
            }

            // 5. Notify admin
            if ($discrepancies->isNotEmpty()) {
                Notification::send(
                    $company->admin,
                    new PaymentReconciliationAlert($discrepancies)
                );
            }
        }
    }
}
```

---

## ⚙️ الإعدادات (Configuration)

### config/payments.php:

```php
return [
    'default_gateway' => env('PAYMENT_GATEWAY', 'paymob'),

    'gateways' => [
        'paymob' => [
            'api_key' => env('PAYMOB_API_KEY'),
            'merchant_id' => env('PAYMOB_MERCHANT_ID'),
            'hmac_secret' => env('PAYMOB_HMAC_SECRET'),
            'iframe_id' => env('PAYMOB_IFRAME_ID'),
            
            'integration_ids' => [
                'vodafone_cash' => env('PAYMOB_INTEGRATION_VODAFONE'),
                'orange_cash' => env('PAYMOB_INTEGRATION_ORANGE'),
                'etisalat_cash' => env('PAYMOB_INTEGRATION_ETISALAT'),
                'we_pay' => env('PAYMOB_INTEGRATION_WE'),
                'instapay' => env('PAYMOB_INTEGRATION_INSTAPAY'),
                'card' => env('PAYMOB_INTEGRATION_CARD'),
            ],

            'webhook_url' => env('PAYMOB_WEBHOOK_URL'),
            'environment' => env('PAYMOB_ENV', 'production'), // sandbox | production
        ],
    ],

    'limits' => [
        'min_amount' => 1.00,
        'max_amount' => 50000.00,
    ],

    'retry' => [
        'max_attempts' => 3,
        'backoff' => [30, 60, 300],
    ],
];
```

---

## 🚀 خطة التنفيذ (Implementation Plan)

| Phase | Duration | Tasks |
|-------|----------|-------|
| **1** | 3-4 أيام | Setup Paymob account, get API keys, configure sandbox |
| **2** | 1 أسبوع | Payment Gateway Abstraction Layer + Paymob implementation |
| **3** | 1 أسبوع | Database migrations + Models + Repositories |
| **4** | 1 أسبوع | PaymentService + Webhooks + Idempotency |
| **5** | 1 أسبوع | Frontend components (Mobile wallets + InstaPay) |
| **6** | 3-4 أيام | Integration with Sales/POS + Subscription billing |
| **7** | 3-4 أيام | Refunds + Partial payments + Split payments |
| **8** | 2-3 أيام | Reconciliation + Reporting |
| **9** | 1 أسبوع | Testing (Unit + Feature + E2E) + Security audit |
| **10** | 2-3 أيام | Documentation + Admin UI for payment settings |

**المدة الإجمالية: 5-6 أسابيع**

---

## ⚠️ القواعد الصارمة (Strict Rules)

1. ✅ **استخدم Payment Gateway Abstraction** — لا تكتب كود Paymob مباشرة في Controllers
2. ✅ **Idempotency دائماً** — لا دفع مزدوج أبداً
3. ✅ **Webhook Signature Verification** — لا تثق في أي webhook بدون تحقق
4. ✅ **Database Transactions** — كل عملية دفع في transaction
5. ✅ **Queue Webhooks** — لا تحجب البوابة
6. ✅ **Log Everything** — كل API call في `payment_gateway_logs`
7. ✅ **Soft Deletes** — لا تحذف المعاملات أبداً
8. ✅ **Audit Trail** — كل تغيير في حالة الدفع مسجل
9. ✅ **Retry Logic** — Webhooks قد تفشل، استخدم queues مع retries
10. ✅ **Test Sandbox First** — اختبر في sandbox قبل production
11. ✅ **No Sensitive Data in Logs** — لا تسجل أرقام بطاقات أو CVV
12. ✅ **HTTPS Only** — كل endpoints يجب أن تكون HTTPS
13. ✅ **Rate Limiting** — احمِ endpoints من الهجمات
14. ✅ **Arabic + English** — كل النصوص والرسائل بلغتين
15. ✅ **RTL Support** — الواجهة تعمل بالعربية بشكل كامل

---

## 📦 المخرجات المطلوبة (Deliverables)

1. ✅ Payment Gateway Abstraction Layer كامل
2. ✅ Paymob Integration (Vodafone Cash, Orange Cash, Etisalat Cash, InstaPay)
3. ✅ Database migrations + Models + Repositories
4. ✅ PaymentService + Webhooks + Idempotency
5. ✅ Frontend components (React + TypeScript)
6. ✅ Integration with Sales/POS
7. ✅ Refunds + Partial payments
8. ✅ Reconciliation system
9. ✅ Tests شاملة (80%+ coverage)
10. ✅ Documentation كاملة (API + User guide)
11. ✅ Admin UI لإعدادات الدفع
12. ✅ Security audit report

---

## 💡 ملاحظات إضافية

- **البوابة الموصى بها**: Paymob (الأفضل للسوق المصري)
- **الرسوم المتوقعة**: ~2.75% + 2 EGP لكل عملية (محافظ) / ~2.5% (InstaPay)
- **وقت التسوية**: T+1 للمحافظ، T+2 للبطاقات
- **الحدود اليومية**: تختلف حسب خطة Paymob (عادة 50,000 - 200,000 ج.م)
- **الدعم الفني**: Paymob يوفر دعم عربي 24/7

---

## 🎯 الإجراء الأول (First Action)

ابدأ بـ:

1. **إنشاء حساب Paymob** (sandbox) — https://paymob.com
2. **الحصول على API Keys** من لوحة التحكم
3. **إنشاء ARCHITECTURE-PAYMENTS.md** يوثق البنية
4. **بناء Payment Gateway Abstraction Layer** أولاً
5. **تنفيذ Paymob Gateway** مع sandbox testing
6. **بناء Frontend components**
7. **Integration testing** مع sandbox
8. **Go live** بعد النجاح في sandbox

**قبل أن تكتب أي سطر كود، أكد لي:**
- فهمت السياق الكامل ✓
- لديك خطة واضحة ✓
- ستلتزم بكل القواعد الصارمة ✓
- ستبدأ بـ Payment Gateway Abstraction Layer ✓

ثم ابدأ بـ **ARCHITECTURE-PAYMENTS.md** أولاً.

---

**ابدأ الآن. أظهر لي أنك فهمت كل شيء، ثم ابدأ بـ ARCHITECTURE-PAYMENTS.md.**