<?php

namespace Tests\Unit;

use App\Http\Controllers\SupplierController;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

class SupplierControllerTest extends TestCase
{
    public function test_store_creates_supplier_payload(): void
    {
        $controller = new SupplierController();
        $request = Request::create('/suppliers', 'POST', [
            'name' => 'ABC Pharma',
            'phone' => '01000000000',
            'email' => 'sales@abc.com',
            'address' => 'Cairo',
        ]);

        $response = $controller->store($request);

        $this->assertSame(201, $response->getStatusCode());
    }
}
