<?php

declare(strict_types=1);

namespace App\Http\Controllers\API;

use App\Http\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller;

abstract class BaseController extends Controller
{
    use ApiResponse, AuthorizesRequests, ValidatesRequests;
}
