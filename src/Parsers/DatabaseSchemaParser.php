<?php

namespace Arseno25\LaravelApiMagic\Parsers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

final class DatabaseSchemaParser
{
    /**
     * Get all user tables (excluding system tables).
     *
     * @return array<int, string>
     */
    public function getTables(array $exclude = []): array
    {
        $defaultExclude = [
            'migrations', 'password_resets', 'password_reset_tokens',
            'failed_jobs', 'personal_access_tokens', 'jobs', 'job_batches',
            'sessions', 'cache', 'cache_locks', 'telescope_entries',
            'telescope_entries_tags', 'telescope_monitoring',
        ];

        $exclude = array_merge($defaultExclude, $exclude);

        $tables = Schema::getTableListing();

        return array_values(array_filter($tables, fn ($table) => ! in_array($table, $exclude)));
    }

    /**
     * Parse a database table into a schema definition.
     *
     * @return array<string, mixed>
     */
    public function parseTable(string $table): array
    {
        $columns = Schema::getColumns($table);

        $fields = [];
        $relationships = [];
        $fillable = [];
        $casts = [];
        $rules = [];
        $hasSoftDeletes = false;
        $hasTimestamps = false;

        foreach ($columns as $column) {
            $name = $column['name'];
            $type = $column['type_name'] ?? $column['type'] ?? 'varchar';
            $nullable = $column['nullable'] ?? false;

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
                $rules[$name] = $this->buildRule($type, $nullable, $name);
                $fields[] = [
                    'name' => $name,
                    'type' => $this->mapColumnType($type),
                    'nullable' => $nullable,
                    'db_type' => $type,
                ];

                continue;
            }

            $fillable[] = $name;
            $fields[] = [
                'name' => $name,
                'type' => $this->mapColumnType($type),
                'nullable' => $nullable,
                'db_type' => $type,
            ];
            $rules[$name] = $this->buildRule($type, $nullable, $name);

            // Determine casts
            $cast = $this->getCast($type, $name);
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
     * Map database column type to a generic type.
     */
    private function mapColumnType(string $type): string
    {
        $type = strtolower($type);

        return match (true) {
            in_array($type, ['int', 'integer', 'bigint', 'smallint', 'tinyint', 'mediumint']) => 'integer',
            in_array($type, ['float', 'double', 'decimal', 'numeric', 'real']) => 'number',
            in_array($type, ['bool', 'boolean']) => 'boolean',
            in_array($type, ['date']) => 'date',
            in_array($type, ['datetime', 'timestamp']) => 'datetime',
            in_array($type, ['time']) => 'time',
            in_array($type, ['json', 'jsonb']) => 'json',
            in_array($type, ['text', 'mediumtext', 'longtext']) => 'text',
            $type === 'enum' => 'string',
            default => 'string',
        };
    }

    /**
     * Build a validation rule string for a field.
     */
    private function buildRule(string $type, bool $nullable, string $name): string
    {
        $rules = [];

        if ($nullable) {
            $rules[] = 'nullable';
        } else {
            $rules[] = 'required';
        }

        $genericType = $this->mapColumnType($type);

        match ($genericType) {
            'integer' => $rules[] = 'integer',
            'number' => $rules[] = 'numeric',
            'boolean' => $rules[] = 'boolean',
            'date' => $rules[] = 'date',
            'datetime' => $rules[] = 'date',
            'json' => $rules[] = 'array',
            default => $rules[] = 'string',
        };

        // Smart max length
        if ($genericType === 'string') {
            if (str_contains($name, 'email')) {
                $rules[] = 'email';
                $rules[] = 'max:255';
            } elseif (str_contains($name, 'url') || str_contains($name, 'link')) {
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

    /**
     * Get the Eloquent cast for a column type.
     */
    private function getCast(string $type, string $name): ?string
    {
        $genericType = $this->mapColumnType($type);

        return match ($genericType) {
            'boolean' => 'boolean',
            'integer' => 'integer',
            'number' => 'decimal:2',
            'date' => 'date',
            'datetime' => 'datetime',
            'json' => 'array',
            default => null,
        };
    }
}
