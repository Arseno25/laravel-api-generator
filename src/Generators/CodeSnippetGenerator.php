<?php

namespace Arseno25\LaravelApiMagic\Generators;

final class CodeSnippetGenerator
{
    /**
     * Generate code snippets for an endpoint in multiple languages.
     *
     * @param  array<string, mixed>  $endpoint
     * @return array<string, string>
     */
    public function generate(string $method, string $path, array $endpoint, string $baseUrl): array
    {
        return [
            'curl' => $this->generateCurl($method, $path, $endpoint, $baseUrl),
            'javascript' => $this->generateJavascript($method, $path, $endpoint, $baseUrl),
            'php' => $this->generatePhp($method, $path, $endpoint, $baseUrl),
            'python' => $this->generatePython($method, $path, $endpoint, $baseUrl),
        ];
    }

    private function generateCurl(string $method, string $path, array $endpoint, string $baseUrl): string
    {
        $url = rtrim($baseUrl, '/').$path;
        $lines = ['curl -X '.strtoupper($method).' \\'];
        $lines[] = "  '{$url}' \\";
        $lines[] = "  -H 'Accept: application/json' \\";

        if (in_array(strtolower($method), ['post', 'put', 'patch'])) {
            $lines[] = "  -H 'Content-Type: application/json' \\";

            $body = $this->buildExampleBody($endpoint);
            if (! empty($body)) {
                $json = json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                $lines[] = "  -d '{$json}'";
            }
        }

        if (! empty($endpoint['security'])) {
            $lines[] = "  -H 'Authorization: Bearer YOUR_TOKEN'";
        }

        return implode("\n", $lines);
    }

    private function generateJavascript(string $method, string $path, array $endpoint, string $baseUrl): string
    {
        $url = rtrim($baseUrl, '/').$path;
        $upperMethod = strtoupper($method);

        $lines = ["const response = await fetch('{$url}', {"];
        $lines[] = "  method: '{$upperMethod}',";
        $lines[] = '  headers: {';
        $lines[] = "    'Accept': 'application/json',";

        if (in_array(strtolower($method), ['post', 'put', 'patch'])) {
            $lines[] = "    'Content-Type': 'application/json',";
        }

        if (! empty($endpoint['security'])) {
            $lines[] = "    'Authorization': `Bearer \${token}`,";
        }

        $lines[] = '  },';

        if (in_array(strtolower($method), ['post', 'put', 'patch'])) {
            $body = $this->buildExampleBody($endpoint);
            if (! empty($body)) {
                $json = json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                $lines[] = "  body: JSON.stringify({$json}),";
            }
        }

        $lines[] = '});';
        $lines[] = '';
        $lines[] = 'const data = await response.json();';

        return implode("\n", $lines);
    }

    private function generatePhp(string $method, string $path, array $endpoint, string $baseUrl): string
    {
        $url = rtrim($baseUrl, '/').$path;
        $httpMethod = strtolower($method);

        $lines = ['$response = Http::'];

        if (! empty($endpoint['security'])) {
            $lines[0] .= "withToken('YOUR_TOKEN')->";
        }

        if (in_array($httpMethod, ['post', 'put', 'patch'])) {
            $body = $this->buildExampleBody($endpoint);
            $bodyStr = ! empty($body) ? var_export($body, true) : '[]';
            $lines[0] .= "{$httpMethod}('{$url}', {$bodyStr});";
        } else {
            $lines[0] .= "{$httpMethod}('{$url}');";
        }

        $lines[] = '';
        $lines[] = '$data = $response->json();';

        return implode("\n", $lines);
    }

    private function generatePython(string $method, string $path, array $endpoint, string $baseUrl): string
    {
        $url = rtrim($baseUrl, '/').$path;
        $pyMethod = strtolower($method);

        $lines = ['import requests', ''];
        $lines[] = "headers = {'Accept': 'application/json'}";

        if (! empty($endpoint['security'])) {
            $lines[] = "headers['Authorization'] = 'Bearer YOUR_TOKEN'";
        }

        if (in_array($pyMethod, ['post', 'put', 'patch'])) {
            $body = $this->buildExampleBody($endpoint);
            $pyBody = ! empty($body) ? json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '{}';
            $lines[] = '';
            $lines[] = "data = {$pyBody}";
            $lines[] = '';
            $lines[] = "response = requests.{$pyMethod}('{$url}', json=data, headers=headers)";
        } else {
            $lines[] = '';
            $lines[] = "response = requests.{$pyMethod}('{$url}', headers=headers)";
        }

        $lines[] = 'result = response.json()';

        return implode("\n", $lines);
    }

    /**
     * Build example body from endpoint parameters.
     *
     * @return array<string, mixed>
     */
    private function buildExampleBody(array $endpoint): array
    {
        $body = [];

        foreach ($endpoint['parameters']['body'] ?? [] as $field) {
            $name = $field['name'] ?? 'unknown';
            $body[$name] = $this->getExampleValue($field);
        }

        return $body;
    }

    private function getExampleValue(array $field): mixed
    {
        $type = $field['type'] ?? 'string';

        if (! empty($field['enum'])) {
            return $field['enum'][0];
        }

        return match ($type) {
            'integer', 'int', 'bigint' => 1,
            'float', 'double', 'decimal' => 1.5,
            'boolean', 'bool' => true,
            'array', 'json' => [],
            'date' => '2024-01-01',
            'datetime', 'timestamp' => '2024-01-01T00:00:00Z',
            'email' => 'user@example.com',
            'url' => 'https://example.com',
            default => 'string',
        };
    }
}
