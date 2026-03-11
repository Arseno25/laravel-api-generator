<?php

namespace Arseno25\LaravelApiMagic\Services;

use Illuminate\Support\Facades\File;

final class ChangelogService
{
    /**
     * Save a schema snapshot to disk.
     *
     * @param  array<string, mixed>  $schema
     */
    public function saveSnapshot(array $schema): string
    {
        $path = config(
            'api-magic.changelog.storage_path',
            storage_path('api-magic/changelog'),
        );

        if (! File::isDirectory($path)) {
            File::makeDirectory($path, 0755, true);
        }

        $filename = date('Y-m-d_His').'.json';
        $fullPath = $path.'/'.$filename;
        $encodedSchema = json_encode(
            $schema,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
        );

        if ($encodedSchema === false) {
            throw new \RuntimeException('Unable to encode changelog snapshot.');
        }

        File::put($fullPath, $encodedSchema);

        return $fullPath;
    }

    /**
     * Get the list of available snapshots (newest first).
     *
     * @return list<array{filename: string, date: string, path: string}>
     */
    public function getSnapshots(): array
    {
        $path = config(
            'api-magic.changelog.storage_path',
            storage_path('api-magic/changelog'),
        );

        if (! File::isDirectory($path)) {
            return [];
        }

        $files = File::glob($path.'/*.json');
        rsort($files);

        return array_map(function ($file) {
            $filename = basename($file, '.json');

            return [
                'filename' => basename($file),
                'date' => str_replace('_', ' ', $filename),
                'path' => $file,
            ];
        }, $files);
    }

    /**
     * Get the latest snapshot schema.
     *
     * @return array<string, mixed>|null
     */
    public function getLatestSnapshot(): ?array
    {
        $snapshots = $this->getSnapshots();

        if (empty($snapshots)) {
            return null;
        }

        $content = File::get($snapshots[0]['path']);

        return json_decode($content, true);
    }

    /**
     * Compute a diff between two schema versions.
     *
     * @param  array<string, mixed>  $oldSchema
     * @param  array<string, mixed>  $newSchema
     * @return array<string, mixed>
     */
    public function computeDiff(array $oldSchema, array $newSchema): array
    {
        $oldEndpoints = $this->flattenEndpoints($oldSchema['endpoints'] ?? []);
        $newEndpoints = $this->flattenEndpoints($newSchema['endpoints'] ?? []);

        $added = array_diff_key($newEndpoints, $oldEndpoints);
        $removed = array_diff_key($oldEndpoints, $newEndpoints);

        $changed = [];
        foreach ($newEndpoints as $key => $endpoint) {
            if (isset($oldEndpoints[$key])) {
                $oldParams = json_encode(
                    $oldEndpoints[$key]['parameters'] ?? [],
                );
                $newParams = json_encode($endpoint['parameters'] ?? []);

                if ($oldParams !== $newParams) {
                    $changed[$key] = [
                        'old' => $oldEndpoints[$key],
                        'new' => $endpoint,
                    ];
                }
            }
        }

        return [
            'added' => $added,
            'removed' => $removed,
            'changed' => $changed,
            'total_added' => count($added),
            'total_removed' => count($removed),
            'total_changed' => count($changed),
        ];
    }

    /**
     * @param  array<string, array<string, array<string, mixed>>>  $endpoints
     *                                                                         Flatten nested endpoint structure into a keyed map.
     * @return array<string, array<string, mixed>>
     */
    private function flattenEndpoints(array $endpoints): array
    {
        $flat = [];

        foreach ($endpoints as $path => $methods) {
            foreach ($methods as $method => $endpoint) {
                $key = strtoupper($method).' '.$path;
                $flat[$key] = $endpoint;
            }
        }

        return $flat;
    }
}
