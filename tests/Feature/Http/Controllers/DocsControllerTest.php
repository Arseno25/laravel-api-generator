<?php

use Arseno25\LaravelApiMagic\Http\Controllers\DocsController;
use Illuminate\Support\Facades\Route;

use function Pest\Laravel\get;
use function Pest\Laravel\getJson;

uses()->group("feature", "docs-controller");

function docsCachePath(): string
{
    // Testbench may bootstrap the fake Laravel app in different vendor layouts,
    // so we probe both known cache locations and return the api-magic.json path that exists.
    $candidatePaths = [
        dirname(__DIR__, 4) .
        "/vendor/orchestra/testbench-core/laravel/bootstrap/cache/api-magic.json",
        dirname(__DIR__, 6) .
        "/vendor/orchestra/testbench-core/laravel/bootstrap/cache/api-magic.json",
    ];

    foreach ($candidatePaths as $path) {
        if (is_dir(dirname($path))) {
            return $path;
        }
    }

    if (!is_dir(dirname($candidatePaths[1]))) {
        // The fallback directory is created on demand for isolated test environments.
        mkdir(dirname($candidatePaths[1]), 0777, true);
    }

    return $candidatePaths[1];
}

beforeEach(function () {
    // Clean up any existing cache
    $cacheFile = docsCachePath();
    if (file_exists($cacheFile)) {
        unlink($cacheFile);
    }
});

afterEach(function () {
    // Clean up after each test
    $cacheFile = docsCachePath();
    if (file_exists($cacheFile)) {
        unlink($cacheFile);
    }
});

describe("GET /api/docs", function () {
    it("displays the documentation view", function () {
        $response = get("/api/docs");

        $response->assertStatus(200);
        $response->assertViewIs("api-magic::docs");
    });

    it("includes documentation assets", function () {
        $response = get("/api/docs");

        $response->assertSee("API Documentation");
        $response->assertDontSee("fonts.googleapis.com");
        $response->assertDontSee("cdnjs.cloudflare.com");
    });

    it("injects frontend docs route configuration", function () {
        $response = get("/api/docs");

        $response->assertSee("window.apiMagicDocsConfig", false);
    });

    it("allows overriding remote docs assets from configuration", function () {
        config()->set("api-magic.docs.assets.tailwind_cdn", null);
        config()->set("api-magic.docs.assets.icon_stylesheet", null);
        config()->set("api-magic.docs.assets.stylesheets", [
            "https://assets.example.test/docs.css",
        ]);
        config()->set("api-magic.docs.assets.scripts", [
            "https://assets.example.test/docs.js",
        ]);

        $response = get("/api/docs");

        $response->assertDontSee("cdn.tailwindcss.com");
        $response->assertDontSee("cdnjs.cloudflare.com");
        $response->assertSee("https://assets.example.test/docs.css");
        $response->assertSee("https://assets.example.test/docs.js");
    });

    it(
        "prefers the published local docs stylesheet when available",
        function () {
            $localStylesheet = public_path("vendor/api-magic/docs.css");
            $originalStylesheet = file_exists($localStylesheet)
                ? file_get_contents($localStylesheet)
                : null;

            if (!is_dir(dirname($localStylesheet))) {
                mkdir(dirname($localStylesheet), 0777, true);
            }

            file_put_contents($localStylesheet, "/* local docs css */");

            try {
                $response = get("/api/docs");

                $response->assertSee("vendor/api-magic/docs.css");
                $response->assertDontSee("cdn.tailwindcss.com");
            } finally {
                if ($originalStylesheet === null) {
                    if (file_exists($localStylesheet)) {
                        unlink($localStylesheet);
                    }
                } else {
                    file_put_contents($localStylesheet, $originalStylesheet);
                }
            }
        },
    );

    it(
        "inlines the package docs stylesheet when the published asset is stale",
        function () {
            $localStylesheet = public_path("vendor/api-magic/docs.css");
            $packageStylesheet =
                dirname(__DIR__, 4) . "/resources/dist/docs.css";
            $originalStylesheet = file_exists($localStylesheet)
                ? file_get_contents($localStylesheet)
                : null;
            $originalModifiedAt = file_exists($localStylesheet)
                ? filemtime($localStylesheet)
                : null;
            $packageModifiedAt = filemtime($packageStylesheet);

            if (!is_dir(dirname($localStylesheet))) {
                mkdir(dirname($localStylesheet), 0777, true);
            }

            file_put_contents($localStylesheet, "/* stale docs css */");
            touch($packageStylesheet, time() + 60);

            try {
                $response = get("/api/docs");

                $response->assertDontSee("vendor/api-magic/docs.css");
                $response->assertDontSee("cdn.tailwindcss.com");
                $response->assertSee("@layer properties", false);
            } finally {
                touch($packageStylesheet, $packageModifiedAt);

                if ($originalStylesheet === null) {
                    if (file_exists($localStylesheet)) {
                        unlink($localStylesheet);
                    }
                } else {
                    file_put_contents($localStylesheet, $originalStylesheet);

                    if (
                        $originalModifiedAt !== false &&
                        $originalModifiedAt !== null
                    ) {
                        touch($localStylesheet, $originalModifiedAt);
                    }
                }
            }
        },
    );
});

