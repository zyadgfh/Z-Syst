<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductCatalogStoreRequest;
use App\Http\Resources\DrugResource;
use App\Models\Drug;
use App\Services\Products\ProductCatalogService;
use Illuminate\Http\Request;

class ProductCatalogController extends Controller
{
    public function __construct(protected ProductCatalogService $service)
    {
    }

    public function index(Request $request)
    {
        $items = $this->service->search($request->query('q'), (int) $request->query('per_page', 25));

        return DrugResource::collection($items)->response();
    }

    public function store(ProductCatalogStoreRequest $request)
    {
        $product = $this->service->create($request->validated() + ['company_id' => $request->user()->company_id ?? app('tenant.company_id')]);

        return (new DrugResource($product))->response()->setStatusCode(201);
    }

    public function show(Drug $drug)
    {
        return (new DrugResource($drug))->response();
    }

    public function update(ProductCatalogStoreRequest $request, Drug $drug)
    {
        $product = $this->service->update($drug, $request->validated());

        return (new DrugResource($product))->response();
    }

    public function destroy(Drug $drug)
    {
        $drug->delete();

        return response()->json(['success' => true, 'message' => 'Product deleted successfully.']);
    }
}
