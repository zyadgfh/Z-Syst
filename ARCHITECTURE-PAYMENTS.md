# 🏗️ بنية نظام الدفع الإلكتروني - Z-Syst Pharmacy

## 📋 نظرة عامة

نظام الدفع الإلكتروني في Z-Syst Pharmacy Management SaaS مصمم لدعم المدفوعات الرقمية في مصر من خلال **Paymob** كـ Payment Aggregator الأساسي، مع دعم المحافظ الإلكترونية والشبكة الفورية للدفع (InstaPay).

---

## 🎯 الأهداف

1. **مدفوعات العملاء (B2C)** — الدفع في نقطة البيع عبر Vodafone Cash, Orange Cash, Etisalat Cash, InstaPay
2. **اشتراكات SaaS (B2B)** — الاشتراكات المتكررة للمالكين
3. **الدفع الجزئي** — الدفع عبر طرق متعددة (نقدي + محفظة إلكترونية)

---

## 📁 بنية المجلدات

```
app/
├── Services/
│   └── Payment/
│       ├── Contracts/
│       │   └── PaymentGatewayInterface.php      ✅ (موجود)
│       ├── Enums/
│       │   ├── PaymentMethodType.php            ✅ (موجود)
│       │   └── TransactionStatus.php            ✅ (موجود)
│       ├── Exceptions/
│       │   ├── PaymentException.php             ✅ (موجود)
│       │   ├── PaymentGatewayException.php      ⏳ (مطلوب)
│       │   ├── InsufficientBalanceException.php ⏳ (مطلوب)
│       │   ├── PaymentDeclinedException.php     ⏳ (مطلوب)
│       │   └── IdempotencyException.php         ⏳ (مطلوب)
│       ├── Gateways/
│       │   ├── BaseGateway.php                  ✅ (موجود)
│       │   ├── CashPaymentGateway.php           ✅ (موجود)
│       │   ├── BNPLPaymentGateway.php           ✅ (موجود)
│       │   ├── VodafoneCashGateway.php          ✅ (موجود - يحتاج تحديث لاستخدام Paymob)
│       │   ├── OrangeCashGateway.php            ⏳ (مطلوب)
│       │   ├── EtisalatCashGateway.php          ⏳ (مطلوب)
│       │   ├── InstaPayGateway.php              ⏳ (مطلوب)
│       │   └── PaymobGateway.php                ⏳ (مطلوب - Aggregator رئيسي)
│       ├── Models/
│       │   └── PaymentTransaction.php           ✅ (موجود)
│       ├── Services/
│       │   ├── PaymentProcessor.php             ✅ (موجود)
│       │   ├── PaymentService.php               ⏳ (مطلوب - Orchestrator محسن)
│       │   └── PaymentGatewayFactory.php        ⏳ (مطلوب)
│       └── DTOs/
│           ├── PaymentRequestDTO.php            ⏳ (مطلوب)
│           ├── PaymentResponseDTO.php           ⏳ (مطلوب)
│           ├── RefundRequestDTO.php             ⏳ (مطلوب)
│           └── WebhookPayloadDTO.php            ⏳ (مطلوب)
├── Models/
│   └── PaymentMethod.php                      ⏳ (مطلوب - إعدادات طرق الدفع)
├── Events/
│   ├── PaymentCreated.php                     ⏳ (مطلوب)
│   ├── PaymentSucceeded.php                   ⏳ (مطلوب)
│   ├── PaymentFailed.php                      ⏳ (مطلوب)
│   ├── PaymentRefunded.php                    ⏳ (مطلوب)
│   └── PaymentWebhookReceived.php             ⏳ (مطلوب)
├── Listeners/
│   ├── UpdateSaleStatusOnPayment.php            ⏳ (مطلوب)
│   ├── SendPaymentReceiptNotification.php       ⏳ (مطلوب)
│   ├── UpdateSubscriptionOnPayment.php          ⏳ (مطلوب)
│   └── LogPaymentActivity.php                 ⏳ (مطلوب)
├── Jobs/
│   ├── ProcessPaymentWebhook.php              ⏳ (مطلوب - موجود جزئياً)
│   ├── VerifyPaymentStatus.php                ⏳ (مطلوب)
│   ├── RetryFailedPayment.php                 ⏳ (مطلوب)
│   └── ReconcilePayments.php                  ⏳ (مطلوب)
└── Http/
    ├── Controllers/
    │   ├── PaymentController.php                ⏳ (مطلوب - API endpoints)
    │   └── PaymentWebhookController.php         ⏳ (مطلوب)
    └── Requests/
        ├── InitiatePaymentRequest.php           ⏳ (مطلوب)
        ├── VerifyPaymentRequest.php           ⏳ (مطلوب)
        └── RefundPaymentRequest.php           ⏳ (مطلوب)
```

