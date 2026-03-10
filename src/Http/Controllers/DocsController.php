<?php

namespace Arseno25\LaravelApiMagic\Http\Controllers;

use Arseno25\LaravelApiMagic\Exporters\InsomniaExporter;
use Arseno25\LaravelApiMagic\Exporters\PostmanExporter;
use Arseno25\LaravelApiMagic\Generators\CodeSnippetGenerator;
use Arseno25\LaravelApiMagic\Http\Middleware\ApiHealthMiddleware;
use Arseno25\LaravelApiMagic\Services\ChangelogService;
use Arseno25\LaravelApiMagic\Services\DocumentationSchemaBuilder;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\File;

final class DocsController extends Controller
{
    private const CACHE_PATH = 'bootstrap/cache/api-magic.json';

    public function __construct(
        private readonly DocumentationSchemaBuilder $schemaBuilder,
    ) {}

    public function index(): View
    {
        return view('api-magic::docs'); // @phpstan-ignore argument.type
    }

    public function json(Request $request): JsonResponse
    {
        $cachedData = $this->getCachedData();

        if ($cachedData !== null) {
            return response()->json($cachedData);
        }

        return response()->json($this->schemaBuilder->buildUiSchema($request));
    }

    public function export(Request $request): JsonResponse
    {
        $format = strtolower((string) $request->query('format', 'openapi'));

        if (! in_array($format, ['openapi', 'postman', 'insomnia'], true)) {
            return response()->json(
                [
                    'error' => 'Invalid format. Supported formats are: openapi, postman, insomnia',
                ],
                400,
            );
        }

        if ($format === 'postman') {
            $postman = app(PostmanExporter::class)->export(
                $this->schemaBuilder->buildInternalSchema($request),
                $request->getSchemeAndHttpHost(),
            );

            return response()
                ->json($postman)
                ->header(
                    'Content-Disposition',
                    'attachment; filename="postman-collection-'.date('Y-m-d').'.json"',
                );
        }

        if ($format === 'insomnia') {
            $insomnia = app(InsomniaExporter::class)->export(
                $this->schemaBuilder->buildInternalSchema($request),
                $request->getSchemeAndHttpHost(),
            );

            return response()
                ->json($insomnia)
                ->header(
                    'Content-Disposition',
                    'attachment; filename="insomnia-collection-'.date('Y-m-d').'.json"',
                );
        }

        return response()
            ->json($this->schemaBuilder->buildOpenApiSchema($request))
            ->header(
                'Content-Disposition',
                'attachment; filename="api-docs-'.date('Y-m-d').'.json"',
            );
    }

    /**
     * @return array<string, mixed>
     */
    public function generateSchemaPublic(Request $request): array
    {
        return $this->schemaBuilder->buildInternalSchema($request);
    }

    public function health(): JsonResponse
    {
        if (! config('api-magic.health.enabled', false)) {
            return response()->json(
                ['message' => 'Health telemetry is disabled.'],
                404,
            );
        }

        return response()->json([
            'metrics' => ApiHealthMiddleware::getMetrics(),
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    public function changelog(): JsonResponse
    {
        if (! config('api-magic.changelog.enabled', false)) {
            return response()->json(
                ['message' => 'Changelog is disabled.'],
                404,
            );
        }

        $service = new ChangelogService;
        $snapshots = $service->getSnapshots();

        if (count($snapshots) < 2) {
            return response()->json([
                'message' => 'Not enough snapshots for comparison. Run: php artisan api-magic:snapshot',
                'snapshots' => count($snapshots),
            ]);
        }

        $current = json_decode(file_get_contents($snapshots[0]['path']), true);
        $previous = json_decode(file_get_contents($snapshots[1]['path']), true);

        return response()->json([
            'diff' => $service->computeDiff($previous, $current),
            'current_snapshot' => $snapshots[0]['date'],
            'previous_snapshot' => $snapshots[1]['date'],
            'total_snapshots' => count($snapshots),
        ]);
    }

    public function codeSnippet(Request $request): JsonResponse
    {
        $method = $request->query('method', 'get');
        $path = $request->query('path', '/');
        $baseUrl = $request->query('base_url', $request->getSchemeAndHttpHost());

        $schema = $this->schemaBuilder->buildInternalSchema($request);
        $endpoint = $schema['endpoints'][$path][$method] ?? null;

        if ($endpoint === null) {
            return response()->json(['message' => 'Endpoint not found.'], 404);
        }

        return response()->json([
            'snippets' => (new CodeSnippetGenerator)->generate(
                $method,
                $path,
                $endpoint,
                $baseUrl,
            ),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function getOpenApiSchema(Request $request): array
    {
        return $this->schemaBuilder->buildOpenApiSchema($request);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getCachedData(): ?array
    {
        $cachePath = base_path(self::CACHE_PATH);

        if (! File::exists($cachePath)) {
            return null;
        }

        $cached = json_decode(File::get($cachePath), true);

        if (! is_array($cached) || ! isset($cached['generated_at'])) {
            return null;
        }

        return $cached;
    }
}
