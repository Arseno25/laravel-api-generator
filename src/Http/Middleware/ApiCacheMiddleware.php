<?php

namespace Arseno25\LaravelApiMagic\Http\Middleware;

use Arseno25\LaravelApiMagic\Attributes\ApiCache;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use ReflectionMethod;

class ApiCacheMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        // Only cache GET requests
        if ($request->method() !== "GET") {
            return $next($request);
        }

        // Check if caching is disabled globally
        if (!config("api-magic.cache.enabled", true)) {
            return $next($request);
        }

        $route = $request->route();
        if (!$route) {
            return $next($request);
        }

        $action = $route->getAction();
        $uses = $action["uses"] ?? null;

        if (!is_string($uses) || !str_contains($uses, "@")) {
            return $next($request);
        }

        [$controller, $method] = explode("@", $uses);

        $cacheAttribute = $this->getCacheAttribute($controller, $method);

        if (!$cacheAttribute) {
            return $next($request);
        }

        $cacheKey = $this->buildCacheKey($request);
        $store =
            $cacheAttribute->store ??
            config("api-magic.cache.store", config("cache.default"));

        // Try to return cached response
        $cached = Cache::store($store)->get($cacheKey);

        if ($cached !== null) {
            return new JsonResponse(
                $cached["data"],
                $cached["status"],
                array_merge($cached["headers"] ?? [], [
                    "X-Api-Cache" => "HIT",
                    "X-Api-Cache-TTL" => $cacheAttribute->ttl,
                ]),
            );
        }

        // Let the request through
        $response = $next($request);

        // Cache the response if it's successful
        if (
            $response instanceof JsonResponse &&
            $response->getStatusCode() >= 200 &&
            $response->getStatusCode() < 300
        ) {
            if ($response->headers->has("Vary")) {
                return $response;
            }

            $safeHeaders = [
                "Content-Type",
                "Content-Length",
                "Cache-Control",
                "ETag",
                "Last-Modified",
                "Expires",
                "Content-Language",
            ];
            $headersToCache = [];
            foreach ($safeHeaders as $header) {
                if ($response->headers->has($header)) {
                    $headersToCache[$header] = $response->headers->all($header);
                }
            }

            $responseContent = $response->getContent();
            $responseData = json_decode(
                is_string($responseContent) ? $responseContent : "",
                true,
            );

            Cache::store($store)->put(
                $cacheKey,
                [
                    "data" => is_array($responseData) ? $responseData : [],
                    "status" => $response->getStatusCode(),
                    "headers" => $headersToCache,
                ],
                $cacheAttribute->ttl,
            );

            $response->headers->set("X-Api-Cache", "MISS");
            $response->headers->set(
                "X-Api-Cache-TTL",
                (string) $cacheAttribute->ttl,
            );
        }

        return $response;
    }

    /**
     * Get the ApiCache attribute from a controller method.
     */
    private function getCacheAttribute(
        string $controller,
        string $method,
    ): ?ApiCache {
        try {
            if (!class_exists($controller)) {
                return null;
            }

            $reflection = new ReflectionMethod($controller, $method);
            $attributes = $reflection->getAttributes(ApiCache::class);

            if (empty($attributes)) {
                return null;
            }

            return $attributes[0]->newInstance();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Build a unique cache key for the request.
     */
    private function buildCacheKey(Request $request): string
    {
        $uri = $request->getPathInfo();
        $query = $request->query();

        ksort($query);

        $queryHash = md5(json_encode($query) ?: "");

        $identity = "anonymous";
        if ($request->user()) {
            $identity = "user_" . $request->user()->getAuthIdentifier();
        } elseif ($request->hasSession()) {
            $identity = "session_" . $request->session()->getId();
        }

        return "api_cache:{$identity}:GET:{$uri}:{$queryHash}";
    }
}