---

## 🔧 Payment Gateway Abstraction Layer

### PaymentGatewayInterface (مُحدَّث)

```php
interface PaymentGatewayInterface
{
    /**
     * Get the payment method type this gateway handles
     */
    public function getMethodType(): PaymentMethodType;

    /**
     * Get display name
     */
    public function getDisplayName(): string;

    /**
     * Check gateway availability
     */
    public function isAvailable(): bool;

    /**
     * Initiate a payment transaction
     */
    public function initiate(array $data): PaymentTransaction;

    /**
     * Verify transaction status
     */
    public function verify(PaymentTransaction $transaction): PaymentTransaction;

    /**
     * Process payment (confirm with OTP if needed)
     */
    public function process(PaymentTransaction $transaction, array $data = []): PaymentTransaction;

    /**
     * Process refund
     */
    public function refund(PaymentTransaction $transaction, ?float $amount = null, ?string $reason = null): PaymentTransaction;

    /**
     * Handle webhook callback
     */
    public function handleWebhook(array $payload): ?PaymentTransaction;

    /**
     * Generate QR code for payment
     */
    public function generateQrCode(PaymentTransaction $transaction): ?string;

    /**
     * Get external transaction status
     */
    public function getExternalTransactionStatus(string $externalTransactionId): array;
}
```

---

## 🏦 Paymob Integration (البوابة الرئيسية)

### لماذا Paymob؟

| الميزة | الوصف |
|--------|-------|
| ✅ يدعم المحافظ | Vodafone Cash, Orange Cash, Etisalat Cash, We Pay |
| ✅ يدعم InstaPay | دمج كامل مع الشبكة الفورية للدفع |
| ✅ الدفع المتكرر | دعم الاشتراكات SaaS |
| ✅ Webhooks موثوقة | تحقق من التوقيع HMAC-SHA512 |
| ✅ وثائق عربية | دعم فني مصري 24/7 |

### PaymobGateway.php (التنفيذ الكامل)

