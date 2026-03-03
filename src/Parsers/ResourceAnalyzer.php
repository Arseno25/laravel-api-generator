<?php

namespace Arseno25\LaravelApiMagic\Parsers;

use Illuminate\Http\Resources\Json\JsonResource;
use ReflectionClass;

final class ResourceAnalyzer
{
    /**
     * Analyze a controller method's return type to extract Resource schema properties.
     *
     * @return array<string, mixed>|null
     */
    public function analyze(string $controller, string $method): ?array
    {
        try {
            $reflection = new ReflectionClass($controller);
            if (! $reflection->hasMethod($method)) {
                return null;
            }

            $methodReflection = $reflection->getMethod($method);
            $returnType = $methodReflection->getReturnType();

            if (! $returnType || ! $returnType instanceof \ReflectionNamedType) {
                return null;
            }

            $resourceClass = $returnType->getName();

            // We only analyze valid JsonResource classes
            if (! class_exists($resourceClass) || ! is_subclass_of($resourceClass, JsonResource::class)) {
                return null;
            }

            return [
                'name' => class_basename($resourceClass),
                'schema' => [
                    'type' => 'object',
                    'description' => "Response mapped by ".class_basename($resourceClass),
                    // For a robust implementation, this could parse the DB schema or the model
                    // to determine properties. For now, we return empty properties which
                    // Swagger accepts as a generic JSON object.
                    'properties' => (object) [],
                ],
            ];

        } catch (\Throwable) {
            return null;
        }
    }
}
