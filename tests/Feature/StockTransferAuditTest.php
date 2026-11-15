<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockTransferAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_transfer_is_completed_only_once(): void
    {
        $business = Business::factory()->create();
        $product = Product::factory()->create(['business_id' => $business->id]);
        $from = Warehouse::create(['business_id' => $business->id, 'name' => 'Source', 'code' => 'A']);
        $to = Warehouse::create(['business_id' => $business->id, 'name' => 'Destination', 'code' => 'B']);
        WarehouseStock::create(['business_id' => $business->id, 'warehouse_id' => $from->id, 'product_id' => $product->id, 'quantity' => 10]);
        $transfer = StockTransfer::create(['business_id' => $business->id, 'from_warehouse_id' => $from->id, 'to_warehouse_id' => $to->id, 'product_id' => $product->id, 'quantity' => 4, 'status' => 'pending']);
        $stale = StockTransfer::findOrFail($transfer->id);

        $this->assertTrue($transfer->complete());
        $this->assertFalse($stale->complete());
        $this->assertDatabaseHas('warehouse_stocks', ['warehouse_id' => $from->id, 'product_id' => $product->id, 'quantity' => 6]);
        $this->assertDatabaseHas('warehouse_stocks', ['warehouse_id' => $to->id, 'product_id' => $product->id, 'quantity' => 4]);
    }
    public function test_user_cannot_complete_another_business_transfer(): void
    {
        $user = \App\Models\User::factory()->create();
        $otherBusiness = Business::factory()->create();
        $this->actingAs($user);
        $transfer = new StockTransfer(['business_id' => $otherBusiness->id]);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
        app(\App\Http\Controllers\Admin\StockTransferController::class)->complete($transfer);
    }

}