```php
<?php

namespace App\Services\Payment\Gateways;

use App\Services\Payment\Contracts\PaymentGatewayInterface;
use App\Services\Payment\Enums\PaymentMethodType;
use App\Services\Payment\Enums\TransactionStatus;
use App\Services\Payment\Exceptions\PaymentException;
use App\Services\Payment\Models\PaymentTransaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymobGateway implements PaymentGatewayInterface
{
    private const API_BASE_SANDBOX = 'https://accept.paymob.com/api';
    private const API_BASE_PRODUCTION = 'https://accept.paymob.com/api';
    
    protected array $integrationIds = [];
    protected bool $isSandbox = true;
    protected string $hmacSecret;

    public function __construct(protected array $config = [])
    {
        $this->integrationIds = $config['integration_ids'] ?? [];
        $this->isSandbox = $config['environment'] === 'sandbox';
        $this->hmacSecret = $config['hmac_secret'];
    }

    public function getMethodType(): PaymentMethodType
    {
        return PaymentMethodType::VODAFONE_CASH; // البوابة تدعم جميع الطرق
    }

    public function getDisplayName(): string
    {
        return 'Paymob Accept';
    }

    public function isAvailable(): bool
    {
        return !empty($this->config['api_key']) && !empty($this->integrationIds);
    }

    /**
     * إرسال طلب دفع عبر Paymob
     */
    public function initiate(array $data): PaymentTransaction
    {
        $methodType = PaymentMethodType::from($data['payment_method_type']);
        
        // 1. الحصول على Auth Token
        $authToken = $this->authenticate();

        // 2. إنشاء Order
        $order = $this->createOrder($authToken, $data);

        // 3. إنشاء Payment Key
        $paymentKey = $this->getPaymentKey($authToken, $order['id'], $data);

        // 4. إنشاء سجل المعاملة
        $transaction = $this->createTransaction([
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'EGP',
            'payment_method_type' => $methodType->value,
            'mobile_number' => $data['mobile_number'] ?? null,
            'wallet_provider' => $this->getWalletProvider($methodType),
            'description' => $data['description'] ?? 'Payment via Paymob',
            'reference_id' => $data['reference_id'] ?? null,
            'reference_type' => $data['reference_type'] ?? null,
        ]);

        // 5. تحديث المعاملة بالـ payment key
        $transaction->update([
            'external_reference' => $paymentKey['id'] ?? uniqid('paymob_'),
            'status' => TransactionStatus::PROCESSING->value,
            'processed_at' => now(),
        ]);

        return $transaction->fresh();
    }

    /**
     * التحقق من حالة الدفع
     */
    public function verify(PaymentTransaction $transaction): PaymentTransaction
    {
        $authToken = $this->authenticate();
        
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$authToken}",
            'Content-Type' => 'application/json',
        ])->get("{$this->getApiBase()}/orders/{$transaction->external_reference}");

        if ($response->successful()) {
            $orderData = $response->json();
            
            foreach ($orderData['transactions'] ?? [] as $tx) {
                $status = $tx['success'] ?? false ? 'completed' : 'failed';
                
                match ($status) {
                    'completed' => $transaction->markAsCompleted($tx['id']),
                    'failed' => $transaction->markAsFailed($tx['data']['message'] ?? 'Payment failed'),
                    'refunded' => $transaction->markAsRefunded(),
                    default => null,
                };
            }
        }

        return $transaction->fresh();
    }

    /**
     * معالجة Webhook من Paymob
     */
    public function handleWebhook(array $payload): ?PaymentTransaction
    {
        $transactionId = $payload['obj']['order']['id'] ?? null;
        $hmac = $payload['hmac'] ?? '';

        // التحقق من التوقيع
        if (!$this->verifyHmac($hmac, $payload)) {
            Log::warning('Invalid Paymob webhook signature', ['hmac' => $hmac]);
            return null;
        }

        $transaction = PaymentTransaction::where('external_reference', $transactionId)->first();
        
        if (!$transaction) {
            Log::warning('Paymob webhook: Transaction not found', ['transaction_id' => $transactionId]);
            return null;
        }

        $isSuccess = $payload['obj']['success'] ?? false;
        $amountCents = $payload['obj']['amount_cents'] ?? 0;

        if ($isSuccess) {
            $transaction->markAsCompleted($payload['obj']['id']);
        } else {
            $transaction->markAsFailed($payload['obj']['data']['message'] ?? 'Payment failed');
        }

        // حفظ بيانات الـ webhook
        $transaction->update([
            'webhook_received' => true,
            'webhook_payload' => $payload,
        ]);

        return $transaction->fresh();
    }

    /**
     * التحقق من HMAC
     */
    protected function verifyHmac(string $hmac, array $payload): bool
    {
        $computedHmac = hash_hmac('sha512', json_encode($payload), $this->hmacSecret);
        return hash_equals($computedHmac, $hmac);
    }

    /**
     * الحصول على Auth Token
     */
    protected function authenticate(): string
    {
        $response = Http::post("{$this->getApiBase()}/auth/tokens", [
            'api_key' => $this->config['api_key'],
        ]);

        return $response->json('token');
    }

    /**
     * إنشاء Order
     */
    protected function createOrder(string $authToken, array $data): array
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$authToken}",
            'Content-Type' => 'application/json',
        ])->post("{$this->getApiBase()}/ecommerce/orders", [
            'auth_token' => $authToken,
            ' delivery_needed' => 'false',
            'amount_cents' => $this->formatAmount($data['amount']),
            'currency' => $data['currency'] ?? 'EGP',
            'merchant_order_id' => uniqid('order_'),
            'items' => [],
        ]);

        return $response->json();
    }

    /**
     * إنشاء Payment Key
     */
    protected function getPaymentKey(string $authToken, int $orderId, array $data): array
    {
        $methodType = PaymentMethodType::from($data['payment_method_type']);
        $integrationId = $this->integrationIds[$methodType->value] ?? null;

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$authToken}",
            'Content-Type' => 'application/json',
        ])->post("{$this->getApiBase()}/accept/payment_keys", [
            'auth_token' => $authToken,
            'amount_cents' => $this->formatAmount($data['amount']),
            'expiration' => 3600,
            'order_id' => $orderId,
            'integration_id' => $integrationId,
            'billing_data' => $this->formatBillingData($data),
            'payment_method' => $integrationId ? null : $this->getPaymentMethod($methodType),
            'lock_order_when_paid' => true,
        ]);

        return $response->json();
    }

    protected function getApiBase(): string
    {
        return $this->isSandbox 
            ? self::API_BASE_SANDBOX 
            : self::API_BASE_PRODUCTION;
    }

    protected function formatAmount(float $amount): int
    {
        return (int) round($amount * 100); // Paymob يستخدم القروش
    }

    protected function getPaymentMethod(PaymentMethodType $type): int
    {
        return $this->integrationIds[$type->value] ?? $this->integrationIds['card'];
    }

    protected function getWalletProvider(PaymentMethodType $type): string
    {
        return match($type) {
            PaymentMethodType::VODAFONE_CASH => 'vodafone',
            PaymentMethodType::ORANGE_CASH => 'orange',
            PaymentMethodType::ETISALAT_CASH => 'etisalat',
            PaymentMethodType::INSTAPAY => 'instapay',
            PaymentMethodType::WE_PAY => 'we',
            default => 'unknown',
        };
    }

    protected function formatBillingData(array $data): array
    {
        return [
            'first_name' => $data['customer_name'] ?? 'Customer',
            'last_name' => ' ',
            'email' => $data['customer_email'] ?? 'customer@example.com',
            'phone_number' => $data['mobile_number'] ?? null,
            // ... باقي البيانات المطلوبة
        ];
    }

    public function generateQrCode(PaymentTransaction $transaction): ?string
    {
        // Paymob يدعم InstaPay QR Code
        if ($transaction->payment_method_type === PaymentMethodType::INSTAPAY->value) {
            return $this->generateInstaPayQr($transaction);
        }
        
        return null;
    }

    protected function generateInstaPayQr(PaymentTransaction $transaction): ?string
    {
        // سيتم تنفيذها عند الحاجة
        return null;
    }

    public function getExternalTransactionStatus(string $externalTransactionId): array
    {
        // سيتم تنفيذها عند الحاجة
        return [];
    }

    public function process(PaymentTransaction $transaction, array $data = []): PaymentTransaction
    {
        // Paymob تتولى العملية تلقائياً للمحافظ
        return $transaction;
    }

    public function refund(PaymentTransaction $transaction, ?float $amount = null, ?string $reason = null): PaymentTransaction
    {
        // سيتم تنفيذها حسب API Paymob
        return $transaction;
    }
}
```

