<?php

namespace Arseno25\LaravelApiMagic\Parsers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

final class DatabaseSchemaParser
{
    private readonly DatabaseColumnMetadataInferrer $metadataInferrer;

    public function __construct(
        ?DatabaseColumnMetadataInferrer $metadataInferrer = null,
    ) {
        $this->metadataInferrer =
            $metadataInferrer ?? new DatabaseColumnMetadataInferrer;
    }

    /**
     * Get all user tables (excluding system tables).
     *
     * @param  list<string>  $exclude
     * @return array<int, string>
     */
    public function getTables(array $exclude = []): array
    {
        $defaultExclude = [
            'migrations',
            'password_resets',
            'password_reset_tokens',
            'failed_jobs',
            'personal_access_tokens',
            'jobs',
            'job_batches',
            'sessions',
            'cache',
            'cache_locks',
            'telescope_entries',
            'telescope_entries_tags',
            'telescope_monitoring',
        ];

        $exclude = array_merge($defaultExclude, $exclude);

        $tables = array_map(
            fn (
                string $table,
            ): string => $this->metadataInferrer->normalizeTableName($table),
            Schema::getTableListing(),
        );

        return array_values(
            array_filter(
                array_unique($tables),
                fn ($table) => ! in_array($table, $exclude, true),
            ),
        );
    }

    /**
     * Parse a database table into a schema definition.
     *
     * @return array<string, mixed>
     */
    public function parseTable(string $table): array
    {
        $table = $this->metadataInferrer->normalizeTableName($table);
        $columns = Schema::getColumns($table);
        $driver = $this->resolveDriverName();

        $fields = [];
        $relationships = [];
        $fillable = [];
        $casts = [];
        $rules = [];
        $hasSoftDeletes = false;
        $hasTimestamps = false;

        foreach ($columns as $column) {
            $metadata = $this->metadataInferrer->infer($column, $driver);
            $name = $metadata['name'];
            $nullable = $metadata['nullable'];

            // Skip auto-managed columns
            if ($name === 'id') {
                continue;
            }

            if (in_array($name, ['created_at', 'updated_at'])) {
                $hasTimestamps = true;

                continue;
            }

            if ($name === 'deleted_at') {
                $hasSoftDeletes = true;

                continue;
            }

            // Detect foreign keys → BelongsTo relationships
            if (str_ends_with($name, '_id')) {
                $relatedModel = Str::studly(Str::beforeLast($name, '_id'));
                $relationships[] = [
                    'type' => 'belongsTo',
                    'model' => $relatedModel,
                    'foreignKey' => $name,
                ];
                $fillable[] = $name;
                $rules[$name] = $this->buildRule(
                    $metadata['type'],
                    $nullable,
                    $name,
                );
                $fields[] = [
                    'name' => $name,
                    'type' => $metadata['type'],
                    'nullable' => $nullable,
                    'db_type' => $metadata['db_type'],
                ];

                continue;
            }

            $fillable[] = $name;
            $fields[] = [
                'name' => $name,
                'type' => $metadata['type'],
                'nullable' => $nullable,
                'db_type' => $metadata['db_type'],
            ];
            $rules[$name] = $this->buildRule(
                $metadata['type'],
                $nullable,
                $name,
            );

            // Determine casts
            $cast = $metadata['cast'];
            if ($cast) {
                $casts[$name] = $cast;
            }
        }

        return [
            'table' => $table,
            'model' => Str::studly(Str::singular($table)),
            'fields' => $fields,
            'fillable' => $fillable,
            'casts' => $casts,
            'rules' => $rules,
            'relationships' => $relationships,
            'hasSoftDeletes' => $hasSoftDeletes,
            'hasTimestamps' => $hasTimestamps,
        ];
    }

    /**
     * Build a validation rule string for a field.
     */
    private function buildRule(
        string $genericType,
        bool $nullable,
        string $name,
    ): string {
        $rules = [];

        if ($nullable) {
            $rules[] = 'nullable';
        } else {
            $rules[] = 'required';
        }

        match ($genericType) {
            'integer' => ($rules[] = 'integer'),
            'number' => ($rules[] = 'numeric'),
            'boolean' => ($rules[] = 'boolean'),
            'date' => ($rules[] = 'date'),
            'datetime' => ($rules[] = 'date'),
            'json' => ($rules[] = 'array'),
            'uuid' => ($rules[] = 'uuid'),
            default => ($rules[] = 'string'),
        };

        // Smart max length
        if ($genericType === 'string') {
            if (str_contains($name, 'email')) {
                $rules[] = 'email';
                $rules[] = 'max:255';
            } elseif (
                str_contains($name, 'url') ||
                str_contains($name, 'link')
            ) {
                $rules[] = 'url';
                $rules[] = 'max:2048';
            } elseif (str_contains($name, 'slug')) {
                $rules[] = 'max:255';
            } else {
                $rules[] = 'max:255';
            }
        }

        if (str_ends_with($name, '_id')) {
            $table = Str::plural(Str::beforeLast($name, '_id'));
            $rules[] = "exists:{$table},id";
        }

        return implode('|', $rules);
    }

    private function resolveDriverName(): string
    {
        return DB::connection()->getDriverName();
    }
}
