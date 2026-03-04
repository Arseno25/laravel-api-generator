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
            'dart' => $this->generateDart($method, $path, $endpoint, $baseUrl),
            'swift' => $this->generateSwift($method, $path, $endpoint, $baseUrl),
            'go' => $this->generateGo($method, $path, $endpoint, $baseUrl),
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

    private function generateDart(string $method, string $path, array $endpoint, string $baseUrl): string
    {
        $url = rtrim($baseUrl, '/').$path;
        $httpMethod = strtolower($method);

        $lines = ["import 'package:http/http.dart' as http;", "import 'dart:convert';", ''];
        $lines[] = "var url = Uri.parse('{$url}');";
        $lines[] = "var headers = {'Accept': 'application/json'};";

        if (! empty($endpoint['security'])) {
            $lines[] = "headers['Authorization'] = 'Bearer YOUR_TOKEN';";
        }

        if (in_array($httpMethod, ['post', 'put', 'patch'])) {
            $lines[] = "headers['Content-Type'] = 'application/json';";
            $body = $this->buildExampleBody($endpoint);
            $dartBody = ! empty($body) ? json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '{}';
            $lines[] = "var body = jsonEncode({$dartBody});";
            $lines[] = "var response = await http.{$httpMethod}(url, headers: headers, body: body);";
        } else {
            $lines[] = "var response = await http.{$httpMethod}(url, headers: headers);";
        }

        $lines[] = 'var data = jsonDecode(response.body);';

        return implode("\n", $lines);
    }

    private function generateSwift(string $method, string $path, array $endpoint, string $baseUrl): string
    {
        $url = rtrim($baseUrl, '/').$path;
        $upperMethod = strtoupper($method);

        $lines = ['import Foundation', ''];
        $lines[] = "var request = URLRequest(url: URL(string: \"{$url}\")!)";
        $lines[] = "request.httpMethod = \"{$upperMethod}\"";
        $lines[] = 'request.addValue("application/json", forHTTPHeaderField: "Accept")';

        if (! empty($endpoint['security'])) {
            $lines[] = 'request.addValue("Bearer YOUR_TOKEN", forHTTPHeaderField: "Authorization")';
        }

        if (in_array(strtolower($method), ['post', 'put', 'patch'])) {
            $lines[] = 'request.addValue("application/json", forHTTPHeaderField: "Content-Type")';
            $body = $this->buildExampleBody($endpoint);
            $swiftBody = ! empty($body) ? json_encode($body, JSON_UNESCAPED_SLASHES) : '{}';
            // Simple escape for Swift multiline or single line string
            $swiftBodyEscaped = str_replace('"', '\"', $swiftBody);
            $lines[] = "let bodyString = \"{$swiftBodyEscaped}\"";
            $lines[] = 'request.httpBody = bodyString.data(using: .utf8)';
        }

        $lines[] = '';
        $lines[] = 'let task = URLSession.shared.dataTask(with: request) { data, response, error in';
        $lines[] = '    guard let data = data else { return }';
        $lines[] = '    print(String(data: data, encoding: .utf8)!)';
        $lines[] = '}';
        $lines[] = 'task.resume()';

        return implode("\n", $lines);
    }

    private function generateGo(string $method, string $path, array $endpoint, string $baseUrl): string
    {
        $url = rtrim($baseUrl, '/').$path;
        $upperMethod = strtoupper($method);

<<<<<<< HEAD
        $lines = ["package main", "", "import (", "\t\"fmt\"", "\t\"net/http\"", "\t\"io\""];
=======
        $lines = ['package main', '', 'import (', "\t\"fmt\"", "\t\"net/http\"", "\t\"io\"", "\t\"strings\"", ')', '', 'func main() {'];
>>>>>>> e98d5bf9a09385848f6e09df4f61219b1f8156ab

        if (in_array(strtolower($method), ['post', 'put', 'patch'])) {
            $lines[] = "\t\"strings\"";
            $lines[] = ")";
            $lines[] = "";
            $lines[] = "func main() {";
            $body = $this->buildExampleBody($endpoint);
            $goBody = ! empty($body) ? json_encode($body, JSON_UNESCAPED_SLASHES) : '{}';
            $goBodyEscaped = str_replace('"', '\"', $goBody);
            $lines[] = "\tbody := strings.NewReader(\"{$goBodyEscaped}\")";
            $lines[] = "\treq, _ := http.NewRequest(\"{$upperMethod}\", \"{$url}\", body)";
            $lines[] = "\treq.Header.Add(\"Content-Type\", \"application/json\")";
        } else {
            $lines[] = ")";
            $lines[] = "";
            $lines[] = "func main() {";
            $lines[] = "\treq, _ := http.NewRequest(\"{$upperMethod}\", \"{$url}\", nil)";
        }

        $lines[] = "\treq.Header.Add(\"Accept\", \"application/json\")";

        if (! empty($endpoint['security'])) {
            $lines[] = "\treq.Header.Add(\"Authorization\", \"Bearer YOUR_TOKEN\")";
        }

        $lines[] = "\tres, _ := http.DefaultClient.Do(req)";
        $lines[] = "\tdefer res.Body.Close()";
        $lines[] = "\trespBody, _ := io.ReadAll(res.Body)";
        $lines[] = "\tfmt.Println(string(respBody))";
        $lines[] = '}';

        return implode("\n", $lines);
    }

    private function buildExampleBody(array $endpoint): array
    {
        $body = [];

        $bodyParams = $endpoint['parameters']['body'] ?? [];
        if (! is_array($bodyParams)) {
            return $body;
        }

        foreach ($bodyParams as $key => $field) {
            if (! is_array($field)) {
                $body[$key] = $field;

                continue;
            }

            $name = is_string($key) ? $key : ($field['name'] ?? 'unknown');
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
