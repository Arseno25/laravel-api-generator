<?php

namespace Arseno25\LaravelApiMagic\Parsers;

use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionProperty;

final class EventAnalyzer
{
    /**
     * Scan the given directory (default app/Events) for ShouldBroadcast events
     * and extract their channels and payload schemas.
     *
     * @return array<string, mixed>
     */
    public function analyze(?string $directory = null): array
    {
        $directory = $directory ?? app_path('Events');
        if (! File::exists($directory)) {
            return [];
        }

        $events = [];
        $files = File::allFiles($directory);

        foreach ($files as $file) {
            $class = $this->extractClassFromFile($file->getPathname());

            if (! $class || ! class_exists($class)) {
                continue;
            }

            $reflection = new ReflectionClass($class);
            if (
                ! $reflection->implementsInterface(ShouldBroadcast::class) &&
                ! $reflection->implementsInterface(ShouldBroadcastNow::class)
            ) {
                continue;
            }

            $eventData = $this->analyzeEventClass($reflection);
            if ($eventData) {
                $events[$eventData['name']] = $eventData;
            }
        }

        return $events;
    }

    /**
     * @param  ReflectionClass<object>  $reflection
     * @return array<string, mixed>
     */
    private function analyzeEventClass(ReflectionClass $reflection): array
    {
        $payload = [];

        // Use broadcastWith method if available
        if ($reflection->hasMethod('broadcastWith')) {
            $payload = [
                'type' => 'object',
                'description' => 'Custom payload from broadcastWith()',
                'properties' => [],
            ];
        } else {
            // Otherwise public properties
            $properties = $reflection->getProperties(
                ReflectionProperty::IS_PUBLIC,
            );
            $schemaProperties = [];
            foreach ($properties as $prop) {
                if ($prop->isStatic()) {
                    continue;
                }
                $type =
                    $prop->hasType() &&
                    $prop->getType() instanceof \ReflectionNamedType
                        ? $prop->getType()->getName()
                        : 'mixed';

                $swaggerType = match ($type) {
                    'int', 'integer' => 'integer',
                    'float', 'double' => 'number',
                    'bool', 'boolean' => 'boolean',
                    'array' => 'array',
                    'string' => 'string',
                    default => 'object',
                };

                $schemaProperties[$prop->getName()] = ['type' => $swaggerType];
            }
            $payload = ['type' => 'object', 'properties' => $schemaProperties];
        }

        // Try to guess channel names (very hard without instantiation, but we can do static analysis or return a placeholder)
        $channel = 'PlaceholderChannel';
        if ($reflection->hasMethod('broadcastOn')) {
            // Read file content and extract channel name roughly
            $filename = $reflection->getFileName();
            if ($filename !== false) {
                $content = file_get_contents($filename);
            } else {
                $content = false;
            }

            if ($content !== false) {
                if (
                    preg_match(
                        "/new\s+(?:PrivateChannel|PresenceChannel|Channel)\(['\"]([^'\"]+)['\"]/",
                        $content,
                        $matches,
                    )
                ) {
                    $channel = $matches[1];
                }
            }
        }

        $eventName = $reflection->getShortName();
        if ($reflection->hasMethod('broadcastAs')) {
            $filename = $reflection->getFileName();
            $content =
                $filename !== false ? file_get_contents($filename) : false;
            if ($content !== false) {
                if (
                    preg_match(
                        "/function\s+broadcastAs\b[^\{]*\{.*?return\s+['\"]([^'\"]+)['\"]/s",
                        $content,
                        $matches,
                    )
                ) {
                    $eventName = $matches[1];
                }
            }
        }

        return [
            'name' => $eventName,
            'description' => $reflection->getDocComment()
                ? $this->getSummaryFromDocBlock($reflection->getDocComment())
                : '',
            'channel' => $channel,
            'payload' => $payload,
        ];
    }

    private function getSummaryFromDocBlock(string $docBlock): string
    {
        preg_match("/@summary\s+(.*)/", $docBlock, $matches);
        if (isset($matches[1])) {
            return trim($matches[1]);
        }

        // Fallback to first line
        $lines = explode("\n", $docBlock);
        foreach ($lines as $line) {
            $line = preg_replace("/^\/?\*+/", '', $line);
            if ($line === null) {
                continue;
            }

            $line = trim($line);
            if (! empty($line) && ! Str::startsWith($line, '@')) {
                return $line;
            }
        }

        return '';
    }

    private function extractClassFromFile(string $path): ?string
    {
        $content = file_get_contents($path);
        if ($content === false) {
            return null;
        }
        if (! preg_match("/namespace\s+([^;]+);/i", $content, $matches)) {
            return null;
        }
        $namespace = trim($matches[1]);

        if (! preg_match("/class\s+([^\s{]+)/i", $content, $matches)) {
            return null;
        }
        $className = trim($matches[1]);

        return $namespace.'\\'.$className;
    }
}
