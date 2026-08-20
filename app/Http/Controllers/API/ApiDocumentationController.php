<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;

/**
 * @OA\Info(
 *     title="Z-Syst Pharmacy Management System API",
 *     version="1.0.0",
 *     description="API documentation for Z-Syst Pharmacy Management System",
 *     @OA\Contact(
 *         name="Z-Syst Team",
 *         email="support@z-syst.com"
 *     ),
 *     @OA\License(
 *         name="Proprietary"
 *     )
 * )
 *
 * @OA\Server(
 *     url="http://localhost:8000/api/v1",
 *     description="Local Development Server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */
class ApiDocumentationController extends BaseController
{
    /**
     * Display the Swagger UI documentation
     *
     * @return JsonResponse
     */
    public function documentation(): JsonResponse
    {
        return response()->json([
            'message' => 'API Documentation available at /api/documentation',
        ]);
    }
}