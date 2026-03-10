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
     * Get the package version from Composer's installed packages data.
     */
    private function getPackageVersion(): string
    {
        // 1. Try Composer runtime API (works for installed packages via Packagist)
        if (class_exists(\Composer\InstalledVersions::class)) {
            try {
                $version = \Composer\InstalledVersions::getPrettyVersion(
                    'arseno25/laravel-api-magic',
                );
                if ($version !== null) {
                    return $version;
                }
            } catch (\Throwable) {
                // Package not installed via Composer, fall through
            }
        }

        // 2. Fallback: read version from composer.json (for local development)
        try {
            $composerJson = dirname(__DIR__, 2).'/composer.json';
            if (File::exists($composerJson)) {
                $composer = json_decode(File::get($composerJson), true);

                return $composer['version'] ?? '1.0.0';
            }
        } catch (\Throwable) {
            // Ignore
        }

        return '1.0.0';
    }

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
            'oauth2' => [
                'type' => 'oauth2',
                'flows' => [
                    'implicit' => [
                        'authorizationUrl' => config(
                            'api-magic.oauth.auth_url',
                            '',
                        ),
                        'scopes' => config('api-magic.oauth.scopes', []),
                    ],
                ],
            ],
        ];

        // Export custom config variables to frontend
        $schema['oauth'] = [
            'authUrl' => config('api-magic.oauth.auth_url', ''),
            'clientId' => config('api-magic.oauth.client_id', ''),
            'scopes' => config('api-magic.oauth.scopes', []),
        ];

        return response()->json($schema);
    }

    /**
     * Export documentation as OpenAPI or Postman JSON.
     */
    public function export(Request $request): JsonResponse
    {
        $format = strtolower((string) $request->query('format', 'openapi'));

        if (! in_array($format, ['openapi', 'postman', 'insomnia'])) {
            return response()->json(
                [
                    'error' => 'Invalid format. Supported formats are: openapi, postman, insomnia',
                ],
                400,
            );
        }

        if ($format === 'postman') {
            $schema = $this->generateSchema($request);
            $exporter = app(
                \Arseno25\LaravelApiMagic\Exporters\PostmanExporter::class,
            );
            $postman = $exporter->export(
                $schema,
                $request->getSchemeAndHttpHost(),
            );

            return response()
                ->json($postman)
                ->header(
                    'Content-Disposition',
                    'attachment; filename="postman-collection-'.
                        date('Y-m-d').
                        '.json"',
                );
        }

        if ($format === 'insomnia') {
            $schema = $this->generateSchema($request);
            $exporter = app(
                \Arseno25\LaravelApiMagic\Exporters\InsomniaExporter::class,
            );
            $insomnia = $exporter->export(
                $schema,
                $request->getSchemeAndHttpHost(),
            );

            return response()
                ->json($insomnia)
                ->header(
                    'Content-Disposition',
                    'attachment; filename="insomnia-collection-'.
                        date('Y-m-d').
                        '.json"',
                );
        }

        $openApi = $this->getOpenApiSchema($request);

        return response()
            ->json($openApi)
            ->header(
                'Content-Disposition',
                'attachment; filename="api-docs-'.date('Y-m-d').'.json"',
            );
    }

    /**
     * Get the internal API schema (for use by artisan commands).
     *
     * @return array<string, mixed>
     */
    public function generateSchemaPublic(Request $request): array
    {
        return $this->generateSchema($request);
    }

    /**
     * Get API health metrics.
     */
    public function health(): JsonResponse
    {
        if (! config('api-magic.health.enabled', false)) {
            return response()->json(
                ['message' => 'Health telemetry is disabled.'],
                404,
            );
        }

        $metrics = \Arseno25\LaravelApiMagic\Http\Middleware\ApiHealthMiddleware::getMetrics();

        return response()->json([
            'metrics' => $metrics,
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Get API changelog (diff between schema snapshots).
     */
    public function changelog(): JsonResponse
    {
        if (! config('api-magic.changelog.enabled', false)) {
            return response()->json(
                ['message' => 'Changelog is disabled.'],
                404,
            );
        }

        $service = new \Arseno25\LaravelApiMagic\Services\ChangelogService;
        $snapshots = $service->getSnapshots();

        if (count($snapshots) < 2) {
            return response()->json([
                'message' => 'Not enough snapshots for comparison. Run: php artisan api-magic:snapshot',
                'snapshots' => count($snapshots),
            ]);
        }

        // Compare last two snapshots
        $current = json_decode(file_get_contents($snapshots[0]['path']), true);
        $previous = json_decode(file_get_contents($snapshots[1]['path']), true);

        $diff = $service->computeDiff($previous, $current);

        return response()->json([
            'diff' => $diff,
            'current_snapshot' => $snapshots[0]['date'],
            'previous_snapshot' => $snapshots[1]['date'],
            'total_snapshots' => count($snapshots),
        ]);
    }

    /**
     * Generate code snippet for a specific endpoint.
     */
    public function codeSnippet(Request $request): JsonResponse
    {
        $method = $request->query('method', 'get');
        $path = $request->query('path', '/');
        $baseUrl = $request->query(
            'base_url',
            $request->getSchemeAndHttpHost(),
        );

        $schema = $this->generateSchema($request);
        $endpoint = $schema['endpoints'][$path][$method] ?? null;

        if (! $endpoint) {
            return response()->json(['message' => 'Endpoint not found.'], 404);
        }

        $generator = new \Arseno25\LaravelApiMagic\Generators\CodeSnippetGenerator;
        $snippets = $generator->generate($method, $path, $endpoint, $baseUrl);

        return response()->json(['snippets' => $snippets]);
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
        \Arseno25\LaravelApiMagic\LaravelApiMagic::callBeforeParse();

        $routes = $this->routeAnalyzer->getApiRoutes();

        // Parse routes once, then group differently
        $parsedRoutes = collect($routes)
            ->map(
                fn ($route) => $this->routeAnalyzer->parseRoute(
                    $route,
                    $this->requestAnalyzer,
                    $this->resourceAnalyzer,
                ),
            )
            ->filter()
            ->sortBy([['version', 'asc'], ['path', 'asc'], ['method', 'asc']])
            ->values();

        $endpoints = $parsedRoutes
            ->groupBy('path')
            ->map(fn ($group) => $group->keyBy('method'))
            ->toArray();

        $endpointsByVersion = $parsedRoutes
            ->groupBy('version')
            ->map(
                fn ($group) => $group
                    ->groupBy('path')
                    ->map(fn ($methods) => $methods->keyBy('method'))
                    ->toArray(),
            )
            ->toArray();

        // Collect all webhooks from all endpoints
        $webhooks = $parsedRoutes
            ->pluck('webhooks')
            ->filter()
            ->flatten(1)
            ->unique('event')
            ->values()
            ->toArray();

        // Collect all broadcasting events
        $eventAnalyzer = new \Arseno25\LaravelApiMagic\Parsers\EventAnalyzer;
        $events = $eventAnalyzer->analyze();

        $schema = [
            'title' => config('app.name', 'Laravel API').' Documentation',
            'version' => $this->getPackageVersion(),
            'baseUrl' => $request->getSchemeAndHttpHost(),
            'servers' => config('api-magic.servers', []),
            'endpoints' => $endpoints,
            'endpointsByVersion' => $endpointsByVersion,
            'versions' => array_keys($endpointsByVersion),
            'webhooks' => $webhooks,
            'events' => $events,
            'features' => [
                'health' => config('api-magic.health.enabled', false),
                'changelog' => config('api-magic.changelog.enabled', false),
            ],
            'generated_at' => now()->toIso8601String(),
        ];

        \Arseno25\LaravelApiMagic\LaravelApiMagic::callAfterParse($schema);

        return $schema;
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
                    'operationId' => strtolower($method).
                        str_replace(['/', '{', '}', '-', '.'], '', $path),
                    'tags' => $endpoint['tags'] ?? ['default'],
                    'responses' => $this->buildOpenApiResponses(
                        $method,
                        $endpoint['response'] ?? null,
                    ),
                ];

                // Add deprecated flag
                if (! empty($endpoint['deprecated'])) {
                    $operation['deprecated'] = true;
                    if (! empty($endpoint['deprecated_info']['message'])) {
                        $operation['description'] = trim(
                            ($operation['description']
                                ? $operation['description']."\n\n"
                                : '').
                                '⚠️ **Deprecated**: '.
                                ($endpoint['deprecated_info']['message'] ??
                                    '').
                                ($endpoint['deprecated_info']['alternative']
                                    ? "\n\nUse `".
                                        $endpoint['deprecated_info'][
                                            'alternative'
                                        ].
                                        '` instead.'
                                    : ''),
                        );
                    }
                }

                // Override responses with #[ApiResponse] definitions if present
                if (! empty($endpoint['responses'])) {
                    $operation['responses'] = [];
                    foreach ($endpoint['responses'] as $resp) {
                        $statusCode = (string) $resp['status'];
                        $responseContent = [
                            'description' => $resp['description'] ?: "Status {$statusCode}",
                        ];

                        if ($resp['example']) {
                            $responseContent['content'] = [
                                'application/json' => [
                                    'example' => $resp['example'],
                                ],
                            ];
                        }

                        $operation['responses'][$statusCode] = $responseContent;
                    }
                }

                // Add security if endpoint requires authentication
                if (! empty($endpoint['security'] ?? [])) {
                    $requiresAuth = false;
                    foreach ($endpoint['security'] as $sec) {
                        if (
                            isset($sec['type']) &&
                            $sec['type'] === 'http' &&
                            isset($sec['scheme']) &&
                            $sec['scheme'] === 'bearer'
                        ) {
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
                    $operation['tags'] = array_values(
                        array_unique($operation['tags']),
                    );
                }

                // Build parameters array (path + query + body)
                $allParameters = [];

                // Add path parameters
                if (! empty($endpoint['parameters']['path'] ?? [])) {
                    $allParameters = array_merge(
                        $allParameters,
                        array_map(
                            fn ($param) => [
                                'name' => $param['name'],
                                'in' => 'path',
                                'required' => true,
                                'schema' => [
                                    'type' => $param['type'] ?? 'string',
                                ],
                                'description' => $param['description'] ?? '',
                            ],
                            $endpoint['parameters']['path'],
                        ),
                    );
                }

                // Add query parameters
                if (! empty($endpoint['parameters']['query'] ?? [])) {
                    $allParameters = array_merge(
                        $allParameters,
                        array_map(
                            fn ($param) => [
                                'name' => $param['name'],
                                'in' => 'query',
                                'required' => $param['required'] ?? false,
                                'schema' => $param['schema'] ?? [
                                    'type' => $param['type'] ?? 'string',
                                ],
                                'description' => $param['description'] ?? '',
                            ],
                            $endpoint['parameters']['query'],
                        ),
                    );
                }

                if (! empty($allParameters)) {
                    $operation['parameters'] = $allParameters;
                }

                // Add request body for POST/PUT/PATCH
                if (
                    in_array($method, ['post', 'put', 'patch']) &&
                    ! empty($endpoint['parameters']['body'] ?? [])
                ) {
                    $hasFile = collect(
                        $endpoint['parameters']['body'],
                    )->contains(
                        fn ($field) => isset($field['is_file']) &&
                            $field['is_file'] === true,
                    );
                    $contentType = $hasFile
                        ? 'multipart/form-data'
                        : 'application/json';

                    $operation['requestBody'] = [
                        'required' => true,
                        'content' => [
                            $contentType => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => $this->buildOpenApiSchemaProperties(
                                        $endpoint['parameters']['body'],
                                    ),
                                ],
                            ],
                        ],
                    ];

                    if (! $hasFile) {
                        $operation['requestBody']['content'][$contentType][
                            'example'
                        ] = $this->buildOpenApiExample(
                            $endpoint['parameters']['body'],
                        );
                    }
                }

                if (isset($endpoint['response'])) {
                    $customSchemas[$endpoint['response']['name']] =
                        $endpoint['response']['schema'];
                }

                $paths[$pathKey][$method] = $operation;
            }
        }

        // Build servers: use config first, then add version-based servers
        $configServers = $schema['servers'] ?? [];
        $servers = [];

        if (! empty($configServers)) {
            foreach ($configServers as $configServer) {
                $servers[] = [
                    'url' => $configServer['url'] ?? $baseUrl,
                    'description' => $configServer['description'] ?? 'Server',
                ];
            }
        } else {
            $servers[] = [
                'url' => $baseUrl,
                'description' => 'API v1',
            ];
        }

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
                'schemas' => array_merge(
                    [
                        'SuccessResponse' => [
                            'type' => 'object',
                            'properties' => [
                                'data' => [
                                    'type' => 'object',
                                    'description' => 'Response data',
                                ],
                                'meta' => [
                                    'type' => 'object',
                                    'description' => 'Pagination metadata',
                                ],
                                'links' => [
                                    'type' => 'object',
                                    'description' => 'Pagination links',
                                ],
                            ],
                        ],
                        'ResourceResponse' => [
                            'type' => 'object',
                            'properties' => [
                                'data' => [
                                    'type' => 'object',
                                    'description' => 'Resource data',
                                ],
                            ],
                        ],
                        'ValidationError' => [
                            'type' => 'object',
                            'properties' => [
                                'message' => [
                                    'type' => 'string',
                                    'example' => 'The given data was invalid.',
                                ],
                                'errors' => [
                                    'type' => 'object',
                                    'description' => 'Validation errors grouped by field',
                                ],
                            ],
                        ],
                    ],
                    $customSchemas,
                ),
            ],
            'tags' => $this->buildOpenApiTags($schema),
        ];
    }

    /**
     * Build OpenAPI response definitions.
     *
     * @param  array<string, mixed>|null  $resourceSchema
     * @return array<mixed>
     */
    private function buildOpenApiResponses(
        string $method,
        ?array $resourceSchema = null,
    ): array {
        $schemaRef = $resourceSchema
            ? '#/components/schemas/'.$resourceSchema['name']
            : '#/components/schemas/ResourceResponse';

        return match ($method) {
            'get' => [
                '200' => [
                    'description' => 'Successful response',
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/SuccessResponse',
                            ],
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
                            'schema' => [
                                '$ref' => '#/components/schemas/ValidationError',
                            ],
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
                $example[$name] = $this->getExampleValue(
                    $field['type'] ?? 'string',
                );
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
