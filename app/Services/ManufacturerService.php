<?php

namespace App\Services;

use App\Models\Manufacturer;

class ManufacturerService
{
    public function index(array $filters = [])
    {
        $query = Manufacturer::query();

        if (!empty($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->latest()->paginate(10);
    }

    public function store(array $data)
    {
        $companyId = $data['company_id'] ?? app('tenant.company_id');
        
        return Manufacturer::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'status' => $data['status'] ?? true,
            'company_id' => $companyId,
        ]);
    }

    public function update(Manufacturer $manufacturer, array $data)
    {
        $manufacturer->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? $manufacturer->description,
            'status' => $data['status'] ?? $manufacturer->status,
        ]);

        return $manufacturer->fresh();
    }

    public function delete(Manufacturer $manufacturer)
    {
        $manufacturer->delete();
        return true;
    }
}