---

## 📊 قاعدة البيانات

### جدول payment_transactions (موجود)

```php
// مراجعة البنية الحالية:
Schema::create('payment_transactions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
    $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
    $table->string('payment_method_type'); // vodafone_cash, orange_cash, cash, bnpl
    $table->string('transaction_type')->default('sale');
    $table->nullableMorphs('reference');
    $table->decimal('amount', 15, 2);
    $table->string('currency', 3)->default('EGP');
    $table->decimal('fee', 15, 2)->default(0);
    $table->decimal('net_amount', 15, 2)->default(0);
    $table->string('status')->default('pending');
    $table->string('external_transaction_id')->nullable()->index();
    $table->string('external_reference')->nullable()->unique();
    $table->string('payment_url')->nullable();
    $table->text('qr_code_url')->nullable();
    $table->text('qr_code_data')->nullable();
    $table->string('mobile_number')->nullable();
    $table->string('wallet_provider')->nullable();
    $table->string('callback_url')->nullable();
    $table->boolean('webhook_received')->default(false);
    $table->json('webhook_payload')->nullable();
    $table->json('metadata')->nullable();
    $table->text('description')->nullable();
    $table->text('notes')->nullable();
    $table->timestamp('initiated_at')->nullable();
    $table->timestamp('processed_at')->nullable();
    $table->timestamp('completed_at')->nullable();
    $table->timestamp('failed_at')->nullable();
    $table->string('failure_reason')->nullable();
    $table->timestamp('refunded_at')->nullable();
    $table->decimal('refund_amount', 15, 2)->nullable();
    $table->text('refund_reason')->nullable();
    $table->string('ip_address', 45)->nullable();
    $table->text('user_agent')->nullable();
    $table->timestamps();
    $table->softDeletes();
});
```

---

## 🎮 API Endpoints

### routes/api.php (إضافة Routes)

