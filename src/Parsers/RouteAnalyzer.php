<?php

namespace Arseno25\LaravelApiMagic\Parsers;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Str;

final class RouteAnalyzer
{
    private array $excludePatterns = [
        'sanctum',
        'passport',
        'oauth',
        'telescope',
        'horizon',
        'ignition',
        '_ignition',
        'docs',
        'schema',
    ];

    /**
     * Get all API routes from the application.
     *
     * @return array<int, Route>
     */
    public function getApiRoutes(): array
    {
        // Laravel 12+ doesn't have getRoutesByType(), use getRoutes() instead
        $allRoutes = RouteFacade::getRoutes()->getRoutes();

        // In Laravel < 12, try getRoutesByType() first if available
        if (method_exists(RouteFacade::getRoutes(), 'getRoutesByType')) {
            $routes = RouteFacade::getRoutes()->getRoutesByType();
            if (!empty($routes)) {
                $allRoutes = [];
                foreach ($routes as $routeCollection) {
                    foreach ($routeCollection as $route) {
                        $allRoutes[] = $route;
                    }
                }
            }
        }

        $apiRoutes = [];
        foreach ($allRoutes as $route) {
            if ($this->isApiRoute($route) && $this->shouldIncludeRoute($route)) {
                $apiRoutes[] = $route;
            }
        }

        return $apiRoutes;
    }

    /**
     * Determine if a route is an API route.
     */
    public function isApiRoute(Route $route): bool
    {
        // Check if route has 'api' middleware
        $middleware = $route->gatherMiddleware();

        foreach ($middleware as $m) {
            if (is_string($m) && str_contains($m, 'api')) {
                return true;
            }
        }

        // Check if URI starts with /api
        $uri = $route->uri;

        return str_starts_with($uri, 'api');
    }

