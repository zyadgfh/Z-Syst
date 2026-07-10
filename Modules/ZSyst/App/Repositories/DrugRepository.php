<?php

namespace Modules\ZSyst\App\Repositories;

use Modules\ZSyst\App\Models\Drug;

class DrugRepository
{
    public function search(?string $search = null, int $limit = 50)
    {
        $query = Drug::query()->where('is_active', true);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%")
                    ->orWhere('scientific_name', 'like', "%{$search}%");
            });
        }

        return $query->latest()->take($limit)->get();
    }

    public function create(array $data): Drug
    {
        return Drug::create($data);
    }

    public function findById(int $id): Drug
    {
        return Drug::findOrFail($id);
    }
}
