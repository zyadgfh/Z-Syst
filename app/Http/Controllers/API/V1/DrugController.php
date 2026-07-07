<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Drug;
use Illuminate\Http\Request;

class DrugController extends Controller
{
    public function index(Request $request)
    {
        $drugs = Drug::select('id','uuid','name','generic_name','barcode')->paginate(25);
        return response()->json($drugs);
    }

    public function show(Drug $drug)
    {
        return response()->json($drug);
    }
}
