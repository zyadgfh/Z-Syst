<?php

namespace App\Http\Controllers\Api;

use App\Models\Party;
use App\Models\Business;
use App\Helpers\HasUploader;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use App\Services\PartyLedgerService;
use Illuminate\Support\Facades\Storage;

class DefaultLangController extends Controller
{
    use HasUploader;

    public function index()
    {
        $default_lang = get_option('general')['default_lang'] ?? '';

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data'    => $default_lang,
        ]);
    }
}
