<?php

return [

    /*
     * Generate an OpenAPI 3.0 document for your API.
     */
    'generate_always' => env('L5_SWAGGER_GENERATE_ALWAYS', false),

    /*
     * Generate a YAML copy of the OpenAPI document.
     */
    'generate_yaml_copy' => env('L5_SWAGGER_GENERATE_YAML_COPY', false),

    /*
     * The path where the generated OpenAPI document will be saved.
     */
    'paths' => [
        'docs' => storage_path('api-docs'),
        'views' => base_path('vendor/l5-swagger/src/views'),
        'annotations' => base_path('app'),
        'base' => env('L5_SWAGGER_BASE_PATH', null),
        'excludes' => [
            'node_modules',
            'vendor',
            'storage',
            'bootstrap/cache',
            'public',
            'tests',
            'app/Console/Commands',
            'app/Exceptions',
            'app/Http/Middleware',
            'app/Models',
            'app/Providers',
        ],
    ],

    /*
     * L5Swagger UI configuration.
     */
    'ui' => [
        'display' => [
            'dark_mode' => env('L5_SWAGGER_UI_DARK_MODE', false),
            'doc_expansion' => env('L5_SWAGGER_UI_DOC_EXPANSION', 'none'),
            'filter' => env('L5_SWAGGER_UI_FILTER', true),
            'try_it_out_enabled' => env('L5_SWAGGER_UI_TRY_IT_OUT', true),
        ],
    ],

    /*
     * Default API documentation configuration.
     */
    'default' => [
        'routes' => [
            'api' => 'api/documentation',
            'docs' => 'api/documentation',
            'oauth2_callback' => 'api/oauth2-callback',
            'assets' => 'api/docs/assets',
        ],
        'paths' => [
            'docs_json' => 'api-docs.json',
            'docs_yaml' => 'api-docs.yaml',
            'annotations' => 'app/Http/Controllers/Api',
            'base' => env('L5_SWAGGER_BASE_PATH', null),
            'excludes' => [],
        ],
        'scan' => [
            'path' => base_path('app/Http/Controllers/Api'),
            'exclude' => [],
        ],
    ],

    /*
     * OpenAPI 3.0 specification metadata.
     */
    'spec' => [
        'openapi' => '3.0.3',
        'info' => [
            'title' => env('L5_SWAGGER_TITLE', 'Z-Syst Pharmacy Management System API'),
            'description' => env('L5_SWAGGER_DESCRIPTION', 'API documentation for Z-Syst Pharmacy Management System'),
            'version' => env('L5_SWAGGER_VERSION', '1.0.0'),
            'contact' => [
                'name' => env('L5_SWAGGER_CONTACT_NAME', 'Z-Syst Team'),
                'email' => env('L5_SWAGGER_CONTACT_EMAIL', 'support@z-syst.com'),
            ],
            'license' => [
                'name' => env('L5_SWAGGER_LICENSE_NAME', 'Proprietary'),
            ],
        ],
        'servers' => [
            [
                'url' => env('L5_SWAGGER_SERVER_URL', 'http://localhost:8000/api/v1'),
                'description' => 'Local Development Server',
            ],
        ],
        'security' => [
            'bearerAuth' => [],
        ],
        'components' => [
            'securitySchemes' => [
                'bearerAuth' => [
                    'type' => 'http',
                    'scheme' => 'bearer',
                    'bearerFormat' => 'JWT',
                ],
            ],
        ],
    ],

    /*
     * Override the default OpenAPI document generator.
     */
    'document' => [
        'api' => \L5Swagger\Http\DocumentGenerator::class,
    ],

    /*
     * Enable/disable the L5Swagger middleware.
     */
    'enabled' => env('L5_SWAGGER_ENABLED', true),

    /*
     * L5Swagger constants.
     */
    'constants' => [
        'L5_SWAGGER_CONST_HOST' => env('L5_SWAGGER_CONST_HOST', 'http://localhost:8000'),
        'L5_SWAGGER_CONST_API_VERSION' => env('L5_SWAGGER_CONST_API_VERSION', 'v1'),
    ],

];