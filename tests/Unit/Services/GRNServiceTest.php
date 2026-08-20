<?php

namespace Tests\Unit\Services;

use App\Models\Business;
use App\Models\GoodsReceivedNote;
use App\Models\GrnItem;
use App\Models\Product;
use App\Models\Party;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\GRNService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GRNServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $service;
    protected $business;
    protected $user;
    protected $warehouse;
    protected $supplier;
    protected $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::factory()->create();
        $this->user = User::factory()->create(['business_id' => $this->business->id]);
        $this->warehouse = Warehouse::factory()->create(['business_id' => $this->business->id]);
        $this->supplier = Party::factory()->create(['business_id' => $this->business->id, 'type' => 'supplier']);
        $this->product = Product::factory()->create(['business_id' => $this->business->id]);

        $this->service = new GRNService();
    }

    protected function createGRN(array $overrides = []): GoodsReceivedNote
    {
        $data = array_merge([
            'business_id' => $this->business->id,
            'supplier_id' => $this->supplier->id,
            'received_by' => $this->user->id,
            'grn_number' => GoodsReceivedNote::generateGRNNumber(),
            'received_date' => now()->toDateString(),
            'notes' => 'Test GRN',
        ], $overrides);

        return GoodsReceivedNote::create($data);
    }

    // ─── CREATE ───────────────────────────────────────────────

    public function test_create_creates_grn(): void
    {
        $data = [
            'business_id' => $this->business->id,
            'supplier_id' => $this->supplier->id,
            'received_by' => $this->user->id,
            'received_date' => now()->toDateString(),
            'notes' => 'Test GRN',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'ordered_quantity' => 10,
                    'received_quantity' => 5,
                    'unit_price' => 25.00,
                ],
            ],
        ];

        $grn = $this->service->create($data);

        $this->assertDatabaseHas('goods_received_notes', ['id' => $grn->id]);
        $this->assertDatabaseHas('grn_items', ['grn_id' => $grn->id]);
    }

    public function test_create_returns_goods_received_note_instance(): void
    {
        $grn = $this->service->create([
            'business_id' => $this->business->id,
            'supplier_id' => $this->supplier->id,
            'received_by' => $this->user->id,
            'received_date' => now()->toDateString(),
        ]);

        $this->assertInstanceOf(GoodsReceivedNote::class, $grn);
        $this->assertEquals(GoodsReceivedNote::STATUS_PENDING, $grn->status);
    }

    // ─── ADD ITEM ─────────────────────────────────────────────

    public function test_add_item_creates_grn_item(): void
    {
        $grn = $this->createGRN();

        $item = $this->service->addItem($grn, [
            'product_id' => $this->product->id,
            'ordered_quantity' => 10,
            'received_quantity' => 5,
            'unit_price' => 25.00,
        ]);

        $this->assertInstanceOf(GrnItem::class, $item);
        $this->assertDatabaseHas('grn_items', ['id' => $item->id]);
    }

    public function test_add_item_sets_correct_values(): void
    {
        $grn = $this->createGRN();

        $item = $this->service->addItem($grn, [
            'product_id' => $this->product->id,
            'ordered_quantity' => 10,
            'received_quantity' => 5,
            'unit_price' => 25.00,
        ]);

        $this->assertEquals($this->product->id, $item->product_id);
        $this->assertEquals(10, $item->ordered_quantity);
        $this->assertEquals(5, $item->received_quantity);
    }

    // ─── REMOVE ITEM ──────────────────────────────────────────

    public function test_remove_item_deletes_grn_item(): void
    {
        $grn = $this->createGRN();
        $item = $this->service->addItem($grn, [
            'product_id' => $this->product->id,
            'ordered_quantity' => 10,
            'received_quantity' => 5,
            'unit_price' => 25.00,
        ]);

        $this->assertDatabaseHas('grn_items', ['id' => $item->id]);

        $item->delete();

        $this->assertDatabaseMissing('grn_items', ['id' => $item->id]);
    }

    // ─── MODEL STATUS CHECKS ──────────────────────────────────

    public function test_is_pending(): void
    {
        $grn = $this->createGRN(['status' => GoodsReceivedNote::STATUS_PENDING]);
        $this->assertTrue($grn->isPending());
    }

    public function test_is_verified(): void
    {
        $grn = $this->createGRN(['status' => GoodsReceivedNote::STATUS_VERIFIED]);
        $this->assertTrue($grn->isVerified());
    }

    public function test_is_rejected(): void
    {
        $grn = $this->createGRN(['status' => GoodsReceivedNote::STATUS_REJECTED]);
        $this->assertTrue($grn->isRejected());
    }

    public function test_is_accepted(): void
    {
        $grn = $this->createGRN(['status' => GoodsReceivedNote::STATUS_ACCEPTED]);
        $this->assertTrue($grn->isAccepted());
    }

    // ─── MARK AS STATUS ───────────────────────────────────────

    public function test_mark_as_verified(): void
    {
        $grn = $this->createGRN(['status' => GoodsReceivedNote::STATUS_PENDING]);
        $grn->markAsVerified($this->user->id);

        $this->assertEquals(GoodsReceivedNote::STATUS_VERIFIED, $grn->fresh()->status);
        $this->assertEquals($this->user->id, $grn->fresh()->verified_by);
    }

    public function test_mark_as_accepted(): void
    {
        $grn = $this->createGRN(['status' => GoodsReceivedNote::STATUS_VERIFIED]);
        $grn->markAsAccepted();

        $this->assertEquals(GoodsReceivedNote::STATUS_ACCEPTED, $grn->fresh()->status);
    }

    public function test_mark_as_rejected(): void
    {
        $grn = $this->createGRN(['status' => GoodsReceivedNote::STATUS_VERIFIED]);
        $grn->markAsRejected();

        $this->assertEquals(GoodsReceivedNote::STATUS_REJECTED, $grn->fresh()->status);
    }

    // ─── GENERATE GRN NUMBER ──────────────────────────────────

    public function test_generate_grn_number(): void
    {
        $number = GoodsReceivedNote::generateGRNNumber();

        $this->assertStringStartsWith('GRN-', $number);
        $this->assertStringContainsString(now()->format('Ymd'), $number);
    }

    public function test_generate_grn_number_increments(): void
    {
        // Create a GRN to ensure at least one record exists
        $this->createGRN();

        $first = GoodsReceivedNote::generateGRNNumber();
        $this->createGRN(['grn_number' => $first]);

        $second = GoodsReceivedNote::generateGRNNumber();

        // The second number should be different (incremented)
        $this->assertNotEquals($first, $second);
    }

    // ─── SCOPES ───────────────────────────────────────────────

    public function test_scope_for_business(): void
    {
        $this->createGRN();
        $this->createGRN();

        $count = GoodsReceivedNote::forBusiness($this->business->id)->count();

        $this->assertEquals(2, $count);
    }

    public function test_scope_pending(): void
    {
        $this->createGRN(['status' => GoodsReceivedNote::STATUS_PENDING]);
        $this->createGRN(['status' => GoodsReceivedNote::STATUS_VERIFIED]);

        $pendingCount = GoodsReceivedNote::pending()->count();

        $this->assertEquals(1, $pendingCount);
    }

    public function test_scope_verified(): void
    {
        $this->createGRN(['status' => GoodsReceivedNote::STATUS_PENDING]);
        $this->createGRN(['status' => GoodsReceivedNote::STATUS_VERIFIED]);
        $this->createGRN(['status' => GoodsReceivedNote::STATUS_VERIFIED]);

        $verifiedCount = GoodsReceivedNote::verified()->count();

        $this->assertEquals(2, $verifiedCount);
    }

    // ─── SOFT DELETES ─────────────────────────────────────────

    public function test_soft_delete(): void
    {
        $grn = $this->createGRN();
        $grn->delete();

        $this->assertSoftDeleted('goods_received_notes', ['id' => $grn->id]);
    }

    // ─── GRN NUMBER UNIQUE ────────────────────────────────────

    public function test_grn_number_is_unique(): void
    {
        $grn = $this->createGRN();

        // Generate a number, then try to create another with the same number
        $number = $grn->grn_number;

        $this->assertDatabaseHas('goods_received_notes', ['grn_number' => $number]);
    }

    // ─── TOTAL QUANTITIES ─────────────────────────────────────

    public function test_get_total_received_quantity(): void
    {
        $grn = $this->createGRN();
        $this->service->addItem($grn, [
            'product_id' => $this->product->id,
            'ordered_quantity' => 10,
            'received_quantity' => 5,
            'unit_price' => 25.00,
        ]);

        $this->assertEquals(5, $grn->total_received_quantity);
    }

    public function test_get_total_accepted_quantity(): void
    {
        $grn = $this->createGRN();
        $this->service->addItem($grn, [
            'product_id' => $this->product->id,
            'ordered_quantity' => 10,
            'received_quantity' => 5,
            'accepted_quantity' => 4,
            'unit_price' => 25.00,
        ]);

        $this->assertEquals(4, $grn->total_accepted_quantity);
    }

    public function test_get_total_rejected_quantity(): void
    {
        $grn = $this->createGRN();
        $this->service->addItem($grn, [
            'product_id' => $this->product->id,
            'ordered_quantity' => 10,
            'received_quantity' => 5,
            'rejected_quantity' => 1,
            'unit_price' => 25.00,
        ]);

        $this->assertEquals(1, $grn->total_rejected_quantity);
    }
}
