<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class ProductAnalyticsController extends Controller
{
    public function index()
    {
        return view('admin.reports.product-analytics');
    }
}