    /**
     * Determine if route should be included in documentation.
     */
    public function shouldIncludeRoute(Route $route): bool
    {
        $uri = $route->uri;

        // Exclude documentation endpoints themselves
        if (str_contains($uri, 'api/docs') || str_contains($uri, 'api/schema')) {
            return false;
        }

        // Exclude based on patterns
        foreach ($this->excludePatterns as $pattern) {
            if (str_contains($uri, $pattern)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Parse a route into documentation format.
     *
     * @return array<string, mixed>|null
     */
    public function parseRoute(Route $route, RequestAnalyzer $requestAnalyzer): ?array
    {
        $methods = array_filter($route->methods, fn ($m) => $m !== 'HEAD');

        if (empty($methods)) {
            return null;
        }

        $method = strtolower($methods[0]);
        $uri = '/'.$route->uri;
        $action = $route->getAction();

        // Extract request class
        $requestClass = $requestAnalyzer->extractRequestFromAction($action);
        $requestRules = $requestClass ? $requestAnalyzer->analyze($requestClass) : [];

        // Get route name or generate one
        $name = $route->getName() ?: $this->generateRouteName($method, $uri);

        // Extract parameters from URI
        $pathParameters = $this->extractUriParameters($uri);

        // Get controller and method info
        $controllerInfo = $this->extractControllerInfo($action);

        // Extract API version
        $version = $this->extractVersion($uri, $controllerInfo);

        // Add query parameters for GET index endpoints
        $queryParameters = [];
        if ($method === 'get' && $controllerInfo['method'] === 'index') {
            $queryParameters = $requestAnalyzer->getIndexQueryParameters();
        }

        return [
            'method' => $method,
            'path' => $uri,
            'name' => $name,
            'summary' => $this->generateSummary($method, $uri, $controllerInfo),
            'description' => $this->generateDescription($method, $uri),
            'parameters' => [
                'path' => $pathParameters,
                'query' => $queryParameters,
                'body' => $requestRules,
            ],
            'controller' => $controllerInfo,
            'request' => $requestClass ? class_basename($requestClass) : null,
            'tags' => $this->generateTags($uri, $version),
            'security' => $this->analyzeSecurity($route),
            'version' => $version,
        ];
    }

    /**
     * Extract path parameters from URI.
     *
     * @return array<int, array<string, string>>
     */
    private function extractUriParameters(string $uri): array
    {
        preg_match_all('/\{([^}]+)\}/', $uri, $matches);

        $parameters = [];

        if (! empty($matches[1])) {
            foreach ($matches[1] as $param) {
                $type = 'string';

                if ($param === 'id') {
                    $type = 'integer';
                }

                $parameters[] = [
                    'name' => $param,
                    'type' => $type,
                    'required' => true,
                    'description' => "The {$param} parameter",
                    'in' => 'path',
                ];
            }
        }

        return $parameters;
    }

    /**
     * Extract controller information from route action.
     *
     * @return array<string, string|null>
     */
    private function extractControllerInfo(array|string|null $action): array
    {
        if (! is_array($action)) {
            return [
                'controller' => null,
                'method' => null,
            ];
        }

        $uses = $action['uses'] ?? null;

        if (is_string($uses) && str_contains($uses, '@')) {
            [$controller, $method] = explode('@', $uses);

            return [
                'controller' => class_basename($controller),
                'method' => $method,
                'full_path' => $controller,
            ];
        }

        return [
            'controller' => null,
            'method' => null,
        ];
    }

    /**
     * Generate route name from method and URI.
     */
    private function generateRouteName(string $method, string $uri): string
    {
        return strtoupper($method).' '.$uri;
    }

    /**
     * Generate summary for route.
     *
     * @param  array<string, string|null>  $controllerInfo
     */
    private function generateSummary(string $method, string $uri, array $controllerInfo): string
    {
        $resource = $this->extractResourceName($uri);
        $action = $this->guessAction($method, $uri, $controllerInfo);

        if ($resource && $action) {
            return "{$action} {$resource}";
        }

        return $this->formatUriTitle($method, $uri);
    }

    /**
     * Generate detailed description for route.
     */
    private function generateDescription(string $method, string $uri): string
    {
        $summaries = match ($method) {
            'GET' => str_ends_with($uri, '}') || ! str_contains($uri, '{')
                ? 'Retrieve a list of resources'
                : 'Retrieve a single resource',
            'POST' => 'Create a new resource',
            'PUT', 'PATCH' => 'Update an existing resource',
            'DELETE' => 'Delete a resource',
            default => '',
        };

        return $summaries;
    }

    /**
     * Extract resource name from URI.
     */
    private function extractResourceName(string $uri): ?string
    {
        // Remove 'api/' prefix
        $uri = preg_replace('#^api/#', '', $uri);

        // Get the first segment
        $segments = explode('/', $uri);
        $resource = $segments[0] ?? null;

        if (! $resource) {
            return null;
        }

        // Convert to singular form for display
        return Str::singular(Str::studly($resource));
    }

    /**
     * Guess the action being performed.
     *
     * @param  array<string, string|null>  $controllerInfo
     */
    private function guessAction(string $method, string $uri, array $controllerInfo): ?string
    {
        $controllerMethod = $controllerInfo['method'];

        if ($controllerMethod) {
            return match ($controllerMethod) {
                'index' => 'List all',
                'show' => 'Get single',
                'store' => 'Create',
                'update' => 'Update',
                'destroy' => 'Delete',
                default => null,
            };
        }

        $hasId = str_contains($uri, '{');

        return match ($method) {
            'GET' => $hasId ? 'Get' : 'List',
            'POST' => 'Create',
            'PUT', 'PATCH' => 'Update',
            'DELETE' => 'Delete',
            default => null,
        };
    }

    /**
     * Format URI as a title.
     */
    private function formatUriTitle(string $method, string $uri): string
    {
        $title = str_replace(['{', '}'], '', $uri);
        $title = str_replace(['-', '_'], ' ', $title);
        $title = Str::title($title);

        return strtoupper($method).' '.$title;
    }

    /**
     * Extract API version from URI or controller namespace.
     *
     * @param  array<string, string|null>  $controllerInfo
     */
    public function extractVersion(string $uri, array $controllerInfo): string
    {
        // First, check URI for version prefix (e.g., /api/v2/products)
        if (preg_match('#/api/v(\d+)/#i', $uri, $matches)) {
            return $matches[1];
        }

        // Second, check controller namespace (e.g., App\Http\Controllers\Api\V2)
        $fullPath = $controllerInfo['full_path'] ?? '';
        if (preg_match('#\\\\Api\\\\V(\d+)\\\\#i', $fullPath, $matches)) {
            return $matches[1];
        }

        return '1';
    }

    /**
     * Generate tags for grouping endpoints.
     *
     * @return array<int, string>
     */
    private function generateTags(string $uri, string $version = '1'): array
    {
        // Remove 'api/' prefix and version prefix
        $uri = preg_replace('#^api/v\d+/#i', '', $uri);
        $uri = preg_replace('#^api/#', '', $uri);

        // Get the first segment as the main tag
        $segments = explode('/', $uri);
        $mainTag = Str::studly($segments[0] ?? 'General');

        $tags = [$mainTag];

        // Add version tag if not v1
        if ($version !== '1') {
            $tags[] = "v{$version}";
        }

        return $tags;
    }

    /**
     * Analyze security requirements for the route.
     *
     * @return array<int, array<string, mixed>>
     */
    private function analyzeSecurity(Route $route): array
    {
        $middleware = $route->gatherMiddleware();
        $security = [];
        $requiresAuth = false;

        foreach ($middleware as $m) {
            if (! is_string($m)) {
                continue;
            }

            // Check for auth middleware (auth:sanctum, auth:api, auth, etc.)
            if (preg_match('#^auth(:|$)#', $m)) {
                $requiresAuth = true;
            }

            // Check for can middleware (authorization gates/policies)
            if (str_contains($m, 'can:')) {
                $security[] = [
                    'type' => 'authorization',
                    'description' => 'Requires specific permission: '.$m,
                ];
            }
        }

        // If authentication is required, add bearer auth security
        if ($requiresAuth) {
            array_unshift($security, [
                'type' => 'http',
                'scheme' => 'bearer',
                'description' => 'Laravel Sanctum/Passport authentication required',
            ]);
        }

        // Check for throttle (rate limiting)
        foreach ($middleware as $m) {
            if (is_string($m) && str_contains($m, 'throttle')) {
                $security[] = [
                    'type' => 'rateLimit',
                    'description' => 'Rate limited: '.$m,
                ];
            }
        }

        return $security;
    }
}
