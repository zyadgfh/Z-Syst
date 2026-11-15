<?php

namespace Tests\Feature;

use App\Models\PurchaseOrder;
use App\Models\SupplierInvoice;
use App\Models\SupplierInvoicePayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SupplierBusinessIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_another_business_invoice_and_order_are_not_accessible(): void
    {
        $user = User::factory()->create();
        $invoice = SupplierInvoice::factory()->create();
        $order = PurchaseOrder::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson("/api/v1/supplier-invoices/{$invoice->id}")->assertNotFound();
        $this->postJson("/api/v1/supplier-invoices/{$invoice->id}/approve")->assertNotFound();
        $this->getJson("/api/v1/purchase-orders/{$order->id}")->assertNotFound();
        $this->postJson("/api/v1/purchase-orders/{$order->id}/cancel")->assertNotFound();
    }

    public function test_another_business_payment_cannot_be_approved(): void
    {
        $user = User::factory()->create();
        $payment = SupplierInvoicePayment::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson("/api/v1/supplier-invoices/payments/{$payment->id}/approve")->assertNotFound();
    }
}
