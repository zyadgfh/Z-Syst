<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\DrugResource;
use App\Models\Drug;
use Illuminate\Http\Request;

class DrugController extends Controller
{
    public function index(Request $request)
    {
        $drugs = Drug::select('id', 'uuid', 'name', 'generic_name', 'barcode')
            ->when($request->filled('q'), function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->query('q') . '%')
                    ->orWhere('generic_name', 'like', '%' . $request->query('q') . '%')
                    ->orWhere('barcode', 'like', '%' . $request->query('q') . '%');
            })
            ->latest()
            ->paginate(25);

        return DrugResource::collection($drugs)->response();
    }

    public function show(Drug $drug)
    {
        return (new DrugResource($drug))->response();
    }
}
