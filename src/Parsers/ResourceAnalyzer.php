<?php

namespace Arseno25\LaravelApiMagic\Parsers;

use Arseno25\LaravelApiMagic\Attributes\ApiMagicSchema;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionNamedType;

final class ResourceAnalyzer
{
    /**
     * Analyze a resource class directly.
     *
     * @return array<string, mixed>|null
     */
    public function analyzeResourceClass(string $resourceClass): ?array
    {
        try {
            $resourceReflection = $this->reflectClass($resourceClass);

            if (
                $resourceReflection !== null &&
                is_subclass_of($resourceClass, ResourceCollection::class)
            ) {
                $innerResource = $this->resolveCollectionResource(
                    $resourceClass,
                );
                $properties = $innerResource
                    ? $this->extractProperties($innerResource)
                    : (object) [];

                return [
                    'name' => class_basename($resourceClass),
                    'schema' => [
                        'type' => 'object',
                        'description' => 'Response mapped by '.
                            class_basename($resourceClass),
                        'properties' => empty((array) $properties)
                            ? (object) []
                            : $properties,
                    ],
                ];
            }

            if (
                $resourceReflection === null ||
                ! is_subclass_of($resourceClass, JsonResource::class)
            ) {
                return null;
            }

            $schemaAttributes = $resourceReflection->getAttributes(
                ApiMagicSchema::class,
            );
            if (! empty($schemaAttributes)) {
                $customSchema = $schemaAttributes[0]->newInstance()->schema;

                return [
                    'name' => class_basename($resourceClass),
                    'schema' => [
                        'type' => 'object',
                        'description' => 'Response mapped by '.
                            class_basename($resourceClass),
                        'properties' => (object) $customSchema,
                    ],
                ];
            }

            $properties = $this->extractProperties($resourceClass);

            return [
                'name' => class_basename($resourceClass),
                'schema' => [
                    'type' => 'object',
                    'description' => 'Response mapped by '.class_basename($resourceClass),
                    'properties' => empty((array) $properties)
                        ? (object) []
                        : $properties,
                ],
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Analyze a controller method's return type to extract Resource schema properties.
     *
     * @return array<string, mixed>|null
     */
    public function analyze(string $controller, string $method): ?array
    {
        try {
            $reflection = $this->reflectClass($controller);
            if ($reflection === null) {
                return null;
            }

            if (! $reflection->hasMethod($method)) {
                return null;
            }

            $methodReflection = $reflection->getMethod($method);

            // Check for ApiMagicSchema Attribute on the Method
            $schemaAttributes = $methodReflection->getAttributes(
                ApiMagicSchema::class,
            );
            if (! empty($schemaAttributes)) {
                $customSchema = $schemaAttributes[0]->newInstance()->schema;

                return [
                    'name' => 'CustomSchema',
                    'schema' => $customSchema,
                ];
            }

            $returnType = $methodReflection->getReturnType();

            if (! $returnType || ! $returnType instanceof ReflectionNamedType) {
                return null;
            }

            $resourceClass = $returnType->getName();

            // Handle AnonymousResourceCollection
            if (
                $resourceClass ===
                "Illuminate\Http\Resources\Json\AnonymousResourceCollection"
            ) {
                $innerResource = $this->extractInnerResourceFromMethodBody(
                    $controller,
                    $method,
                );

                if ($innerResource) {
                    $properties = $this->extractProperties($innerResource);

                    return [
                        'name' => class_basename($innerResource).'Collection',
                        'schema' => [
                            'type' => 'object',
                            'description' => 'Collection of '.
                                class_basename($innerResource),
                            'properties' => [
                                'data' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'object',
                                        'properties' => empty(
                                            (array) $properties
                                        )
                                            ? (object) []
                                            : $properties,
                                    ],
                                ],
                            ],
                        ],
                    ];
                }
            }

            return $this->analyzeResourceClass($resourceClass);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Extract properties from a JsonResource class by parsing its toArray() method.
     *
     * @return array<string, array<string, mixed>>|object
     */
    private function extractProperties(string $resourceClass): array|object
    {
        $properties = [];

        // Strategy 1: Parse toArray() source code for $this->field patterns
        $fromSource = $this->extractFromSource($resourceClass);
        if (! empty($fromSource)) {
            return $fromSource;
        }

        // Strategy 2: Parse DocBlock @property annotations
        $fromDocBlock = $this->extractFromDocBlock($resourceClass);
        if (! empty($fromDocBlock)) {
            return $fromDocBlock;
        }

        // Strategy 3: Resolve from the underlying model's fillable/casts
        $fromModel = $this->extractFromModel($resourceClass);
        if (! empty($fromModel)) {
            return $fromModel;
        }

        return (object) $properties;
    }

    /**
     * Attempt to extract the inner resource class from the method body
     * when it returns AnonymousResourceCollection.
     */
    private function extractInnerResourceFromMethodBody(
        string $controller,
        string $methodName,
    ): ?string {
        try {
            $reflection = $this->reflectClass($controller);
            if ($reflection === null) {
                return null;
            }

            $method = $reflection->getMethod($methodName);

            $filename = $method->getFileName();
            if (! $filename || ! file_exists($filename)) {
                return null;
            }

            $startLine = $method->getStartLine();
            $endLine = $method->getEndLine();

            /** @var string $fileContents */
            $fileContents = file_get_contents($filename);
            $lines = explode("\n", $fileContents);
            $methodBody = implode(
                "\n",
                array_slice($lines, $startLine - 1, $endLine - $startLine + 1),
            );

            // Match pattern like `return UserResource::collection(...)`
            if (
                preg_match(
                    "/return\s+([a-zA-Z0-9_\\\\]+)::collection\s*\(/",
                    $methodBody,
                    $matches,
                )
            ) {
                $resourceName = $matches[1];

                // If it's fully qualified, use it
                if (str_starts_with($resourceName, '\\')) {
                    return ltrim($resourceName, '\\');
                }

                // Otherwise, try to resolve via namespace imports or same namespace
                // We'll simplify and check if it exists in same namespace or common ones
                $namespace = $reflection->getNamespaceName();
                $possibleNamespaces = [
                    $namespace.'\\'.$resourceName,
                    'App\\Http\\Resources\\'.$resourceName,
                    'App\\Http\\Resources\\'.
                    str_replace(
                        'Controller',
                        'Resource',
                        class_basename($controller),
                    ), // Fallback guess
                ];

                foreach ($possibleNamespaces as $ns) {
                    if (class_exists($ns)) {
                        return $ns;
                    }
                }

                // Read use statements if possible (naive approach)
                if (
                    preg_match_all(
                        "/use\s+([a-zA-Z0-9_\\\\]+)(?:\s+as\s+([a-zA-Z0-9_]+))?;/",
                        $fileContents,
                        $useMatches,
                        PREG_SET_ORDER,
                    )
                ) {
                    foreach ($useMatches as $useMatch) {
                        $fullClass = $useMatch[1];
                        $alias = $useMatch[2] ?? class_basename($fullClass);
                        if (
                            $alias === $resourceName &&
                            class_exists($fullClass)
                        ) {
                            return $fullClass;
                        }
                    }
                }
            }

            return null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Parse the toArray() method source for $this->field references.
     *
     * @return array<string, array<string, mixed>>
     */
    private function extractFromSource(string $resourceClass): array
    {
        try {
            $reflection = $this->reflectClass($resourceClass);
            if ($reflection === null) {
                return [];
            }

            if (! $reflection->hasMethod('toArray')) {
                return [];
            }

            $method = $reflection->getMethod('toArray');

            // Only analyze if the class itself declares toArray (not inherited)
            if ($method->getDeclaringClass()->getName() !== $resourceClass) {
                return [];
            }

            $filename = $method->getFileName();
            if (! $filename || ! file_exists($filename)) {
                return [];
            }

            $startLine = $method->getStartLine();
            $endLine = $method->getEndLine();

            /** @var string $fileContents */
            $fileContents = file_get_contents($filename);
            $lines = explode("\n", $fileContents);
            $methodBody = implode(
                "\n",
                array_slice($lines, $startLine - 1, $endLine - $startLine + 1),
            );

            $properties = [];

            // Match patterns like 'field_name' => $this->field_name
            if (
                preg_match_all(
                    "/['\"](\w+)['\"]\s*=>\s*\\\$this->(\w+)/",
                    $methodBody,
                    $matches,
                    PREG_SET_ORDER,
                )
            ) {
                foreach ($matches as $match) {
                    $key = $match[1];
                    $field = $match[2];
                    $properties[$key] = [
                        'type' => $this->guessTypeFromFieldName($field),
                        'description' => Str::headline($key),
                    ];
                }
            }

            // Match patterns like 'field_name' => $this->field->format(...)  (dates)
            if (
                preg_match_all(
                    "/['\"](\w+)['\"]\s*=>\s*\\\$this->(\w+)->format/",
                    $methodBody,
                    $matches,
                    PREG_SET_ORDER,
                )
            ) {
                foreach ($matches as $match) {
                    $key = $match[1];
                    $properties[$key] = [
                        'type' => 'string',
                        'format' => 'date-time',
                        'description' => Str::headline($key),
                    ];
                }
            }

            // Match patterns like 'field' => SomeResource::make($this->relation)
            if (
                preg_match_all(
                    "/['\"](\w+)['\"]\s*=>\s*(\w+Resource)::(?:make|collection)\s*\(\s*\\\$this->(\w+)/",
                    $methodBody,
                    $matches,
                    PREG_SET_ORDER,
                )
            ) {
                foreach ($matches as $match) {
                    $key = $match[1];
                    $resourceName = $match[2];
                    $isCollection = str_contains(
                        $methodBody,
                        $resourceName.'::collection',
                    );
                    $properties[$key] = $isCollection
                        ? [
                            'type' => 'array',
                            'items' => ['type' => 'object'],
                            'description' => Str::headline($key),
                        ]
                        : [
                            'type' => 'object',
                            'description' => Str::headline($key),
                        ];
                }
            }

            // Match nested arrays or objects: 'field' => [ ...
            if (
                preg_match_all(
                    "/['\"](\w+)['\"]\s*=>\s*(?:\[|array\s*\()/",
                    $methodBody,
                    $matches,
                    PREG_SET_ORDER,
                )
            ) {
                foreach ($matches as $match) {
                    $key = $match[1];
                    if (! isset($properties[$key])) {
                        $properties[$key] = [
                            'type' => 'object',
                            'description' => Str::headline($key).' (Nested Object/Array)',
                        ];
                    }
                }
            }

            // Match Polymorphic (MorphTo) conditional relations: 'field' => $this->type === 'x' ? new XResource : new YResource
            if (
                preg_match_all(
                    "/['\"](\w+)['\"]\s*=>\s*.*?\?.*?new\s+(\w+Resource).*?:.*?new\s+(\w+Resource)/s",
                    $methodBody,
                    $matches,
                    PREG_SET_ORDER,
                )
            ) {
                foreach ($matches as $match) {
                    $key = $match[1];
                    $properties[$key] = [
                        'oneOf' => [
                            [
                                'type' => 'object',
                                'title' => $match[2],
                            ],
                            [
                                'type' => 'object',
                                'title' => $match[3],
                            ],
                        ],
                        'description' => Str::headline($key).' (Polymorphic response)',
                    ];
                }
            }

            return $properties;
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Extract properties from class DocBlock @property annotations.
     *
     * @return array<string, array<string, mixed>>
     */
    private function extractFromDocBlock(string $resourceClass): array
    {
        try {
            $reflection = $this->reflectClass($resourceClass);
            if ($reflection === null) {
                return [];
            }

            $docComment = $reflection->getDocComment();

            if ($docComment === false) {
                return [];
            }

            $properties = [];

            // Match @property type $name patterns
            if (
                preg_match_all(
                    '/@property\s+([\w|\\\\]+)\s+\$(\w+)/',
                    $docComment,
                    $matches,
                    PREG_SET_ORDER,
                )
            ) {
                foreach ($matches as $match) {
                    $type = $match[1];
                    $name = $match[2];
                    $properties[$name] = [
                        'type' => $this->phpTypeToOpenApiType($type),
                        'description' => Str::headline($name),
                    ];
                }
            }

            return $properties;
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Extract properties from the underlying Eloquent model.
     *
     * @return array<string, array<string, mixed>>
     */
    private function extractFromModel(string $resourceClass): array
    {
        try {
            $reflection = $this->reflectClass($resourceClass);
            if ($reflection === null) {
                return [];
            }

            // Try to determine the model from the resource class name
            $resourceBaseName = class_basename($resourceClass);
            $modelName = str_replace(
                ['Resource', 'Collection'],
                '',
                $resourceBaseName,
            );

            // Common model namespace patterns
            $modelNamespaces = [
                "App\\Models\\{$modelName}",
                "App\\{$modelName}",
            ];

            $modelClass = null;
            foreach ($modelNamespaces as $ns) {
                if (class_exists($ns)) {
                    $modelClass = $ns;
                    break;
                }
            }

            if (! $modelClass) {
                return [];
            }

            $modelReflection = $this->reflectClass($modelClass);
            if ($modelReflection === null) {
                return [];
            }

            $model = $modelReflection->newInstanceWithoutConstructor();

            $properties = [];

            // Get fillable fields
            if ($modelReflection->hasProperty('fillable')) {
                $prop = $modelReflection->getProperty('fillable');
                $prop->setAccessible(true);
                /** @var array<int, string> $fillable */
                $fillable = $prop->getValue($model);

                foreach ($fillable as $field) {
                    $properties[$field] = [
                        'type' => $this->guessTypeFromFieldName($field),
                        'description' => Str::headline($field),
                    ];
                }
            }

            // Override types using $casts
            if ($modelReflection->hasProperty('casts')) {
                $castProp = $modelReflection->getProperty('casts');
                $castProp->setAccessible(true);
                /** @var array<string, string> $casts */
                $casts = $castProp->getValue($model);

                foreach ($casts as $field => $cast) {
                    if (isset($properties[$field])) {
                        $properties[$field]['type'] = $this->castToOpenApiType(
                            $cast,
                        );
                    }
                }
            }

            // Always add 'id' at the beginning
            if (! isset($properties['id'])) {
                $properties = array_merge(
                    ['id' => ['type' => 'integer', 'description' => 'Id']],
                    $properties,
                );
            }

            // Add timestamps if model uses them
            if ($modelReflection->hasProperty('timestamps')) {
                $tsProp = $modelReflection->getProperty('timestamps');
                $tsProp->setAccessible(true);
                if ($tsProp->getValue($model) !== false) {
                    $properties['created_at'] = [
                        'type' => 'string',
                        'format' => 'date-time',
                        'description' => 'Created At',
                    ];
                    $properties['updated_at'] = [
                        'type' => 'string',
                        'format' => 'date-time',
                        'description' => 'Updated At',
                    ];
                }
            }

            return $properties;
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Resolve the inner resource class from a ResourceCollection.
     */
    private function resolveCollectionResource(string $collectionClass): ?string
    {
        try {
            // Convention: FooCollection -> FooResource
            $baseName = class_basename($collectionClass);
            $collectionReflection = $this->reflectClass($collectionClass);
            if ($collectionReflection === null) {
                return null;
            }

            $namespace = $collectionReflection->getNamespaceName();
            $resourceName = str_replace('Collection', 'Resource', $baseName);
            $fullClass = $namespace.'\\'.$resourceName;

            if (
                class_exists($fullClass) &&
                is_subclass_of($fullClass, JsonResource::class)
            ) {
                return $fullClass;
            }

            return null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Guess OpenAPI type from field name conventions.
     */
    private function guessTypeFromFieldName(string $field): string
    {
        if ($field === 'id' || Str::endsWith($field, '_id')) {
            return 'integer';
        }
        if (Str::startsWith($field, 'is_') || Str::startsWith($field, 'has_')) {
            return 'boolean';
        }
        if (
            Str::contains($field, [
                'price',
                'amount',
                'total',
                'cost',
                'balance',
                'rate',
            ])
        ) {
            return 'number';
        }
        if (
            Str::contains($field, ['count', 'quantity', 'qty', 'age', 'number'])
        ) {
            return 'integer';
        }
        if (
            Str::endsWith($field, '_at') ||
            Str::contains($field, ['date', 'time'])
        ) {
            return 'string';
        }

        return 'string';
    }

    /**
     * Convert PHP type hint to OpenAPI type.
     */
    private function phpTypeToOpenApiType(string $phpType): string
    {
        $typeMap = [
            'int' => 'integer',
            'integer' => 'integer',
            'float' => 'number',
            'double' => 'number',
            'bool' => 'boolean',
            'boolean' => 'boolean',
            'string' => 'string',
            'array' => 'array',
            'object' => 'object',
        ];

        $cleanType = ltrim(explode('|', $phpType)[0], '?\\');

        return $typeMap[strtolower($cleanType)] ?? 'string';
    }

    /**
     * Convert Laravel cast type to OpenAPI type.
     */
    private function castToOpenApiType(string $cast): string
    {
        $castMap = [
            'int' => 'integer',
            'integer' => 'integer',
            'real' => 'number',
            'float' => 'number',
            'double' => 'number',
            'decimal' => 'number',
            'string' => 'string',
            'bool' => 'boolean',
            'boolean' => 'boolean',
            'object' => 'object',
            'array' => 'array',
            'collection' => 'array',
            'json' => 'object',
            'date' => 'string',
            'datetime' => 'string',
            'immutable_date' => 'string',
            'immutable_datetime' => 'string',
            'timestamp' => 'integer',
        ];

        $cleanCast = strtolower(explode(':', $cast)[0]);

        return $castMap[$cleanCast] ?? 'string';
    }

    /**
     * @param  class-string<object>|string  $className
     * @return ReflectionClass<object>|null
     */
    private function reflectClass(string $className): ?ReflectionClass
    {
        if (! class_exists($className)) {
            return null;
        }

        /** @var class-string<object> $className */
        return new ReflectionClass($className);
    }
}
