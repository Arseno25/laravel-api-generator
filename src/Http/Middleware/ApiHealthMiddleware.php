<?php

namespace Arseno25\LaravelApiMagic\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ApiHealthMiddleware
{
    /**
     * Handle an incoming request and record telemetry.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if (!config("api-magic.health.enabled", false)) {
            return $next($request);
        }

        $startTime = microtime(true);

        $response = $next($request);

        $duration = round((microtime(true) - $startTime) * 1000, 2);
        $statusCode = $response->getStatusCode();
        $path = $request->getPathInfo();
        $method = strtoupper($request->method());

        $this->recordMetric($method, $path, $statusCode, $duration);

        return $response;
    }

    /**
     * Record a single request metric.
     */
    private function recordMetric(
        string $method,
        string $path,
        int $statusCode,
        float $durationMs,
    ): void {
        $store = config("api-magic.health.store") ?? config("cache.default");
        $window = config("api-magic.health.window", 60);
        $key = "api_health:{$method}:{$path}";

        $metrics = Cache::store($store)->get($key, [
            "total_requests" => 0,
            "total_errors" => 0,
            "total_duration_ms" => 0,
            "status_codes" => [],
            "last_request_at" => null,
            "window_start" => now()->toIso8601String(),
        ]);

        $metrics["total_requests"]++;
        $metrics["total_duration_ms"] += $durationMs;
        $metrics["last_request_at"] = now()->toIso8601String();

        if ($statusCode >= 400) {
            $metrics["total_errors"]++;
        }

        $statusKey = (string) $statusCode;
        $metrics["status_codes"][$statusKey] =
            ($metrics["status_codes"][$statusKey] ?? 0) + 1;

        Cache::store($store)->put($key, $metrics, $window * 60);

        // Track global endpoint list
        $endpointListKey = "api_health:endpoints";
        $endpoints = Cache::store($store)->get($endpointListKey, []);
        $endpointKey = "{$method} {$path}";
        if (!in_array($endpointKey, $endpoints)) {
            $endpoints[] = $endpointKey;
        }

        Cache::store($store)->put($endpointListKey, $endpoints, $window * 60);
    }

    /**
     * Get all recorded health metrics.
     *
     * @return list<array<string, mixed>>
     */
    public static function getMetrics(): array
    {
        $store = config("api-magic.health.store") ?? config("cache.default");
        $endpoints = Cache::store($store)->get("api_health:endpoints", []);

        $metrics = [];
        foreach ($endpoints as $endpoint) {
            [$method, $path] = explode(" ", $endpoint, 2);
            $key = "api_health:{$method}:{$path}";
            $data = Cache::store($store)->get($key);

            if ($data) {
                $avgDuration =
                    $data["total_requests"] > 0
                        ? round(
                            $data["total_duration_ms"] /
                                $data["total_requests"],
                            2,
                        )
                        : 0;

                $errorRate =
                    $data["total_requests"] > 0
                        ? round(
                            ($data["total_errors"] / $data["total_requests"]) *
                                100,
                            1,
                        )
                        : 0;

                $metrics[] = [
                    "method" => $method,
                    "path" => $path,
                    "total_requests" => $data["total_requests"],
                    "total_errors" => $data["total_errors"],
                    "avg_response_ms" => $avgDuration,
                    "error_rate" => $errorRate,
                    "status_codes" => $data["status_codes"],
                    "last_request_at" => $data["last_request_at"],
                ];
            }
        }

        // Sort by total requests desc
        usort(
            $metrics,
            fn($a, $b) => $b["total_requests"] <=> $a["total_requests"],
        );

        return $metrics;
    }
}
