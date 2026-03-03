<?php

namespace Arseno25\LaravelApiMagic\Parsers;

use Illuminate\Foundation\Http\FormRequest;
use ReflectionClass;
use ReflectionNamedType;

final class RequestAnalyzer
{
    /**
     * Analyze a FormRequest class and extract validation rules.
     *
     * @param  class-string<FormRequest>  $requestClass
     * @return array<string, array<string, mixed>>
     */
    public function analyze(string $requestClass): array
    {
        if (! class_exists($requestClass)) {
            return [];
        }

        try {
            $reflection = new ReflectionClass($requestClass);

            if (! $reflection->isSubclassOf(FormRequest::class)) {
                return [];
            }

            /** @var FormRequest $instance */
            $instance = $reflection->newInstance();
            $rules = $instance->rules(); // @phpstan-ignore method.notFound

            return $this->parseRules($rules);
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Parse validation rules into structured format.
     *
     * @param  array<string, mixed>  $rules
     * @return array<string, array<string, mixed>>
     */
    private function parseRules(array $rules): array
    {
        $parsed = [];

        foreach ($rules as $field => $rule) {
            $parsed[$field] = $this->parseRuleString($rule);
        }

        return $parsed;
    }

    /**
     * Parse a single rule string into components.
     *
     * @return array<string, mixed>
     */
    private function parseRuleString(string|array $rule): array
    {
        if (is_array($rule)) {
            return [
                'type' => $this->guessType($rule),
                'required' => $this->isRequired($rule),
                'rules' => $this->rulesToString($rule),
                'description' => $this->generateDescription($rule),
                'enum' => $this->extractEnum($rule),
                'is_file' => $this->isFile($rule),
            ];
        }

        $ruleArray = explode('|', $rule);

        return [
            'type' => $this->guessType($ruleArray),
            'required' => in_array('required', $ruleArray),
            'rules' => $rule,
            'description' => $this->generateDescription($ruleArray),
            'enum' => $this->extractEnum($ruleArray),
            'is_file' => $this->isFile($ruleArray),
        ];
    }

    /**
     * Guess the field type from validation rules.
     *
     * @param  array<string>  $rules
     */
    private function guessType(array $rules): string
    {
        $rulesString = implode('|', $rules);

        if (str_contains($rulesString, 'integer') || str_contains($rulesString, 'numeric')) {
            return 'integer';
        }

        if (str_contains($rulesString, 'boolean')) {
            return 'boolean';
        }

        if (str_contains($rulesString, 'array')) {
            return 'array';
        }

        if (str_contains($rulesString, 'email')) {
            return 'email';
        }

        if (str_contains($rulesString, 'url')) {
            return 'url';
        }

        if (str_contains($rulesString, 'date')) {
            return 'date';
        }

        if (str_contains($rulesString, 'uuid')) {
            return 'uuid';
        }

        return 'string';
    }

    /**
     * Check if field is required.
     *
     * @param  array<string>  $rules
     */
    private function isRequired(array $rules): bool
    {
        return in_array('required', $rules);
    }

    /**
     * Check if field is a file or image.
     *
     * @param  array<string>  $rules
     */
    private function isFile(array $rules): bool
    {
        return in_array('file', $rules) || in_array('image', $rules) || collect($rules)->contains(fn ($rule) => str_starts_with($rule, 'mimes:'));
    }

    /**
     * Convert rules array back to string.
     *
     * @param  array<string>  $rules
     */
    private function rulesToString(array $rules): string
    {
        return implode('|', $rules);
    }

    /**
     * Extract enum values if present.
     *
     * @param  array<string>  $rules
     * @return array<int, string>|null
     */
    private function extractEnum(array $rules): ?array
    {
        foreach ($rules as $rule) {
            if (str_starts_with($rule, 'in:')) {
                $values = substr($rule, 3);
                $enumValues = array_map('trim', explode(',', $values));

                return array_map(fn ($v) => trim($v, '"\''), $enumValues);
            }
        }

        return null;
    }

    /**
     * Generate human-readable description from rules.
     *
     * @param  array<string>  $rules
     */
    private function generateDescription(array $rules): string
    {
        $description = [];

        if (in_array('required', $rules)) {
            $description[] = 'Required';
        } elseif (in_array('nullable', $rules)) {
            $description[] = 'Optional';
        }

        foreach ($rules as $rule) {
            if (str_starts_with($rule, 'min:')) {
                $description[] = 'Min: '.substr($rule, 4);
            }
            if (str_starts_with($rule, 'max:')) {
                $description[] = 'Max: '.substr($rule, 4);
            }
            if (str_starts_with($rule, 'between:')) {
                $description[] = 'Between: '.substr($rule, 8);
            }
            if ($rule === 'email') {
                $description[] = 'Must be valid email';
            }
            if ($rule === 'unique') {
                $description[] = 'Must be unique';
            }
            if ($rule === 'confirmed') {
                $description[] = 'Requires confirmation field';
            }
        }

        return empty($description) ? 'No additional constraints' : implode(', ', $description);
    }

    /**
     * Extract request class from route action.
     */
    public function extractRequestFromAction(array|string|null $action): ?string
    {
        if (! is_array($action)) {
            return null;
        }

        if (isset($action['uses']) && is_string($action['uses'])) {
            $parts = explode('@', $action['uses']);
            if (count($parts) === 2) {
                [$controller, $method] = $parts;

                if (class_exists($controller)) {
                    return $this->findRequestInMethod($controller, $method);
                }
            }
        }

        return null;
    }

    /**
     * Find FormRequest class in controller method signature.
     */
    private function findRequestInMethod(string $controller, string $method): ?string
    {
        try {
            $reflection = new ReflectionClass($controller);

            if (! $reflection->hasMethod($method)) {
                return null;
            }

            $methodReflection = $reflection->getMethod($method);
            $parameters = $methodReflection->getParameters();

            foreach ($parameters as $parameter) {
                $type = $parameter->getType();

                if ($type instanceof ReflectionNamedType && ! $type->isBuiltin()) {
                    $typeName = $type->getName();

                    if (class_exists($typeName) && is_subclass_of($typeName, FormRequest::class)) {
                        return $typeName;
                    }
                }
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }

    /**
     * Get standard query parameters for pagination and search.
     * Used for GET index endpoints.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getIndexQueryParameters(): array
    {
        return [
            [
                'name' => 'page',
                'in' => 'query',
                'type' => 'integer',
                'required' => false,
                'description' => 'Page number for pagination',
                'schema' => [
                    'type' => 'integer',
                    'default' => 1,
                    'minimum' => 1,
                ],
            ],
            [
                'name' => 'per_page',
                'in' => 'query',
                'type' => 'integer',
                'required' => false,
                'description' => 'Number of items per page',
                'schema' => [
                    'type' => 'integer',
                    'default' => 15,
                    'minimum' => 1,
                    'maximum' => 100,
                ],
            ],
            [
                'name' => 'search',
                'in' => 'query',
                'type' => 'string',
                'required' => false,
                'description' => 'Search query to filter results',
                'schema' => [
                    'type' => 'string',
                    'minLength' => 1,
                ],
            ],
        ];
    }
}
