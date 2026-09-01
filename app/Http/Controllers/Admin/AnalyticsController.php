<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:analytics-read')->only('index');
    }

    public function index()
    {
        return view('admin.analytics.index');
    }
}