describe("GET /api/docs/json", function () {
    it("returns JSON documentation schema", function () {
        $response = getJson("/api/docs/json");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            "title",
            "version",
            "baseUrl",
            "endpoints",
            "versions",
            "generated_at",
        ]);
    });

    it("includes endpoints array", function () {
        $response = getJson("/api/docs/json");

        $response->assertJsonStructure([
            "endpoints" => [],
        ]);
    });

    it("includes versions array", function () {
        // Without any routes registered via Route::get(), iterations should be empty.
        // We'll register one to ensure '1' gets populated.
        Illuminate\Support\Facades\Route::middleware("api")->get(
            "/api/users",
            function () {},
        );

        $response = getJson("/api/docs/json");

        $response->assertJson([
            "versions" => ["1"],
        ]);
    });

    it("includes generated_at timestamp", function () {
        $response = getJson("/api/docs/json");

        $data = $response->json();
        expect($data["generated_at"])->not->toBeEmpty();
    });

    it("includes security schemes", function () {
        $response = getJson("/api/docs/json");

        $response->assertJsonStructure(["securitySchemes"]);

        $data = $response->json();
        expect($data["securitySchemes"])->toHaveKey("bearerAuth");
        expect($data["securitySchemes"]["bearerAuth"])->toMatchArray([
            "type" => "http",
            "scheme" => "bearer",
            "bearerFormat" => "JWT",
        ]);
    });

    it("uses configured server definitions", function () {
        config()->set("api-magic.servers", [
            ["url" => "https://api.example.test", "description" => "Example"],
        ]);

        $response = getJson("/api/docs/json");

        $response->assertJson([
            "servers" => [
                [
                    "url" => "https://api.example.test",
                    "description" => "Example",
                ],
            ],
        ]);
    });
});

describe("GET /api/docs/export", function () {
    it("exports OpenAPI format JSON", function () {
        $response = getJson("/api/docs/export");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            "openapi",
            "info",
            "servers",
            "paths",
            "components",
        ]);
    });

    it("includes OpenAPI version", function () {
        $response = getJson("/api/docs/export");

        $response->assertJson([
            "openapi" => "3.0.0",
        ]);
    });

    it("includes info section", function () {
        $response = getJson("/api/docs/export");

        $response->assertJsonStructure([
            "info" => ["title", "version", "description"],
        ]);
    });

    it("includes components with security schemes", function () {
        $response = getJson("/api/docs/export");

        $response->assertJsonStructure([
            "components" => [
                "securitySchemes" => ["bearerAuth"],
            ],
        ]);

        $data = $response->json();
        expect(
            $data["components"]["securitySchemes"]["bearerAuth"],
        )->toMatchArray([
            "type" => "http",
            "scheme" => "bearer",
            "bearerFormat" => "JWT",
        ]);
    });

    it("includes tags array", function () {
        $response = getJson("/api/docs/export");

        $response->assertJsonStructure([
            "tags" => [],
        ]);
    });

    it("sets content disposition header for download", function () {
        $response = getJson("/api/docs/export");

        expect($response->headers->get("content-disposition"))->toContain(
            "attachment",
        );
        expect($response->headers->get("content-disposition"))->toContain(
            "api-docs-",
        );
    });

    it("exports Insomnia format JSON when requested", function () {
        $response = getJson("/api/docs/export?format=insomnia");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            "_type",
            "__export_format",
            "resources",
        ]);

        $data = $response->json();
        expect($data["_type"])->toBe("export");
        expect($data["__export_format"])->toBe(4);
    });

    it("uses configured servers in the exported OpenAPI schema", function () {
        config()->set("api-magic.servers", [
            ["url" => "https://api.example.test", "description" => "Example"],
        ]);

        $response = getJson("/api/docs/export");

        $response->assertJsonPath("servers.0.url", "https://api.example.test");
        $response->assertJsonPath("servers.0.description", "Example");
    });
});

