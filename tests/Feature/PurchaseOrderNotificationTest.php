<?php

namespace Tests\Feature;

use App\Mail\BusinessMail;
use App\Models\Party;
use App\Models\Product;
use App\Models\User;
use App\Services\PurchaseOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PurchaseOrderNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected PurchaseOrderService $poService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->poService = app(PurchaseOrderService::class);
    }

    protected function createSentPo(): \App\Models\PurchaseOrder
    {
        $user = User::factory()->create();
        $supplier = Party::factory()->create([
            'type' => 'supplier',
            'email' => 'supplier@example.com',
            'phone' => '+201000000003',
        ]);
        $product = Product::factory()->create();

        $po = $this->poService->create([
            'supplier_id' => $supplier->id,
            'business_id' => $user->business_id,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 5,
                    'unit_price' => 20,
                ],
            ],
        ]);

        return $po;
    }

    public function test_sending_purchase_order_emails_supplier_with_po_details(): void
    {
        Mail::fake();

        $po = $this->createSentPo();

        $this->poService->send($po);

        Mail::assertSent(BusinessMail::class, function (BusinessMail $mail) use ($po) {
            return str_contains($mail->subjectLine, $po->po_number)
                && str_contains($mail->subjectLine, 'sent')
                && $mail->hasTo('supplier@example.com')
                && ! empty($mail->table['rows']);
        });
    }

    public function test_cancelling_purchase_order_notifies_supplier_again(): void
    {
        Mail::fake();

        $po = $this->createSentPo();

        $this->poService->send($po);
        $this->poService->cancel($po);

        Mail::assertSent(BusinessMail::class, 2);
    }

    public function test_restoring_cancelled_purchase_order_notifies_supplier(): void
    {
        Mail::fake();

        $po = $this->createSentPo();

        $this->poService->send($po);
        $this->poService->cancel($po);
        $this->poService->restore($po);

        Mail::assertSent(BusinessMail::class, 3);
    }
}
