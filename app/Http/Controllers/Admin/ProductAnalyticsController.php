<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class ProductAnalyticsController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:analytics-read')->only(['index','show']);
    }

    public function index()
    {
        return view('admin.reports.product-analytics');
    }
}
