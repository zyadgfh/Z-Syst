<?php

namespace App\Services;

use App\Models\Category;

class CategoryService
{
    public function index(array $filters = [])
    {
        $query = Category::query();

        if (!empty($filters['search'])) {
            $query->where('categoryName', 'like', '%' . $filters['search'] . '%');
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->latest()->paginate(10);
    }

    public function store(array $data)
    {
        $companyId = $data['company_id'] ?? app('tenant.company_id');
        
        return Category::create([
            'categoryName' => $data['categoryName'],
            'description' => $data['description'] ?? null,
            'status' => $data['status'] ?? true,
            'company_id' => $companyId,
        ]);
    }

    public function update(Category $category, array $data)
    {
        $category->update([
            'categoryName' => $data['categoryName'],
            'description' => $data['description'] ?? $category->description,
            'status' => $data['status'] ?? $category->status,
        ]);

        return $category->fresh();
    }

    public function delete(Category $category)
    {
        $category->delete();
        return true;
    }
}