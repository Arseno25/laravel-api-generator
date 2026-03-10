<?php

namespace Arseno25\LaravelApiMagic\Services;

use Arseno25\LaravelApiMagic\LaravelApiMagic;
use Arseno25\LaravelApiMagic\Parsers\EventAnalyzer;
use Arseno25\LaravelApiMagic\Parsers\RequestAnalyzer;
use Arseno25\LaravelApiMagic\Parsers\ResourceAnalyzer;
use Arseno25\LaravelApiMagic\Parsers\RouteAnalyzer;
use Composer\InstalledVersions;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

final class DocumentationSchemaBuilder
{
    public function __construct(
        private readonly RouteAnalyzer $routeAnalyzer,
        private readonly RequestAnalyzer $requestAnalyzer,
        private readonly ResourceAnalyzer $resourceAnalyzer,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function buildInternalSchema(Request $request): array
    {
        LaravelApiMagic::callBeforeParse();

        $parsedRoutes = collect($this->routeAnalyzer->getApiRoutes())
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
            ->map(fn (Collection $group) => $group->keyBy('method'))
            ->toArray();

        $endpointsByVersion = $parsedRoutes
            ->groupBy('version')
            ->map(
                fn (Collection $group) => $group
                    ->groupBy('path')
                    ->map(fn (Collection $methods) => $methods->keyBy('method'))
                    ->toArray(),
            )
            ->toArray();

        $schema = [
            'title' => config('app.name', 'Laravel API').' Documentation',
            'version' => $this->getPackageVersion(),
            'baseUrl' => $request->getSchemeAndHttpHost(),
            'servers' => config('api-magic.servers', []),
            'endpoints' => $endpoints,
            'endpointsByVersion' => $endpointsByVersion,
            'versions' => array_keys($endpointsByVersion),
            'webhooks' => $parsedRoutes
                ->pluck('webhooks')
                ->filter()
                ->flatten(1)
                ->unique('event')
                ->values()
                ->toArray(),
            'events' => new EventAnalyzer()->analyze(),
            'features' => [
                'health' => config('api-magic.health.enabled', false),
                'changelog' => config('api-magic.changelog.enabled', false),
            ],
            'generated_at' => now()->toIso8601String(),
        ];

        LaravelApiMagic::callAfterParse($schema);

        return $schema;
    }

    /**
     * @return array<string, mixed>
     */
    public function buildUiSchema(Request $request): array
    {
        $schema = $this->buildInternalSchema($request);
        $schema['securitySchemes'] = $this->buildSecuritySchemes();
        $schema['oauth'] = [
            'authUrl' => config('api-magic.oauth.auth_url', ''),
            'clientId' => config('api-magic.oauth.client_id', ''),
            'scopes' => config('api-magic.oauth.scopes', []),
        ];

        return $schema;
    }

    /**
     * @return array<string, mixed>
     */
    public function buildOpenApiSchema(Request $request): array
    {
        return $this->convertToOpenApi(
            $this->buildInternalSchema($request),
            $request,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function convertToOpenApi(array $schema, Request $request): array
    {
        $baseUrl = $schema['baseUrl'] ?? $request->getSchemeAndHttpHost();
        $paths = [];
        $componentSchemas = $this->defaultComponentSchemas();

        foreach ($schema['endpoints'] ?? [] as $path => $methods) {
            $pathKey = $this->normalizeOpenApiPath($path, $baseUrl);
            $paths[$pathKey] = [];

            foreach ($methods as $method => $endpoint) {
                $responseRef = $this->registerResponseSchema(
                    $componentSchemas,
                    $endpoint['response'] ?? null,
                );
                $customResponseRefs = $this->registerCustomResponseSchemas(
                    $componentSchemas,
                    $endpoint['responses'] ?? [],
                );

                $requestBody = $this->buildOpenApiRequestBody(
                    $componentSchemas,
                    $endpoint,
                );

                $operation = [
                    'summary' => $endpoint['summary'] ?? '',
                    'description' => $endpoint['description'] ?? '',
                    'operationId' => $this->buildOperationId(
                        $method,
                        $path,
                        $endpoint,
                    ),
                    'tags' => $endpoint['tags'] ?? ['default'],
                    'responses' => $this->buildOpenApiResponses(
                        $method,
                        $endpoint,
                        $responseRef,
                        $customResponseRefs,
                        $componentSchemas,
                    ),
                ];

                if ($requestBody !== null) {
                    $operation['requestBody'] = $requestBody;
                }

                $parameters = $this->buildOpenApiParameters($endpoint);
                if ($parameters !== []) {
                    $operation['parameters'] = $parameters;
                }

                if (! empty($endpoint['deprecated'])) {
                    $operation['deprecated'] = true;
                }

                $security = $this->buildOpenApiSecurity($endpoint);
                if ($security !== []) {
                    $operation['security'] = $security;
                }

                $paths[$pathKey][$method] = $operation;
            }
        }

        return [
            'openapi' => '3.0.0',
            'info' => [
                'title' => $schema['title'] ?? 'API Documentation',
                'version' => $schema['version'] ?? '1.0.0',
                'description' => 'Auto-generated API documentation',
            ],
            'servers' => $this->buildOpenApiServers($schema, $baseUrl),
            'paths' => $paths,
            'components' => [
                'securitySchemes' => $this->buildSecuritySchemes(),
                'schemas' => $componentSchemas,
            ],
            'tags' => $this->buildOpenApiTags($schema),
        ];
    }

    /**
     * @param  array<string, mixed>  $componentSchemas
     * @param  array<string, mixed>|null  $responseSchema
     */
    private function registerResponseSchema(
        array &$componentSchemas,
        ?array $responseSchema,
    ): ?string {
        if (
            $responseSchema === null ||
            ! isset($responseSchema['name'], $responseSchema['schema'])
        ) {
            return null;
        }

        $componentSchemas[$responseSchema['name']] = $responseSchema['schema'];

        if ($this->schemaLooksLikeResourcePayload($responseSchema['schema'])) {
            $envelopeName = $responseSchema['name'].'Response';
            $componentSchemas[$envelopeName] = [
                'type' => 'object',
                'properties' => [
                    'data' => [
                        '$ref' => '#/components/schemas/'.$responseSchema['name'],
                    ],
                ],
            ];

            return '#/components/schemas/'.$envelopeName;
        }

        return '#/components/schemas/'.$responseSchema['name'];
    }

    /**
     * @param  array<string, mixed>  $componentSchemas
     * @param  array<int, array<string, mixed>>  $responses
     * @return array<string, string>
     */
    private function registerCustomResponseSchemas(
        array &$componentSchemas,
        array $responses,
    ): array {
        $references = [];

        foreach ($responses as $response) {
            $resourceClass = $response['resource'] ?? null;

            if (! is_string($resourceClass) || $resourceClass === '') {
                continue;
            }

            $analyzed = $this->resourceAnalyzer->analyzeResourceClass(
                $resourceClass,
            );

            if (
                $analyzed === null ||
                ! isset($analyzed['name'], $analyzed['schema'])
            ) {
                continue;
            }

            $componentSchemas[$analyzed['name']] = $analyzed['schema'];

            $responseComponentName =
                $response['is_array'] ?? false
                    ? $analyzed['name'].'CollectionResponse'
                    : $analyzed['name'].'Response';

            $componentSchemas[$responseComponentName] =
                $response['is_array'] ?? false
                    ? [
                        'type' => 'object',
                        'properties' => [
                            'data' => [
                                'type' => 'array',
                                'items' => [
                                    '$ref' => '#/components/schemas/'.
                                        $analyzed['name'],
                                ],
                            ],
                        ],
                    ]
                    : [
                        'type' => 'object',
                        'properties' => [
                            'data' => [
                                '$ref' => '#/components/schemas/'.$analyzed['name'],
                            ],
                        ],
                    ];

            $references[(string) ($response['status'] ?? 200)] =
                '#/components/schemas/'.$responseComponentName;
        }

        return $references;
    }

    /**
     * @param  array<string, mixed>  $componentSchemas
     * @param  array<string, mixed>  $endpoint
     * @return array<string, mixed>|null
     */
    private function buildOpenApiRequestBody(
        array &$componentSchemas,
        array $endpoint,
    ): ?array {
        $fields = $endpoint['parameters']['body'] ?? [];
        if (
            ! in_array(
                $endpoint['method'] ?? '',
                ['post', 'put', 'patch'],
                true,
            ) ||
            $fields === []
        ) {
            return null;
        }

        $componentName = $this->buildRequestComponentName($endpoint);
        $hasFile = collect($fields)->contains(
            fn (array $field) => ($field['is_file'] ?? false) === true,
        );
        $componentSchemas[$componentName] = $this->buildObjectSchema($fields);

        $requestBody = [
            'required' => true,
            'content' => $hasFile
                ? [
                    'multipart/form-data' => [
                        'schema' => [
                            '$ref' => '#/components/schemas/'.$componentName,
                        ],
                    ],
                ]
                : [
                    'application/json' => [
                        'schema' => [
                            '$ref' => '#/components/schemas/'.$componentName,
                        ],
                    ],
                    'application/x-www-form-urlencoded' => [
                        'schema' => [
                            '$ref' => '#/components/schemas/'.$componentName,
                        ],
                    ],
                ],
        ];

        $requestExample = $endpoint['example']['request'] ?? null;
        if ($requestExample !== null) {
            foreach (array_keys($requestBody['content']) as $contentType) {
                $requestBody['content'][$contentType][
                    'example'
                ] = $requestExample;
            }
        } elseif (! $hasFile) {
            $example = $this->buildExampleFromFields($fields);

            foreach (array_keys($requestBody['content']) as $contentType) {
                $requestBody['content'][$contentType]['example'] = $example;
            }
        }

        return $requestBody;
    }

    /**
     * @param  array<string, mixed>  $endpoint
     * @return array<int, array<string, mixed>>
     */
    private function buildOpenApiParameters(array $endpoint): array
    {
        $parameters = [];

        foreach ($endpoint['parameters']['path'] ?? [] as $parameter) {
            $parameters[] = [
                'name' => $parameter['name'],
                'in' => 'path',
                'required' => true,
                'description' => $parameter['description'] ?? '',
                'schema' => $this->normalizeFieldSchema($parameter),
            ];
        }

        foreach ($endpoint['parameters']['query'] ?? [] as $parameter) {
            $schema =
                $parameter['schema'] ?? $this->normalizeFieldSchema($parameter);
            $parameters[] = [
                'name' => $parameter['name'],
                'in' => 'query',
                'required' => $parameter['required'] ?? false,
                'description' => $parameter['description'] ?? '',
                'schema' => $schema,
            ];
        }

        return $parameters;
    }

    /**
     * @param  array<string, mixed>  $endpoint
     * @return array<int, array<string, mixed>>
     */
    private function buildOpenApiSecurity(array $endpoint): array
    {
        $security = [];

        foreach ($endpoint['security'] ?? [] as $definition) {
            if (
                ($definition['type'] ?? null) === 'http' &&
                ($definition['scheme'] ?? null) === 'bearer'
            ) {
                $security[] = ['bearerAuth' => []];
            }
        }

        return $security;
    }

    /**
     * @param  array<string, mixed>  $endpoint
     * @return array<string, mixed>
     */
    private function buildOpenApiResponses(
        string $method,
        array $endpoint,
        ?string $responseRef,
        array $customResponseRefs,
        array $componentSchemas,
    ): array {
        if (! empty($endpoint['responses'])) {
            $responses = [];

            foreach ($endpoint['responses'] as $response) {
                $statusCode = (string) $response['status'];
                $responseContent = [
                    'description' => $response['description'] ?:
                        'HTTP '.$response['status'],
                ];

                if (isset($customResponseRefs[$statusCode])) {
                    $responseContent['content'] = [
                        'application/json' => [
                            'schema' => [
                                '$ref' => $customResponseRefs[$statusCode],
                            ],
                        ],
                    ];

                    $example =
                        $response['example'] ??
                        $this->buildExampleFromReference(
                            $customResponseRefs[$statusCode],
                            $componentSchemas,
                        );

                    if ($example !== null) {
                        $responseContent['content']['application/json'][
                            'example'
                        ] = $example;
                    }
                } elseif ($response['example']) {
                    $responseContent['content'] = [
                        'application/json' => [
                            'example' => $response['example'],
                        ],
                    ];
                }

                $responses[$statusCode] = $responseContent;
            }

            return $responses;
        }

        $successStatus = match ($method) {
            'post' => '201',
            'delete' => '204',
            default => '200',
        };

        $successResponse = [
            'description' => match ($method) {
                'post' => 'Resource created successfully',
                'put', 'patch' => 'Resource updated successfully',
                'delete' => 'Resource deleted successfully',
                default => 'Successful response',
            },
        ];

        if ($method !== 'delete') {
            $resolvedResponseRef =
                $responseRef ?? $this->defaultResponseReference($endpoint);
            $successResponse['content'] = [
                'application/json' => [
                    'schema' => [
                        '$ref' => $resolvedResponseRef,
                    ],
                ],
            ];

            if (isset($endpoint['example']['response'])) {
                $successResponse['content']['application/json']['example'] =
                    $endpoint['example']['response'];
            } else {
                $generatedExample = $this->buildExampleFromReference(
                    $resolvedResponseRef,
                    $componentSchemas,
                );

                if ($generatedExample !== null) {
                    $successResponse['content']['application/json'][
                        'example'
                    ] = $generatedExample;
                }
            }
        }

        $responses = [$successStatus => $successResponse];

        if ($method !== 'post') {
            $responses['404'] = [
                'description' => 'Resource not found',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            '$ref' => '#/components/schemas/ErrorResponse',
                        ],
                    ],
                ],
            ];
        }

        if (in_array($method, ['post', 'put', 'patch'], true)) {
            $responses['422'] = [
                'description' => 'Validation error',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            '$ref' => '#/components/schemas/ValidationError',
                        ],
                    ],
                ],
            ];
        }

        if ($this->buildOpenApiSecurity($endpoint) !== []) {
            $responses['401'] = [
                'description' => 'Unauthenticated',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            '$ref' => '#/components/schemas/ErrorResponse',
                        ],
                    ],
                ],
            ];
            $responses['403'] = [
                'description' => 'Forbidden',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            '$ref' => '#/components/schemas/ErrorResponse',
                        ],
                    ],
                ],
            ];
        }

        return $responses;
    }

    /**
     * @param  array<string, mixed>  $schema
     * @return array<int, array<string, string>>
     */
    private function buildOpenApiTags(array $schema): array
    {
        $tags = [];

        foreach ($schema['endpoints'] ?? [] as $methods) {
            foreach ($methods as $endpoint) {
                foreach ($endpoint['tags'] ?? ['default'] as $tag) {
                    $tags[$tag] ??= [
                        'name' => $tag,
                        'description' => 'Operations related to '.$tag,
                    ];
                }
            }
        }

        return array_values($tags);
    }

    /**
     * @param  array<string, mixed>  $schema
     * @return array<int, array<string, string>>
     */
    private function buildOpenApiServers(array $schema, string $baseUrl): array
    {
        $configuredServers = $schema['servers'] ?? [];
        if ($configuredServers !== []) {
            return array_map(
                fn (array $server) => [
                    'url' => $server['url'] ?? $baseUrl,
                    'description' => $server['description'] ?? 'Server',
                ],
                $configuredServers,
            );
        }

        return [
            [
                'url' => $baseUrl,
                'description' => 'Current environment',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSecuritySchemes(): array
    {
        return [
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
    }

    /**
     * @param  array<string, mixed>  $fields
     * @return array<string, mixed>
     */
    private function buildObjectSchema(array $fields): array
    {
        $schema = [
            'type' => 'object',
            'properties' => [],
        ];

        foreach ($fields as $name => $field) {
            $this->insertFieldIntoSchema(
                $schema,
                explode('.', (string) $name),
                $field,
            );
        }

        return $this->cleanupSchemaNode($schema);
    }

    /**
     * @param  array<string, mixed>  $field
     * @return array<string, mixed>
     */
    private function normalizeFieldSchema(array $field): array
    {
        if (($field['is_file'] ?? false) === true) {
            return [
                'type' => 'string',
                'format' => 'binary',
                'description' => $field['description'] ?? '',
            ];
        }

        $type = $field['type'] ?? 'string';
        $schema = match ($type) {
            'integer' => ['type' => 'integer'],
            'number' => ['type' => 'number'],
            'boolean' => ['type' => 'boolean'],
            'array' => ['type' => 'array', 'items' => ['type' => 'string']],
            'email' => ['type' => 'string', 'format' => 'email'],
            'url' => ['type' => 'string', 'format' => 'uri'],
            'date' => ['type' => 'string', 'format' => 'date'],
            'uuid' => ['type' => 'string', 'format' => 'uuid'],
            default => ['type' => 'string'],
        };

        if (($field['required'] ?? false) === false) {
            $schema['nullable'] = true;
        }

        if (isset($field['description'])) {
            $schema['description'] = $field['description'];
        }

        if (isset($field['enum']) && is_array($field['enum'])) {
            $schema['enum'] = $field['enum'];
        }

        return $schema;
    }

    /**
     * @param  array<string, mixed>  $fields
     * @return array<string, mixed>
     */
    private function buildExampleFromFields(array $fields): array
    {
        $example = [];

        foreach ($fields as $name => $field) {
            if (($field['required'] ?? false) !== true) {
                continue;
            }

            $this->insertExampleValue(
                $example,
                explode('.', (string) $name),
                $this->exampleValueForType($field['type'] ?? 'string'),
            );
        }

        return $example;
    }

    /**
     * @param  array<string, mixed>  $schema
     * @param  list<string>  $segments
     * @param  array<string, mixed>  $field
     */
    private function insertFieldIntoSchema(
        array &$schema,
        array $segments,
        array $field,
    ): void {
        $segment = array_shift($segments);

        if ($segment === null) {
            return;
        }

        if ($segment === '*') {
            $schema['type'] = 'array';
            $schema['items'] ??= ['type' => 'object', 'properties' => []];

            if ($segments === []) {
                $schema['items'] = $this->normalizeFieldSchema($field);

                return;
            }

            if (($schema['items']['type'] ?? null) !== 'object') {
                $schema['items'] = ['type' => 'object', 'properties' => []];
            }

            $this->insertFieldIntoSchema($schema['items'], $segments, $field);

            return;
        }

        $schema['type'] = 'object';
        $schema['properties'] ??= [];

        if ($segments === []) {
            $schema['properties'][$segment] = $this->normalizeFieldSchema(
                $field,
            );

            if (($field['required'] ?? false) === true) {
                $schema['required'] ??= [];
                if (! in_array($segment, $schema['required'], true)) {
                    $schema['required'][] = $segment;
                }
            }

            return;
        }

        $nextSegment = $segments[0];
        $schema['properties'][$segment] ??=
            $nextSegment === '*'
                ? [
                    'type' => 'array',
                    'items' => ['type' => 'object', 'properties' => []],
                ]
                : ['type' => 'object', 'properties' => []];

        if (($field['required'] ?? false) === true) {
            $schema['required'] ??= [];
            if (! in_array($segment, $schema['required'], true)) {
                $schema['required'][] = $segment;
            }
        }

        $this->insertFieldIntoSchema(
            $schema['properties'][$segment],
            $segments,
            $field,
        );
    }

    /**
     * @param  array<string, mixed>  $example
     * @param  list<string>  $segments
     */
    private function insertExampleValue(
        array &$example,
        array $segments,
        mixed $value,
    ): void {
        $segment = array_shift($segments);

        if ($segment === null) {
            return;
        }

        if ($segment === '*') {
            $example[0] ??= [];

            if ($segments === []) {
                $example[0] = $value;

                return;
            }

            if (! is_array($example[0])) {
                $example[0] = [];
            }

            $this->insertExampleValue($example[0], $segments, $value);

            return;
        }

        if ($segments === []) {
            $example[$segment] = $value;

            return;
        }

        $example[$segment] ??= $segments[0] === '*' ? [] : [];
        $this->insertExampleValue($example[$segment], $segments, $value);
    }

    /**
     * @param  array<string, mixed>  $schema
     * @return array<string, mixed>
     */
    private function cleanupSchemaNode(array $schema): array
    {
        if (
            ($schema['type'] ?? null) === 'object' &&
            isset($schema['properties'])
        ) {
            foreach ($schema['properties'] as $property => $childSchema) {
                if (is_array($childSchema)) {
                    $schema['properties'][$property] = $this->cleanupSchemaNode(
                        $childSchema,
                    );
                }
            }

            if (($schema['required'] ?? []) === []) {
                unset($schema['required']);
            }
        }

        if (
            ($schema['type'] ?? null) === 'array' &&
            isset($schema['items']) &&
            is_array($schema['items'])
        ) {
            $schema['items'] = $this->cleanupSchemaNode($schema['items']);
        }

        return $schema;
    }

    private function exampleValueForType(string $type): mixed
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
     * @param  array<string, mixed>  $componentSchemas
     */
    private function buildExampleFromReference(
        string $reference,
        array $componentSchemas,
    ): mixed {
        $schemaName = Str::afterLast($reference, '/');

        if (
            ! isset($componentSchemas[$schemaName]) ||
            ! is_array($componentSchemas[$schemaName])
        ) {
            return null;
        }

        return $this->buildExampleFromSchema(
            $componentSchemas[$schemaName],
            $componentSchemas,
        );
    }

    /**
     * @param  array<string, mixed>  $schema
     * @param  array<string, mixed>  $componentSchemas
     */
    private function buildExampleFromSchema(
        array $schema,
        array $componentSchemas,
    ): mixed {
        if (isset($schema['example'])) {
            return $schema['example'];
        }

        if (isset($schema['enum'][0])) {
            return $schema['enum'][0];
        }

        if (isset($schema['$ref']) && is_string($schema['$ref'])) {
            return $this->buildExampleFromReference(
                $schema['$ref'],
                $componentSchemas,
            );
        }

        if (
            isset($schema['oneOf']) &&
            is_array($schema['oneOf']) &&
            isset($schema['oneOf'][0]) &&
            is_array($schema['oneOf'][0])
        ) {
            return $this->buildExampleFromSchema(
                $schema['oneOf'][0],
                $componentSchemas,
            );
        }

        return match ($schema['type'] ?? 'object') {
            'object' => $this->buildObjectExample($schema, $componentSchemas),
            'array' => [
                $this->buildExampleFromSchema(
                    is_array($schema['items'] ?? null)
                        ? $schema['items']
                        : ['type' => 'string'],
                    $componentSchemas,
                ),
            ],
            'integer', 'number' => 1,
            'boolean' => true,
            default => match ($schema['format'] ?? null) {
                'email' => 'example@example.com',
                'uri' => 'https://example.com',
                'uuid' => (string) Str::uuid(),
                'date' => now()->toDateString(),
                'date-time' => now()->toIso8601String(),
                'binary' => null,
                default => 'string',
            },
        };
    }

    /**
     * @param  array<string, mixed>  $schema
     * @param  array<string, mixed>  $componentSchemas
     * @return array<string, mixed>
     */
    private function buildObjectExample(
        array $schema,
        array $componentSchemas,
    ): array {
        $example = [];

        foreach ($schema['properties'] ?? [] as $property => $propertySchema) {
            if (! is_array($propertySchema)) {
                continue;
            }

            $value = $this->buildExampleFromSchema(
                $propertySchema,
                $componentSchemas,
            );

            if ($value !== null) {
                $example[$property] = $value;
            }
        }

        return $example;
    }

    /**
     * @param  array<string, mixed>  $schema
     */
    private function schemaLooksLikeResourcePayload(array $schema): bool
    {
        if (($schema['type'] ?? null) !== 'object') {
            return false;
        }

        return ! array_key_exists('data', $schema['properties'] ?? []);
    }

    /**
     * @param  array<string, mixed>  $endpoint
     */
    private function defaultResponseReference(array $endpoint): string
    {
        $controllerMethod = $endpoint['controller']['method'] ?? null;

        if ($controllerMethod === 'index') {
            return '#/components/schemas/PaginatedResponse';
        }

        return '#/components/schemas/ResourceResponse';
    }

    /**
     * @param  array<string, mixed>  $endpoint
     */
    private function buildRequestComponentName(array $endpoint): string
    {
        if (! empty($endpoint['request'])) {
            return Str::finish($endpoint['request'], 'Payload');
        }

        return Str::studly(
            $this->buildOperationId(
                $endpoint['method'] ?? 'post',
                $endpoint['path'] ?? '/',
                $endpoint,
            ),
        ).'Request';
    }

    /**
     * @param  array<string, mixed>  $endpoint
     */
    private function buildOperationId(
        string $method,
        string $path,
        array $endpoint,
    ): string {
        $controller = $endpoint['controller']['controller'] ?? null;
        $controllerMethod = $endpoint['controller']['method'] ?? null;

        if ($controller !== null && $controllerMethod !== null) {
            return Str::camel(
                str_replace('Controller', '', $controller).
                    '_'.
                    $controllerMethod,
            );
        }

        return Str::camel(
            strtolower($method).
                '_'.
                trim(
                    str_replace(
                        ['{', '}', '/', '-', '.'],
                        [' ', ' ', ' ', ' ', ' '],
                        $path,
                    ),
                ),
        );
    }

    private function normalizeOpenApiPath(string $path, string $baseUrl): string
    {
        $pathKey = str_replace($baseUrl, '', $path);

        if (! str_starts_with($pathKey, '/')) {
            $pathKey = '/'.$pathKey;
        }

        return $pathKey;
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultComponentSchemas(): array
    {
        return [
            'ResourceResponse' => [
                'type' => 'object',
                'properties' => [
                    'data' => [
                        'type' => 'object',
                        'description' => 'Resource payload',
                    ],
                ],
            ],
            'PaginatedResponse' => [
                'type' => 'object',
                'properties' => [
                    'data' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                        ],
                    ],
                    'meta' => [
                        '$ref' => '#/components/schemas/PaginationMeta',
                    ],
                    'links' => [
                        '$ref' => '#/components/schemas/PaginationLinks',
                    ],
                ],
            ],
            'PaginationMeta' => [
                'type' => 'object',
                'properties' => [
                    'current_page' => ['type' => 'integer', 'example' => 1],
                    'from' => ['type' => 'integer', 'nullable' => true],
                    'last_page' => ['type' => 'integer', 'example' => 1],
                    'path' => ['type' => 'string', 'format' => 'uri'],
                    'per_page' => ['type' => 'integer', 'example' => 15],
                    'to' => ['type' => 'integer', 'nullable' => true],
                    'total' => ['type' => 'integer', 'example' => 1],
                ],
            ],
            'PaginationLinks' => [
                'type' => 'object',
                'properties' => [
                    'first' => [
                        'type' => 'string',
                        'format' => 'uri',
                        'nullable' => true,
                    ],
                    'last' => [
                        'type' => 'string',
                        'format' => 'uri',
                        'nullable' => true,
                    ],
                    'prev' => [
                        'type' => 'string',
                        'format' => 'uri',
                        'nullable' => true,
                    ],
                    'next' => [
                        'type' => 'string',
                        'format' => 'uri',
                        'nullable' => true,
                    ],
                ],
            ],
            'ErrorResponse' => [
                'type' => 'object',
                'properties' => [
                    'message' => [
                        'type' => 'string',
                        'example' => 'An error occurred.',
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
                        'additionalProperties' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'string',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function getPackageVersion(): string
    {
        if (class_exists(InstalledVersions::class)) {
            try {
                $version = InstalledVersions::getPrettyVersion(
                    'arseno25/laravel-api-magic',
                );

                if ($version !== null) {
                    return $version;
                }
            } catch (\Throwable) {
            }
        }

        try {
            $composerJson = dirname(__DIR__, 2).'/composer.json';
            if (File::exists($composerJson)) {
                $composer = json_decode(File::get($composerJson), true);

                return $composer['version'] ?? '1.0.0';
            }
        } catch (\Throwable) {
        }

        return '1.0.0';
    }
}
