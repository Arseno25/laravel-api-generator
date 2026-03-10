<?php

namespace Arseno25\LaravelApiMagic\Services;

use Illuminate\Support\Arr;

final class OpenApiSchemaValidator
{
    /**
     * @param  array<string, mixed>  $schema
     * @return list<string>
     */
    public function validate(array $schema): array
    {
        $issues = [];

        foreach (['openapi', 'info', 'paths', 'components'] as $key) {
            if (! array_key_exists($key, $schema)) {
                $issues[] = "Missing top-level key [{$key}].";
            }
        }

        if (! isset($schema['info']['title']) || ! is_string($schema['info']['title'])) {
            $issues[] = 'Missing [info.title].';
        }

        if (! isset($schema['info']['version']) || ! is_string($schema['info']['version'])) {
            $issues[] = 'Missing [info.version].';
        }

        $issues = [...$issues, ...$this->validatePaths($schema)];
        $issues = [...$issues, ...$this->validateSecurityReferences($schema)];
        $issues = [...$issues, ...$this->validateRefs($schema)];

        return array_values(array_unique($issues));
    }

    /**
     * @param  array<string, mixed>  $schema
     * @return list<string>
     */
    private function validatePaths(array $schema): array
    {
        $issues = [];
        $operationIds = [];
        $allowedMethods = ['get', 'post', 'put', 'patch', 'delete', 'options', 'head', 'trace'];

        foreach ($schema['paths'] ?? [] as $path => $operations) {
            if (! is_string($path) || ! str_starts_with($path, '/')) {
                $issues[] = "Path [{$path}] must start with '/'.";
            }

            if (! is_array($operations)) {
                $issues[] = "Path [{$path}] must contain an operation map.";

                continue;
            }

            foreach ($operations as $method => $operation) {
                if (! in_array($method, $allowedMethods, true)) {
                    $issues[] = "Path [{$path}] contains unsupported method [{$method}].";
                }

                if (! is_array($operation)) {
                    $issues[] = "Operation [{$method} {$path}] must be an object.";

                    continue;
                }

                $operationId = $operation['operationId'] ?? null;
                if (! is_string($operationId) || $operationId === '') {
                    $issues[] = "Operation [{$method} {$path}] is missing [operationId].";
                } elseif (isset($operationIds[$operationId])) {
                    $issues[] = "Duplicate operationId [{$operationId}] found on [{$method} {$path}].";
                } else {
                    $operationIds[$operationId] = true;
                }

                foreach ($operation['parameters'] ?? [] as $parameter) {
                    if (! is_array($parameter)) {
                        $issues[] = "Operation [{$method} {$path}] contains a malformed parameter.";

                        continue;
                    }

                    if (! isset($parameter['name'], $parameter['in'], $parameter['schema'])) {
                        $issues[] = "Operation [{$method} {$path}] contains a parameter without [name], [in], or [schema].";
                    }

                    if (($parameter['in'] ?? null) === 'path' && ($parameter['required'] ?? false) !== true) {
                        $issues[] = "Path parameter [{$parameter['name']}] on [{$method} {$path}] must be required.";
                    }
                }

                foreach ($operation['responses'] ?? [] as $status => $response) {
                    if (! is_array($response) || ! isset($response['description'])) {
                        $issues[] = "Response [{$status}] on [{$method} {$path}] must include a description.";
                    }
                }
            }
        }

        return $issues;
    }

    /**
     * @param  array<string, mixed>  $schema
     * @return list<string>
     */
    private function validateSecurityReferences(array $schema): array
    {
        $issues = [];
        $schemes = array_keys($schema['components']['securitySchemes'] ?? []);

        foreach ($schema['paths'] ?? [] as $path => $operations) {
            if (! is_array($operations)) {
                continue;
            }

            foreach ($operations as $method => $operation) {
                foreach ($operation['security'] ?? [] as $securityRequirement) {
                    if (! is_array($securityRequirement)) {
                        $issues[] = "Security requirement on [{$method} {$path}] must be an object.";

                        continue;
                    }

                    foreach (array_keys($securityRequirement) as $schemeName) {
                        if (! in_array($schemeName, $schemes, true)) {
                            $issues[] = "Operation [{$method} {$path}] references unknown security scheme [{$schemeName}].";
                        }
                    }
                }
            }
        }

        return $issues;
    }

    /**
     * @param  array<string, mixed>  $schema
     * @return list<string>
     */
    private function validateRefs(array $schema): array
    {
        $issues = [];

        foreach ($this->collectRefs($schema) as $reference) {
            $resolved = Arr::get($schema, str_replace('/', '.', ltrim($reference, '#/')));

            if ($resolved === null) {
                $issues[] = "Reference [{$reference}] could not be resolved.";
            }
        }

        return $issues;
    }

    /**
     * @param  array<string, mixed>  $value
     * @return list<string>
     */
    private function collectRefs(array $value): array
    {
        $references = [];

        array_walk_recursive(
            $value,
            function (mixed $item, string $key) use (&$references): void {
                if ($key === '$ref' && is_string($item)) {
                    $references[] = $item;
                }
            },
        );

        return $references;
    }
}
