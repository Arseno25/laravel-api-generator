<?php

namespace Arseno25\LaravelApiMagic\Generators;

use Illuminate\Support\Str;

final class GraphqlGenerator
{
    /**
     * Generate a GraphQL schema from the internal API schema.
     *
     * @param  array<string, mixed>  $schema
     */
    public function generate(array $schema): string
    {
        $queries = [];
        $mutations = [];
        $types = [];
        $inputs = [];

        /** @var array<string, array<string, array<string, mixed>>> $endpoints */
        $endpoints = $schema['endpoints'] ?? [];

        foreach ($endpoints as $path => $methods) {
            foreach ($methods as $method => $endpoint) {
                $resourceName = $this->extractResourceName($path);
                $typeName = Str::studly(Str::singular($resourceName));

                // Build output type from response schema
                if (! isset($types[$typeName])) {
                    $types[$typeName] = $this->buildOutputType($typeName, $endpoint);
                }

                $methodUpper = strtoupper($method);

                if ($methodUpper === 'GET') {
                    $queries[] = $this->buildQuery($path, $endpoint, $typeName);
                } elseif (in_array($methodUpper, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
                    $inputTypeName = $typeName.$this->actionToSuffix($methodUpper).'Input';
                    $mutation = $this->buildMutation($path, $endpoint, $typeName, $inputTypeName, $methodUpper);
                    $mutations[] = $mutation;

                    // Build input type for POST/PUT/PATCH
                    if (in_array($methodUpper, ['POST', 'PUT', 'PATCH'])) {
                        $inputType = $this->buildInputType($inputTypeName, $endpoint);
                        if ($inputType && ! isset($inputs[$inputTypeName])) {
                            $inputs[$inputTypeName] = $inputType;
                        }
                    }
                }
            }
        }

        return $this->renderSchema($queries, $mutations, $types, $inputs);
    }

    /**
     * Build a Query field definition.
     *
     * @param  array<string, mixed>  $endpoint
     * @return array<string, string>
     */
    private function buildQuery(string $path, array $endpoint, string $typeName): array
    {
        $hasPathParam = str_contains($path, '{');
        $isCollection = ! $hasPathParam;

        $fieldName = $isCollection
            ? Str::camel(Str::plural($typeName))
            : Str::camel($typeName);

        $args = [];

        // Path parameters
        $pathParams = $endpoint['parameters']['path'] ?? [];
        if (is_array($pathParams)) {
            foreach ($pathParams as $param) {
                if (is_array($param)) {
                    $args[] = $param['name'].': ID!';
                }
            }
        }

        // Query parameters for collections
        if ($isCollection) {
            $queryParams = $endpoint['parameters']['query'] ?? [];
            if (is_array($queryParams)) {
                foreach ($queryParams as $param) {
                    if (is_array($param)) {
                        $gqlType = $this->toGraphqlType($param['type'] ?? 'string', $param['required'] ?? false);
                        $args[] = ($param['name'] ?? 'param').': '.$gqlType;
                    }
                }
            }
        }

        $argStr = ! empty($args) ? '('.implode(', ', $args).')' : '';
        $returnType = $isCollection ? '['.$typeName.'!]!' : $typeName;
        $description = $endpoint['description'] ?? '';

        return [
            'name' => $fieldName,
            'definition' => "  \"{$description}\"\n  {$fieldName}{$argStr}: {$returnType}",
        ];
    }

    /**
     * Build a Mutation field definition.
     *
     * @param  array<string, mixed>  $endpoint
     * @return array<string, string>
     */
    private function buildMutation(string $path, array $endpoint, string $typeName, string $inputTypeName, string $method): array
    {
        $action = match ($method) {
            'POST' => 'create',
            'PUT', 'PATCH' => 'update',
            'DELETE' => 'delete',
            default => 'process',
        };

        $fieldName = Str::camel($action.$typeName);

        $args = [];

        // Path parameters (ID for update/delete)
        $pathParams = $endpoint['parameters']['path'] ?? [];
        if (is_array($pathParams)) {
            foreach ($pathParams as $param) {
                if (is_array($param)) {
                    $args[] = ($param['name'] ?? 'id').': ID!';
                }
            }
        }

        // Input type for POST/PUT/PATCH
        if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $bodyFields = $endpoint['parameters']['body'] ?? [];
            if (! empty($bodyFields) && is_array($bodyFields)) {
                $args[] = 'input: '.$inputTypeName.'!';
            }
        }

        $argStr = ! empty($args) ? '('.implode(', ', $args).')' : '';
        $returnType = $method === 'DELETE' ? 'Boolean!' : $typeName;
        $description = $endpoint['description'] ?? '';

        return [
            'name' => $fieldName,
            'definition' => "  \"{$description}\"\n  {$fieldName}{$argStr}: {$returnType}",
        ];
    }

