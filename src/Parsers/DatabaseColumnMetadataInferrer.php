<?php

namespace Arseno25\LaravelApiMagic\Parsers;

use Illuminate\Support\Str;

final class DatabaseColumnMetadataInferrer
{
    /**
     * @param  array<string, mixed>  $column
     * @return array{name: string, db_type: string, raw_type: string, type: string, nullable: bool, cast: string|null}
     */
    public function infer(array $column, ?string $driver = null): array
    {
        $name = (string) ($column['name'] ?? '');
        $dbType = strtolower(
            (string) ($column['type_name'] ?? ($column['type'] ?? 'varchar')),
        );
        $rawType = strtolower((string) ($column['type'] ?? $dbType));
        $driver = strtolower((string) ($driver ?? 'unknown'));
        $genericType = $this->mapColumnType($dbType, $rawType, $driver);

        return [
            'name' => $name,
            'db_type' => $dbType,
            'raw_type' => $rawType,
            'type' => $genericType,
            'nullable' => (bool) ($column['nullable'] ?? false),
            'cast' => $this->getCast($genericType, $column),
        ];
    }

    public function normalizeTableName(string $table): string
    {
        $sanitized = str_replace(['"', '`', '[', ']'], '', $table);

        return Str::afterLast($sanitized, '.');
    }

    private function mapColumnType(
        string $dbType,
        string $rawType,
        string $driver,
    ): string {
        if (
            $dbType === 'uuid' ||
            str_contains($rawType, 'uuid') ||
            str_contains($rawType, 'uniqueidentifier')
        ) {
            return 'uuid';
        }

        if (
            in_array($dbType, ['json', 'jsonb'], true) ||
            str_contains($rawType, 'json')
        ) {
            return 'json';
        }

        if (
            in_array($dbType, ['bool', 'boolean', 'bit'], true) ||
            ($dbType === 'tinyint' && str_contains($rawType, 'tinyint(1)')) ||
            ($driver === 'pgsql' && str_contains($rawType, 'boolean'))
        ) {
            return 'boolean';
        }

        if (
            in_array(
                $dbType,
                ['timestamp', 'timestamptz', 'datetime', 'datetime2'],
                true,
            ) ||
            str_contains($rawType, 'timestamp with time zone') ||
            str_contains($rawType, 'timestamp without time zone')
        ) {
            return 'datetime';
        }

        if ($dbType === 'date') {
            return 'date';
        }

        if (
            in_array($dbType, ['time', 'timetz'], true) ||
            str_contains($rawType, 'time without time zone')
        ) {
            return 'time';
        }

        if (
            in_array($dbType, ['float', 'double', 'real', 'money'], true) ||
            str_contains($rawType, 'double precision')
        ) {
            return 'float';
        }

        if (in_array($dbType, ['decimal', 'numeric'], true)) {
            return 'decimal';
        }

        if (
            in_array(
                $dbType,
                [
                    'int',
                    'integer',
                    'bigint',
                    'smallint',
                    'tinyint',
                    'mediumint',
                    'serial',
                    'bigserial',
                ],
                true,
            )
        ) {
            return 'integer';
        }

        if (
            in_array(
                $dbType,
                ['text', 'mediumtext', 'longtext', 'tinytext'],
                true,
            ) ||
            str_contains($rawType, 'text') ||
            str_contains($rawType, 'clob')
        ) {
            return 'text';
        }

        return 'string';
    }

    /**
     * @param  array<string, mixed>  $column
     */
    private function getCast(string $genericType, array $column): ?string
    {
        return match ($genericType) {
            'boolean' => 'boolean',
            'integer' => 'integer',
            'float' => 'float',
            'decimal' => 'decimal:'.$this->resolveScale($column),
            'date' => 'date',
            'datetime' => 'datetime',
            'json' => 'array',
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $column
     */
    private function resolveScale(array $column): int
    {
        $scale = $column['scale'] ?? ($column['numeric_scale'] ?? null);

        return is_numeric($scale) ? max(0, (int) $scale) : 2;
    }
}
