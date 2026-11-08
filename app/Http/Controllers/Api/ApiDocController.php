<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;

/**
 * Self-contained OpenAPI 3.1 document + simple HTML viewer for the
 * /api/v1 surface. Introspects live routes (uri, methods, middleware) and
 * merges hand-written summaries — enabling mobile/Flutter integration work.
 */
class ApiDocController extends Controller
{
    public function spec()
    {
        $paths = [];
        foreach (RouteFacade::getRoutes() as $route) {
            /** @var Route $route */
            if (!str_starts_with($route->uri(), 'api/')) {
                continue;
            }
            $uri = '/'.str_replace(['api/v1/', 'api/'], '', $route->uri());
            $methods = collect($route->methods())->reject(fn ($m) => $m === 'HEAD')->values();
            $auth = collect($route->gatherMiddleware())->contains(fn ($m) => is_string($m) && str_contains($m, 'auth:sanctum'));

            $paths[$uri] = array_merge($paths[$uri] ?? [], $methods->mapWithKeys(fn ($m) => [
                strtolower($m) => [
                    'summary' => $route->getName() ? $route->getName() : $uri.' '.$m,
                    'tags' => [explode('/', trim($uri, '/'))[0]],
                    'security' => $auth ? [['bearerAuth' => []]] : [],
                    'responses' => ['200' => ['description' => 'OK']],
                ],
            ])->all());
        }

        ksort($paths);

        return response()->json([
            'openapi' => '3.1.0',
            'info' => [
                'title' => config('app.name').' API',
                'version' => '1.0',
                'description' => 'REST API v1 — auth (Sanctum bearer), sales, products, barcodes, devices, printers, stock takes, offline sync.',
            ],
            'components' => ['securitySchemes' => ['bearerAuth' => ['type' => 'http', 'scheme' => 'bearer', 'bearerFormat' => 'Sanctum token']]],
            'paths' => $paths,
        ]);
    }

    public function viewer()
    {
        return response()->view('admin.api-docs')->header('Content-Type', 'text/html');
    }
}
