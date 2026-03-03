<?php

namespace Arseno25\LaravelApiMagic\Http\Controllers;

use Arseno25\LaravelApiMagic\Parsers\RequestAnalyzer;
use Arseno25\LaravelApiMagic\Parsers\ResourceAnalyzer;
use Arseno25\LaravelApiMagic\Parsers\RouteAnalyzer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

final class DocsController extends Controller
{
    private const CACHE_PATH = 'bootstrap/cache/api-magic.json';

    public function __construct(
        private readonly RouteAnalyzer $routeAnalyzer,
        private readonly RequestAnalyzer $requestAnalyzer,
        private readonly ResourceAnalyzer $resourceAnalyzer,
    ) {}

    /**
     * Display the API documentation UI.
     */
    public function index(): \Illuminate\Contracts\View\View
    {
        return view('api-magic::docs'); // @phpstan-ignore argument.type
    }

    /**
     * Get API documentation schema as JSON.
     * Uses cache if available, otherwise generates on-the-fly.
     */
    public function json(Request $request): JsonResponse
    {
        $cachedData = $this->getCachedData();

        if ($cachedData) {
            return response()->json($cachedData);
        }

        $schema = $this->generateSchema($request);

        // Add OpenAPI security schemes to the response
        $schema['securitySchemes'] = [
            'bearerAuth' => [
                'type' => 'http',
                'scheme' => 'bearer',
                'bearerFormat' => 'JWT',
                'description' => 'Laravel Sanctum Bearer Token authentication',
            ],
        ];

        return response()->json($schema);
    }

    /**
     * Export documentation as OpenAPI/Postman JSON.
     */
    public function export(Request $request): JsonResponse
    {
        $openApi = $this->getOpenApiSchema($request);

        return response()->json($openApi)
            ->header('Content-Disposition', 'attachment; filename="api-docs-'.date('Y-m-d').'.json"');
    }

    /**
     * Get the raw OpenAPI 3.0 schema array.
     *
     * @return array<string, mixed>
     */
    public function getOpenApiSchema(Request $request): array
    {
        $schema = $this->generateSchema($request);
        return $this->convertToOpenApi($schema, $request);
    }

    /**
     * Get cached documentation data if available.
     *
     * @return array<string, mixed>|null
     */
    private function getCachedData(): ?array
    {
        $cachePath = base_path(self::CACHE_PATH);

        if (! File::exists($cachePath)) {
            return null;
        }

        $cached = json_decode(File::get($cachePath), true);

        // Check if cache is still valid (optional: add TTL check)
        if (! $cached || ! isset($cached['generated_at'])) {
            return null;
        }

        return $cached;
    }