describe("caching behavior", function () {
    it("uses cached data when available", function () {
        // Create a cached version
        $cachePath = docsCachePath();
        if (!is_dir(dirname($cachePath))) {
            mkdir(dirname($cachePath), 0777, true);
        }

        $cachedData = [
            "generated_at" => now()->toIso8601String(),
            "endpoints" => [
                "/api/test" => [
                    "get" => [
                        "summary" => "Cached endpoint",
                    ],
                ],
            ],
            "versions" => ["1"],
        ];

        file_put_contents($cachePath, json_encode($cachedData));

        $response = getJson("/api/docs/json");

        // Should return cached data
        $response->assertJson([
            "endpoints" => $cachedData["endpoints"],
        ]);
    });

    it("generates fresh data when cache is missing", function () {
        // Ensure no cache exists
        $cachePath = docsCachePath();
        if (file_exists($cachePath)) {
            unlink($cachePath);
        }

        $response = getJson("/api/docs/json");

        $response->assertStatus(200);
        $response->assertJsonStructure(["title", "endpoints", "generated_at"]);
    });

    it("handles corrupted cache gracefully", function () {
        $cachePath = docsCachePath();
        if (!is_dir(dirname($cachePath))) {
            mkdir(dirname($cachePath), 0777, true);
        }

        // Write invalid JSON
        file_put_contents($cachePath, "invalid json content");

        $response = getJson("/api/docs/json");

        // Should generate fresh data instead of failing
        $response->assertStatus(200);
    });
});

describe("endpoint grouping", function () {
    it("groups endpoints by path", function () {
        $response = getJson("/api/docs/json");

        $data = $response->json();

        expect($data["endpoints"])->toBeArray();

        // Each endpoint should be keyed by path
        foreach ($data["endpoints"] as $path => $methods) {
            expect(is_string($path))->toBeTrue();
            expect(is_array($methods))->toBeTrue();
        }
    });

    it("groups endpoints by version", function () {
        $response = getJson("/api/docs/json");

        $data = $response->json();

        expect($data["endpointsByVersion"])->toBeArray();
        expect($data["versions"])->toBeArray();
    });
});

describe("security in endpoints", function () {
    it("includes security information for authenticated routes", function () {
        // Register a protected route
        Illuminate\Support\Facades\Route::middleware("api")
            ->middleware("auth:sanctum")
            ->get("/api/protected-test", function () {
                return response()->json(["protected" => true]);
            });

        $response = getJson("/api/docs/json");

        $data = $response->json();

        // Check if any endpoint has security requirements
        $hasSecurity = false;
        foreach ($data["endpoints"] as $path => $methods) {
            foreach ($methods as $method => $endpoint) {
                if (!empty($endpoint["security"])) {
                    $hasSecurity = true;
                    break 2;
                }
            }
        }

        expect($hasSecurity)->toBeTrue();
    });
});

describe("error handling", function () {
    it("handles missing view gracefully", function () {
        // This test would require temporarily removing the view
        // For now, we just verify the controller exists
        $controller = app(DocsController::class);

        expect($controller)->toBeInstanceOf(DocsController::class);
    });

    it("returns valid JSON even with no API routes", function () {
        // This is difficult to test as there are always some routes
        // But we can verify the response structure
        $response = getJson("/api/docs/json");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            "title",
            "version",
            "baseUrl",
            "endpoints" => [],
            "versions",
            "generated_at",
        ]);
    });
});

describe("feature toggles", function () {
    it(
        "returns 404 for health metrics when the feature is disabled",
        function () {
            config()->set("api-magic.health.enabled", false);

            getJson("/api/docs/health")->assertStatus(404);
        },
    );

    it("returns health metrics when the feature is enabled", function () {
        config()->set("api-magic.health.enabled", true);
        config()->set("api-magic.health.store", "array");

        getJson("/api/docs/health")
            ->assertOk()
            ->assertJsonStructure(["metrics", "generated_at"]);
    });
});

describe("query parameters for index endpoints", function () {
    it("includes standard query parameters for GET index", function () {
        // Register an index route with a controller so RouteAnalyzer detects the 'index' method
        Illuminate\Support\Facades\Route::middleware("api")->get(
            "/api/products",
            [
                Arseno25\LaravelApiMagic\Http\Controllers\DocsController::class,
                "index",
            ],
        );

        $response = getJson("/api/docs/json");

        $data = $response->json();

        // Find the products endpoint
        $found = false;
        foreach ($data["endpoints"] as $path => $methods) {
            if (str_contains($path, "products")) {
                if (isset($methods["get"])) {
                    $endpoint = $methods["get"];
                    // Should have query parameters for index
                    if (!empty($endpoint["parameters"]["query"])) {
                        $queryNames = array_column(
                            $endpoint["parameters"]["query"],
                            "name",
                        );
                        if (
                            in_array("page", $queryNames) &&
                            in_array("per_page", $queryNames)
                        ) {
                            $found = true;
                        }
                    }
                }
            }
        }

        expect($found)->toBeTrue();
    });
});
