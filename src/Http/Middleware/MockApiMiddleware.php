<?php

namespace Arseno25\LaravelApiMagic\Http\Middleware;

use Arseno25\LaravelApiMagic\Attributes\ApiMock;
use Arseno25\LaravelApiMagic\Parsers\RequestAnalyzer;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use ReflectionMethod;

class MockApiMiddleware
{
    public function __construct(
        private readonly RequestAnalyzer $requestAnalyzer,
    ) {}

    public function handle(Request $request, Closure $next): mixed
    {
        // Check if mock mode is enabled globally or via header
        $mockEnabled = config('laravel-api-magic.mock.enabled', false);
        $headerMock = $request->header('X-Api-Mock') === 'true';

        // Get the current route's controller and method
        $route = $request->route();
        if (! $route) {
            return $next($request);
        }

        $action = $route->getAction();
        $uses = $action['uses'] ?? null;

        if (! is_string($uses) || ! str_contains($uses, '@')) {
            return $next($request);
        }

        [$controller, $method] = explode('@', $uses);

        // Check for #[ApiMock] attribute
        $mockAttribute = $this->getMockAttribute($controller, $method);

        // If mock is not triggered globally, via header, or via attribute, skip
        if (! $mockEnabled && ! $headerMock && ! $mockAttribute) {
            return $next($request);
        }

        $statusCode = $mockAttribute !== null ? $mockAttribute->statusCode : 200;
        $count = $mockAttribute !== null ? $mockAttribute->count : 5;

        // Generate mock response based on method type
        $httpMethod = strtolower($request->method());
        $mockData = $this->generateMockData($controller, $method, $httpMethod, $count, $action);

        return new JsonResponse([
            'data' => $mockData,
            '_mock' => true,
            '_generated_at' => now()->toIso8601String(),
        ], $statusCode, [
            'X-Api-Mock' => 'true',
            'X-Api-Mock-Generated' => now()->toIso8601String(),
        ]);
    }