    /**
     * Build output type from endpoint response schema.
     *
     * @param  array<string, mixed>  $endpoint
     */
    private function buildOutputType(string $typeName, array $endpoint): string
    {
        $fields = [];

        // Try response schema properties
        $properties = $endpoint['response']['schema']['properties'] ?? null;
        if (is_array($properties) && ! empty($properties)) {
            foreach ($properties as $name => $prop) {
                $type = $this->toGraphqlType($prop['type'] ?? 'string', false);
                $desc = $prop['description'] ?? '';
                $fields[] = $desc ? "  \"{$desc}\"\n  {$name}: {$type}" : "  {$name}: {$type}";
            }
        }

        // Fallback: infer from request body fields
        if (empty($fields)) {
            $fields[] = '  id: ID!';
            $bodyFields = $endpoint['parameters']['body'] ?? [];
            if (is_array($bodyFields)) {
                foreach ($bodyFields as $name => $field) {
                    if (is_array($field)) {
                        $type = $this->toGraphqlType($field['type'] ?? 'string', false);
                        $fields[] = "  {$name}: {$type}";
                    }
                }
            }
            $fields[] = '  created_at: String';
            $fields[] = '  updated_at: String';
        }

        $fieldsStr = implode("\n", $fields);

        return "type {$typeName} {\n{$fieldsStr}\n}";
    }

    /**
     * Build input type from endpoint request body.
     *
     * @param  array<string, mixed>  $endpoint
     */
    private function buildInputType(string $inputTypeName, array $endpoint): ?string
    {
        $fields = [];
        $bodyFields = $endpoint['parameters']['body'] ?? [];

        if (empty($bodyFields) || ! is_array($bodyFields)) {
            return null;
        }

        foreach ($bodyFields as $name => $field) {
            if (! is_array($field)) {
                continue;
            }
            $required = $field['required'] ?? false;
            $type = $this->toGraphqlType($field['type'] ?? 'string', $required);
            $desc = $field['description'] ?? '';
            $fields[] = $desc ? "  \"{$desc}\"\n  {$name}: {$type}" : "  {$name}: {$type}";
        }

        if (empty($fields)) {
            return null;
        }

        $fieldsStr = implode("\n", $fields);

        return "input {$inputTypeName} {\n{$fieldsStr}\n}";
    }

    /**
     * Render the complete GraphQL schema.
     *
     * @param  array<int, array<string, string>>  $queries
     * @param  array<int, array<string, string>>  $mutations
     * @param  array<string, string>  $types
     * @param  array<string, string>  $inputs
     */
    private function renderSchema(array $queries, array $mutations, array $types, array $inputs): string
    {
        $parts = [];

        $parts[] = '# Auto-generated GraphQL schema by Laravel API Magic';
        $parts[] = '# Generated at: '.now()->toIso8601String();
        $parts[] = '# Do not edit manually — re-run `php artisan api-magic:graphql` to regenerate.';
        $parts[] = '';

        // Scalar types
        $parts[] = 'scalar DateTime';
        $parts[] = 'scalar JSON';
        $parts[] = '';

        // Output types
        foreach ($types as $type) {
            $parts[] = $type;
            $parts[] = '';
        }

        // Input types
        foreach ($inputs as $input) {
            $parts[] = $input;
            $parts[] = '';
        }

        // Query type
        if (! empty($queries)) {
            $queryFields = array_map(fn (array $q) => $q['definition'], $queries);
            $parts[] = "type Query {\n".implode("\n\n", $queryFields)."\n}";
            $parts[] = '';
        }

        // Mutation type
        if (! empty($mutations)) {
            $mutationFields = array_map(fn (array $m) => $m['definition'], $mutations);
            $parts[] = "type Mutation {\n".implode("\n\n", $mutationFields)."\n}";
            $parts[] = '';
        }

        return implode("\n", $parts);
    }

    /**
     * Convert a type string to GraphQL type.
     */
    private function toGraphqlType(string $type, bool $required): string
    {
        $gqlType = match (strtolower($type)) {
            'integer', 'int' => 'Int',
            'number', 'float', 'double', 'decimal' => 'Float',
            'boolean', 'bool' => 'Boolean',
            'array' => '[JSON]',
            'object' => 'JSON',
            default => 'String',
        };

        return $required ? $gqlType.'!' : $gqlType;
    }

    /**
     * Extract resource name from URI path.
     */
    private function extractResourceName(string $path): string
    {
        $segments = explode('/', trim($path, '/'));
        $filtered = array_filter($segments, fn (string $s) => ! str_starts_with($s, '{') && $s !== 'api' && ! preg_match('/^v\d+$/', $s));

        $last = end($filtered) ?: 'resource';

        return str_replace('-', '_', $last);
    }

    /**
     * Convert HTTP method to mutation suffix.
     */
    private function actionToSuffix(string $method): string
    {
        return match ($method) {
            'POST' => 'Create',
            'PUT', 'PATCH' => 'Update',
            'DELETE' => 'Delete',
            default => '',
        };
    }
}
