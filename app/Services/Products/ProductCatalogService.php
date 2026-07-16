<?php

namespace App\Services\Products;

use App\Models\Drug;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class ProductCatalogService
{
    public function create(array $data): Drug
    {
        $companyId = $data['company_id'] ?? app('tenant.company_id');

        $payload = [
            'company_id' => $companyId,
            'uuid' => (string) Str::uuid(),
            'name' => $data['name'],
            'generic_name' => $data['generic_name'] ?? null,
            'barcode' => $data['barcode'] ?? Str::slug($data['name']),
            'manufacturer' => $data['manufacturer'] ?? null,
            'form' => $data['form'] ?? null,
            'strength' => $data['strength'] ?? null,
            'notes' => $data['notes'] ?? null,
        ];

        return Drug::create($payload);
    }

    public function update(Drug $drug, array $data): Drug
    {
        $drug->fill($data);
        $drug->save();

        return $drug->fresh();
    }

    public function search(?string $query, int $perPage = 25): LengthAwarePaginator
    {
        return Drug::query()
            ->when($query, function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('generic_name', 'like', "%{$query}%")
                    ->orWhere('barcode', 'like', "%{$query}%");
            })
            ->latest()
            ->paginate($perPage);
    }

    public function findByBarcode(string $barcode): ?Drug
    {
        return Drug::where('barcode', $barcode)->first();
    }
}