    /**
     * Generate the API documentation schema.
     *
     * @return array<string, mixed>
     */
    private function generateSchema(Request $request): array
    {
        $routes = $this->routeAnalyzer->getApiRoutes();

        // Parse routes once, then group differently
        $parsedRoutes = collect($routes)
            ->map(fn ($route) => $this->routeAnalyzer->parseRoute($route, $this->requestAnalyzer, $this->resourceAnalyzer))
            ->filter()
            ->sortBy([
                ['version', 'asc'],
                ['path', 'asc'],
                ['method', 'asc'],
            ])
            ->values();

        $endpoints = $parsedRoutes
            ->groupBy('path')
            ->map(fn ($group) => $group->keyBy('method'))
            ->toArray();

        $endpointsByVersion = $parsedRoutes
            ->groupBy('version')
            ->map(fn ($group) => $group->groupBy('path')->map(fn ($methods) => $methods->keyBy('method'))->toArray())
            ->toArray();

        return [
            'title' => config('app.name', 'Laravel API').' Documentation',
            'version' => '1.0.0',
            'baseUrl' => $request->getSchemeAndHttpHost(),
            'endpoints' => $endpoints,
            'endpointsByVersion' => $endpointsByVersion,
            'versions' => array_keys($endpointsByVersion),
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Convert internal schema to OpenAPI 3.0 format.
     *
     * @return array<string, mixed>
     */
    private function convertToOpenApi(array $schema, Request $request): array
    {
        $baseUrl = $schema['baseUrl'] ?? $request->getSchemeAndHttpHost();
        $paths = [];
        $versionServers = [];
        $customSchemas = [];

        foreach ($schema['endpoints'] ?? [] as $path => $methods) {
            $pathKey = str_replace($baseUrl, '', $path);

            if (! str_starts_with($pathKey, '/')) {
                $pathKey = '/'.$pathKey;
            }

            $paths[$pathKey] = [];

            foreach ($methods as $method => $endpoint) {
                $operation = [
                    'summary' => $endpoint['summary'] ?? '',
                    'description' => $endpoint['description'] ?? '',
                    'operationId' => strtolower($method).str_replace(['/', '{', '}', '-', '.'], '', $path),
                    'tags' => $endpoint['tags'] ?? ['default'],
                    'responses' => $this->buildOpenApiResponses($method, $endpoint['response'] ?? null),
                ];

                // Add security if endpoint requires authentication
                if (! empty($endpoint['security'] ?? [])) {
                    $requiresAuth = false;
                    foreach ($endpoint['security'] as $sec) {
                        if (isset($sec['type']) && $sec['type'] === 'http' && isset($sec['scheme']) && $sec['scheme'] === 'bearer') {
                            $requiresAuth = true;
                            break;
                        }
                    }
                    if ($requiresAuth) {
                        $operation['security'] = [['bearerAuth' => []]];
                    }
                }

                // Add version info to tags
                $version = $endpoint['version'] ?? '1';
                if ($version !== '1') {
                    $operation['tags'][] = "v{$version}";
                    $operation['tags'] = array_values(array_unique($operation['tags']));
                }

                // Build parameters array (path + query + body)
                $allParameters = [];

                // Add path parameters
                if (! empty($endpoint['parameters']['path'] ?? [])) {
                    $allParameters = array_merge($allParameters, array_map(fn ($param) => [
                        'name' => $param['name'],
                        'in' => 'path',
                        'required' => true,
                        'schema' => ['type' => $param['type'] ?? 'string'],
                        'description' => $param['description'] ?? '',
                    ], $endpoint['parameters']['path']));
                }

                // Add query parameters
                if (! empty($endpoint['parameters']['query'] ?? [])) {
                    $allParameters = array_merge($allParameters, array_map(fn ($param) => [
                        'name' => $param['name'],
                        'in' => 'query',
                        'required' => $param['required'] ?? false,
                        'schema' => $param['schema'] ?? ['type' => $param['type'] ?? 'string'],
                        'description' => $param['description'] ?? '',
                    ], $endpoint['parameters']['query']));
                }

                if (! empty($allParameters)) {
                    $operation['parameters'] = $allParameters;
                }

                // Add request body for POST/PUT/PATCH
                if (in_array($method, ['post', 'put', 'patch']) && ! empty($endpoint['parameters']['body'] ?? [])) {

                    $hasFile = collect($endpoint['parameters']['body'])->contains(fn($field) => isset($field['is_file']) && $field['is_file'] === true);
                    $contentType = $hasFile ? 'multipart/form-data' : 'application/json';

                    $operation['requestBody'] = [
                        'required' => true,
                        'content' => [
                            $contentType => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => $this->buildOpenApiSchemaProperties($endpoint['parameters']['body']),
                                ],
                            ],
                        ],
                    ];

                    if (! $hasFile) {
                        $operation['requestBody']['content'][$contentType]['example'] = $this->buildOpenApiExample($endpoint['parameters']['body']);
                    }
                }

                if (isset($endpoint['response'])) {
                    $customSchemas[$endpoint['response']['name']] = $endpoint['response']['schema'];
                }

                $paths[$pathKey][$method] = $operation;
            }
        }

        // Build servers for different API versions
        $servers = [[
            'url' => $baseUrl,
            'description' => 'API v1',
        ]];

        foreach ($schema['versions'] ?? ['1'] as $version) {
            if ($version !== '1') {
                $servers[] = [
                    'url' => $baseUrl.'/v'.$version,
                    'description' => "API v{$version}",
                ];
            }
        }

        return [
            'openapi' => '3.0.0',
            'info' => [
                'title' => $schema['title'] ?? 'API Documentation',
                'version' => $schema['version'] ?? '1.0.0',
                'description' => 'Auto-generated API documentation',
            ],
            'servers' => $servers,
            'paths' => $paths,
            'components' => [
                'securitySchemes' => [
                    'bearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'JWT',
                        'description' => 'Laravel Sanctum Bearer Token authentication. Enter token without "Bearer" prefix.',
                    ],
                ],
                'schemas' => array_merge([
                    'SuccessResponse' => [
                        'type' => 'object',
                        'properties' => [
                            'data' => ['type' => 'object', 'description' => 'Response data'],
                            'meta' => ['type' => 'object', 'description' => 'Pagination metadata'],
                            'links' => ['type' => 'object', 'description' => 'Pagination links'],
                        ],
                    ],
                    'ResourceResponse' => [
                        'type' => 'object',
                        'properties' => [
                            'data' => ['type' => 'object', 'description' => 'Resource data'],
                        ],
                    ],
                    'ValidationError' => [
                        'type' => 'object',
                        'properties' => [
                            'message' => ['type' => 'string', 'example' => 'The given data was invalid.'],
                            'errors' => ['type' => 'object', 'description' => 'Validation errors grouped by field'],
                        ],
                    ],
                ], $customSchemas),
            ],
            'tags' => $this->buildOpenApiTags($schema),
        ];
    }

    /**
     * Build OpenAPI response definitions.
     *
     * @param array<string, mixed>|null $resourceSchema
     * @return array<mixed>
     */
    private function buildOpenApiResponses(string $method, ?array $resourceSchema = null): array
    {
        $schemaRef = $resourceSchema ? '#/components/schemas/'.$resourceSchema['name'] : '#/components/schemas/ResourceResponse';
        
        return match ($method) {
            'get' => [
                '200' => [
                    'description' => 'Successful response',
                    'content' => [
                        'application/json' => [
                            'schema' => ['$ref' => '#/components/schemas/SuccessResponse'],
                        ],
                    ],
                ],
                '404' => [
                    'description' => 'Resource not found',
                ],
            ],
            'post' => [
                '201' => [
                    'description' => 'Resource created',
                    'content' => [
                        'application/json' => [
                            'schema' => ['$ref' => $schemaRef],
                        ],
                    ],
                ],
                '422' => [
                    'description' => 'Validation error',
                    'content' => [
                        'application/json' => [
                            'schema' => ['$ref' => '#/components/schemas/ValidationError'],
                        ],
                    ],
                ],
            ],
            'put', 'patch' => [
                '200' => [
                    'description' => 'Resource updated',
                    'content' => [
                        'application/json' => [
                            'schema' => ['$ref' => $schemaRef],
                        ],
                    ],
                ],
                '404' => [
                    'description' => 'Resource not found',
                ],
                '422' => [
                    'description' => 'Validation error',
                ],
            ],
            'delete' => [
                '204' => [
                    'description' => 'Resource deleted',
                ],
                '404' => [
                    'description' => 'Resource not found',
                ],
            ],
            default => [
                '200' => [
                    'description' => 'Successful response',
                ],
            ],
        };
    }

    /**
     * Build OpenAPI schema properties from validation rules.
     *
     * @param  array<string, mixed>  $fields
     * @return array<string, mixed>
     */
    private function buildOpenApiSchemaProperties(array $fields): array
    {
        $properties = [];

        foreach ($fields as $name => $field) {
            $schema = [
                'type' => $field['type'] ?? 'string',
                'description' => $field['description'] ?? '',
            ];

            if (! ($field['required'] ?? false)) {
                $schema['nullable'] = true;
            }

            if (isset($field['enum'])) {
                $schema['enum'] = $field['enum'];
            }
            
            if (isset($field['is_file']) && $field['is_file']) {
                $schema['format'] = 'binary';
            }

            $properties[$name] = $schema;
        }

        return $properties;
    }

    /**
     * Build example values for request body.
     *
     * @param  array<string, mixed>  $fields
     * @return array<string, mixed>
     */
    private function buildOpenApiExample(array $fields): array
    {
        $example = [];

        foreach ($fields as $name => $field) {
            if ($field['required'] ?? false) {
                $example[$name] = $this->getExampleValue($field['type'] ?? 'string');
            }
        }

        return $example;
    }

    /**
     * Get example value for a field type.
     */
    private function getExampleValue(string $type): mixed
    {
        return match ($type) {
            'integer', 'number' => 1,
            'boolean' => true,
            'array' => [],
            'email' => 'example@example.com',
            'url' => 'https://example.com',
            'date' => now()->toDateString(),
            'uuid' => (string) Str::uuid(),
            default => 'string',
        };
    }

    /**
     * Build OpenAPI tags from schema.
     *
     * @param  array<string, mixed>  $schema
     * @return array<int, array<string, string>>
     */
    private function buildOpenApiTags(array $schema): array
    {
        $tags = [];

        foreach ($schema['endpoints'] ?? [] as $methods) {
            foreach ($methods as $endpoint) {
                foreach ($endpoint['tags'] ?? ['default'] as $tag) {
                    if (! isset($tags[$tag])) {
                        $tags[$tag] = [
                            'name' => $tag,
                            'description' => "Operations related to {$tag}",
                        ];
                    }
                }
            }
        }

        return array_values($tags);
    }
}