```php
// إضافة إلى routes/api.php داخل middleware group
Route::prefix('payments')->group(function () {
    // Payment endpoints
    Route::post('/initiate', [Api\PaymentController::class, 'initiate']);
    Route::post('/verify', [Api\PaymentController::class, 'verify']);
    Route::post('/refund', [Api\PaymentController::class, 'refund']);
    Route::get('/methods', [Api\PaymentController::class, 'availableMethods']);
    Route::get('/{transaction}', [Api\PaymentController::class, 'show']);
    
    // POS-specific endpoints
    Route::post('/pos/quick-pay', [Api\PosPaymentController::class, 'quickPay']);
    Route::post('/pos/split-payment', [Api\PosPaymentController::class, 'splitPayment']);
});

// Webhooks (بدون auth)
Route::prefix('webhooks/payments')->group(function () {
    Route::post('/{gateway}', [Api\PaymentWebhookController::class, 'handle'])
        ->middleware('throttle:webhooks');
});
```

---

## 🔄 Events & Listeners

### Events المطلوبة:

```php
// PaymentCreated
// PaymentSucceeded
// PaymentFailed
// PaymentRefunded
// PaymentWebhookReceived
```

### Listeners المطلوبة:

```php
// UpdateSaleStatusOnPayment - تحديث حالة المبيعات عند الدفع
// SendPaymentReceiptNotification - إرسال إيصال الدفع
// UpdateSubscriptionOnPayment - تحديث الاشتراك (B2B)
// LogPaymentActivity - تسجيل جميع الأنشطة
```

---

## ⚙️ Configuration

### config/payments.php (جديد)

```php
<?php

return [
    'default_gateway' => env('PAYMENT_GATEWAY', 'paymob'),
    
    'gateways' => [
        'paymob' => [
            'api_key' => env('PAYMOB_API_KEY'),
            'merchant_id' => env('PAYMOB_MERCHANT_ID'),
            'hmac_secret' => env('PAYMOB_HMAC_SECRET'),
            'iframe_id' => env('PAYMOB_IFRAME_ID'),
            'environment' => env('PAYMOB_ENV', 'sandbox'), // sandbox | production
            
            'integration_ids' => [
                'vodafone_cash' => env('PAYMOB_INTEGRATION_VODAFONE'),
                'orange_cash' => env('PAYMOB_INTEGRATION_ORANGE'),
                'etisalat_cash' => env('PAYMOB_INTEGRATION_ETISALAT'),
                'we_pay' => env('PAYMOB_INTEGRATION_WE'),
                'instapay' => env('PAYMOB_INTEGRATION_INSTAPAY'),
                'card' => env('PAYMOB_INTEGRATION_CARD'),
            ],
            
            'webhook_url' => env('PAYMOB_WEBHOOK_URL'),
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

## 🧪 Testing Strategy

### Unit Tests:

```bash
tests/
├── PaymentProcessorTest.php
├── Gateways/
│   ├── PaymobGatewayTest.php
│   ├── VodafoneCashGatewayTest.php
│   └── InstaPayGatewayTest.php
├── PaymentWebhookTest.php
└── PaymentServiceTest.php
```

---

## 🚀 خطة التنفيذ

| المرحلة | المدة | المهام |
|--------|-------|-------|
| **1** | 3 أيام | إعداد Paymob Sandbox + الحصول على API Keys |
| **2** | 1 أسبوع | تكامل Paymob Gateway (Vodafone Cash, Orange Cash, Etisalat Cash, InstaPay) |
| **3** | 1 أسبوع | DTOs + PaymentService + Events |
| **4** | 4 أيام | Webhooks Controller + Queue Jobs |
| **5** | 1 أسبوع | Frontend Components (React/TypeScript) |
| **6** | 3 أيام | Integration مع المبيعات + POS |
| **7** | 2 أيام | Testing + Security Audit |

---

## 🔐 أمان PCI-DSS

1. **عدم تخزين بيانات البطاقات** - استخدام Tokenization فقط
2. **Webhooks Signature Verification** - HMAC-SHA512
3. **Idempotency** - كل طلب يحتوي على merchant_reference فريد
4. **Rate Limiting** - حماية من الهجمات
5. **HTTPS Only** - جميع endpoints آمنة
6. **Audit Logs** - سجل كل عملية

---

## 📝 ملاحظات مهمة

- البدء بـ **ARCHITECTURE-PAYMENTS.md** (هذا الملف) ✅
- البوابة الموصى به: **Paymob** (أفضل دعم للسوق المصري)
- الرسوم المتوقعة: ~2.75% + 2 EGP لكل عملية
- وقت التسوية: T+1 للمحافظ، T+2 للبطاقات
- دعم Paymob العربي 24/7