    /**
     * Get the ApiMock attribute from a controller method.
     */
    private function getMockAttribute(string $controller, string $method): ?ApiMock
    {
        try {
            if (! class_exists($controller)) {
                return null;
            }

            $reflection = new ReflectionMethod($controller, $method);
            $attributes = $reflection->getAttributes(ApiMock::class);

            if (empty($attributes)) {
                // Check class-level attribute
                $classAttributes = $reflection->getDeclaringClass()->getAttributes(ApiMock::class);
                if (empty($classAttributes)) {
                    return null;
                }

                return $classAttributes[0]->newInstance();
            }

            return $attributes[0]->newInstance();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Generate mock data based on the controller's FormRequest rules.
     *
     * @return array<string, mixed>|array<int, array<string, mixed>>
     */
    private function generateMockData(string $controller, string $method, string $httpMethod, int $count, array|string|null $action = null): array
    {
        // Try to get the FormRequest rules to generate realistic data
        $requestClass = $this->requestAnalyzer->extractRequestFromAction($action);
        $fields = $requestClass ? $this->requestAnalyzer->analyze($requestClass) : [];

        if (empty($fields)) {
            // Generate generic mock data
            return $this->generateGenericMock($httpMethod, $count);
        }

        // For list endpoints (index), return multiple items
        if (in_array($method, ['index']) || $httpMethod === 'get') {
            $items = [];
            for ($i = 0; $i < $count; $i++) {
                $items[] = $this->generateItemFromFields($fields, $i + 1);
            }

            return $items;
        }

        // For single endpoints, return one item
        return $this->generateItemFromFields($fields, 1);
    }

    /**
     * Generate a single mock item from field definitions.
     *
     * @param  array<string, mixed>  $fields
     * @return array<string, mixed>
     */
    private function generateItemFromFields(array $fields, int $index): array
    {
        $item = ['id' => $index];

        foreach ($fields as $field) {
            $name = $field['name'] ?? 'field';
            $type = $field['type'] ?? 'string';
            $enum = $field['enum'] ?? null;

            if ($enum) {
                $item[$name] = $enum[array_rand($enum)];

                continue;
            }

            $item[$name] = $this->generateMockValue($name, $type, $index);
        }

        $item['created_at'] = now()->subDays(rand(1, 30))->toIso8601String();
        $item['updated_at'] = now()->toIso8601String();

        return $item;
    }

    /**
     * Generate a realistic mock value based on field name and type.
     */
    private function generateMockValue(string $name, string $type, int $index): mixed
    {
        $n = strtolower($name);

        // Smart name-based generation
        if (str_contains($n, 'email')) {
            return "user{$index}@example.com";
        }
        if (str_contains($n, 'name') && str_contains($n, 'first')) {
            return ['John', 'Jane', 'Bob', 'Alice', 'Charlie'][$index % 5];
        }
        if (str_contains($n, 'name') && str_contains($n, 'last')) {
            return ['Doe', 'Smith', 'Wilson', 'Brown', 'Davis'][$index % 5];
        }
        if (str_contains($n, 'name')) {
            return "Example Name {$index}";
        }
        if (str_contains($n, 'title') || str_contains($n, 'subject')) {
            return "Sample Title {$index}";
        }
        if (str_contains($n, 'description') || str_contains($n, 'content') || str_contains($n, 'body')) {
            return "This is a sample description for item {$index}. Lorem ipsum dolor sit amet.";
        }
        if (str_contains($n, 'slug')) {
            return "sample-slug-{$index}";
        }
        if (str_contains($n, 'url') || str_contains($n, 'link') || str_contains($n, 'website')) {
            return "https://example.com/item/{$index}";
        }
        if (str_contains($n, 'phone')) {
            return '+1-555-'.str_pad((string) ($index * 1234 % 10000), 4, '0', STR_PAD_LEFT);
        }
        if (str_contains($n, 'price') || str_contains($n, 'cost') || str_contains($n, 'amount')) {
            return round(9.99 + ($index * 10.5), 2);
        }
        if (str_contains($n, 'quantity') || str_contains($n, 'stock') || str_contains($n, 'qty')) {
            return $index * 10;
        }
        if (str_contains($n, 'image') || str_contains($n, 'avatar') || str_contains($n, 'photo')) {
            return "https://picsum.photos/seed/{$index}/400/300";
        }
        if (str_contains($n, '_id') || str_contains($n, 'category')) {
            return $index;
        }
        if (str_contains($n, 'password')) {
            return 'mock_password_hash';
        }
        if (str_contains($n, 'status')) {
            return ['active', 'inactive', 'pending'][$index % 3];
        }
        if (str_contains($n, 'is_') || str_contains($n, 'has_')) {
            return $index % 2 === 0;
        }

        // Type-based fallback
        return match ($type) {
            'integer', 'int', 'bigint' => $index * 100,
            'number', 'float', 'double', 'decimal' => round($index * 1.5, 2),
            'boolean', 'bool' => $index % 2 === 0,
            'array' => ["item_{$index}_a", "item_{$index}_b"],
            'date', 'datetime', 'timestamp' => now()->subDays($index)->toIso8601String(),
            default => "value_{$index}",
        };
    }

    /**
     * Generate generic mock data when no FormRequest is found.
     *
     * @return array<int, array<string, mixed>>
     */
    private function generateGenericMock(string $httpMethod, int $count): array
    {
        $items = [];
        for ($i = 1; $i <= $count; $i++) {
            $items[] = [
                'id' => $i,
                'name' => "Item {$i}",
                'description' => "Description for item {$i}",
                'status' => ['active', 'inactive', 'pending'][$i % 3],
                'created_at' => now()->subDays($i)->toIso8601String(),
                'updated_at' => now()->toIso8601String(),
            ];
        }

        return $httpMethod === 'get' ? $items : $items[0];
    }
}
