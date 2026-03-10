<?php

namespace Arseno25\LaravelApiMagic\Commands;

use Arseno25\LaravelApiMagic\Generators\StubManager;
use Arseno25\LaravelApiMagic\Parsers\DatabaseSchemaParser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\note;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\select;
use function Laravel\Prompts\table;
use function Laravel\Prompts\warning;

final class ReverseEngineerCommand extends Command
{
    protected $signature = 'api-magic:reverse
        {--table= : Specific table name to reverse-engineer}
        {--all : Reverse-engineer all user tables}
        {--exclude= : Comma-separated tables to exclude}
        {--v= : API version (e.g., 1, 2)}
        {--test : Generate Pest tests}
        {--factory : Generate factories}
        {--seeder : Generate seeders}
        {--policy : Generate policies}
        {--force : Overwrite existing files}';

    protected $description = 'Reverse-engineer existing database tables into a full API stack (Model, Controller, FormRequest, Resource, etc.)';

    public function handle(
        DatabaseSchemaParser $dbParser,
        StubManager $stubManager,
    ): int {
        intro('🔮 API Magic — Reverse Engineering');

        $exclude = array_filter(
            explode(',', (string) $this->option('exclude')),
        );

        // Determine which tables to process
        if ($this->option('all')) {
            $tables = $dbParser->getTables($exclude);
        } elseif ($this->option('table')) {
            $tables = [(string) $this->option('table')];
        } else {
            // Interactive table selection
            $availableTables = $dbParser->getTables($exclude);

            if (empty($availableTables)) {
                warning('No user tables found in the database.');

                return self::FAILURE;
            }

            $tables = multiselect(
                label: 'Select tables to reverse-engineer:',
                options: array_combine($availableTables, $availableTables),
                required: true,
            );
        }

        if (empty($tables)) {
            warning('No tables selected.');

            return self::FAILURE;
        }

        info('📊 Found '.count($tables).' table(s) to process.');

        // Get version
        /** @var string|null $version */
        $version = $this->option('v');
        if (
            $version === null &&
            ! $this->input->hasParameterOption('--no-interaction')
        ) {
            $versionChoice = select(
                label: 'API versioning?',
                options: [
                    'none' => 'No versioning (e.g., /api/products)',
                    '1' => 'v1 (e.g., /api/v1/products)',
                    '2' => 'v2 (e.g., /api/v2/products)',
                ],
                default: 'none',
            );
            $version = $versionChoice === 'none' ? null : $versionChoice;
        }

        $generateTest = $this->option('test');
        $generateFactory = $this->option('factory');
        $generateSeeder = $this->option('seeder');
        $generatePolicy = $this->option('policy');
        $force = $this->option('force');

        $generatedCount = 0;

        foreach ($tables as $tableName) {
            $schema = $dbParser->parseTable($tableName);

            if (empty($schema['fields'])) {
                warning(
                    "⏭️  Skipped table '{$tableName}': no usable fields found.",
                );

                continue;
            }

            $model = $schema['model'];

            // Display schema summary
            table(
                ['Column', 'Type', 'Nullable'],
                array_map(
                    fn ($f) => [
                        $f['name'],
                        $f['db_type'],
                        $f['nullable'] ? '✓' : '✗',
                    ],
                    $schema['fields'],
                ),
            );

            if (! empty($schema['relationships'])) {
                info(
                    '  🔗 Relationships: '.
                        implode(
                            ', ',
                            array_map(
                                fn ($r) => $r['type'].' → '.$r['model'],
                                $schema['relationships'],
                            ),
                        ),
                );
            }

            if (
                ! $force &&
                ! $this->input->hasParameterOption('--no-interaction')
            ) {
                if (
                    ! confirm(
                        "Generate API for '{$model}' from table '{$tableName}'?",
                        true,
                    )
                ) {
                    continue;
                }
            }

            $this->generateFromSchema(
                $schema,
                $version,
                $generateTest,
                $generateFactory,
                $generateSeeder,
                $generatePolicy,
                $force,
                $stubManager,
            );
            $generatedCount++;
        }

        if ($generatedCount > 0) {
            outro(
                "✨ Reverse engineered {$generatedCount} table(s) successfully!",
            );

            $routeLines = [];
            foreach ($tables as $tableName) {
                $model = Str::studly(Str::singular($tableName));
                $routePrefix = $version !== null ? "v{$version}/" : '';
                $routeLines[] = "Route::apiResource('{$routePrefix}{$tableName}', {$model}Controller::class);";
            }

            note(
                "Add to routes/api.php:\n".implode("\n", $routeLines),
                '📌 Next Steps',
            );
        }

        return self::SUCCESS;
    }

    /**
     * Generate all files from a parsed database schema.
     *
     * @param  array<string, mixed>  $schema
     */
    private function generateFromSchema(
        array $schema,
        ?string $version,
        bool $generateTest,
        bool $generateFactory,
        bool $generateSeeder,
        bool $generatePolicy,
        bool $force,
        StubManager $stubManager,
    ): void {
        $model = $schema['model'];
        $table = $schema['table'];

        // Build field strings for stubs
        $migrationFields = $this->buildMigrationFields(
            $schema['fields'],
            $schema['hasSoftDeletes'],
        );
        $fillableStr = $this->buildFillable($schema['fillable']);
        $rulesStr = $this->buildRules($schema['rules']);
        $resourceProps = $this->buildResourceProperties($schema['fillable']);
        $relationsStr = $this->buildRelations($schema['relationships']);
        $foreignKeys = $this->buildForeignKeys($schema['relationships']);
        $factoryDefs = $this->buildFactoryDefinitions($schema['fields']);
        $searchConditions = $this->buildSearchConditions($schema['fillable']);
        $relationImports = $this->buildRelationImports(
            $schema['relationships'],
        );

        $controllerNamespace = $this->buildNamespace(
            'App\\Http\\Controllers\\Api',
            $version,
        );
        $resourceNamespace = $this->buildNamespace(
            'App\\Http\\Resources',
            $version,
        );
        $controllerDir = $this->buildPath('Http/Controllers/Api', $version);
        $resourceDir = $this->buildPath('Http/Resources', $version);
        $routePrefix = $version !== null ? "v{$version}/" : '';
        $apiPrefix = "/api/{$routePrefix}".Str::kebab(Str::plural($model));

        $replacements = [
            '{{ namespace }}' => 'App',
            '{{ controllerNamespace }}' => $controllerNamespace,
            '{{ resourceNamespace }}' => $resourceNamespace,
            '{{ factoryNamespace }}' => 'Database\\Factories',
            '{{ seederNamespace }}' => 'Database\\Seeders',
            '{{ model }}' => $model,
            '{{ modelVariable }}' => Str::camel($model),
            '{{ modelPlural }}' => Str::plural($model),
            '{{ modelPluralVariable }}' => Str::plural(Str::camel($model)),
            '{{ table }}' => $table,
            '{{ fields }}' => $migrationFields,
            '{{ fillable }}' => $fillableStr,
            '{{ casts }}' => $this->buildCasts($schema['casts']),
            '{{ rules }}' => $rulesStr,
            '{{ resourceProperties }}' => $resourceProps,
            '{{ relations }}' => $relationsStr,
            '{{ relationImports }}' => $relationImports,
            '{{ foreignKeys }}' => $foreignKeys,
            '{{ factoryDefinitions }}' => $factoryDefs,
            '{{ searchConditions }}' => $searchConditions,
            '{{ apiResourceUrl }}' => Str::kebab(Str::plural($model)),
            '{{ apiPrefix }}' => $apiPrefix,
            '{{ apiVersion }}' => $version ?? '',
            '{{ softDeletes }}' => $schema['hasSoftDeletes']
                ? '$table->softDeletes();'
                : '',
            '{{ softDeletesTrait }}' => $schema['hasSoftDeletes']
                ? '    use SoftDeletes;'
                : '',
            '{{ searchablefields }}' => ! empty($schema['fillable']),
            '{{ seederCount }}' => (string) config(
                'api-magic.generator.seeder_count',
                10,
            ),
        ];

        // Note: We skip migration since the table already exists
        $files = [
            [
                'stub' => 'model.stub',
                'destination' => app_path("Models/{$model}.php"),
                'replacements' => $replacements,
            ],
            [
                'stub' => 'controller.api.stub',
                'destination' => app_path(
                    "{$controllerDir}/{$model}Controller.php",
                ),
                'replacements' => $replacements,
            ],
            [
                'stub' => 'request.stub',
                'destination' => app_path(
                    "Http/Requests/Store{$model}Request.php",
                ),
                'replacements' => array_merge($replacements, [
                    '{{ requestClass }}' => "Store{$model}Request",
                ]),
            ],
            [
                'stub' => 'request.stub',
                'destination' => app_path(
                    "Http/Requests/Update{$model}Request.php",
                ),
                'replacements' => array_merge($replacements, [
                    '{{ requestClass }}' => "Update{$model}Request",
                ]),
            ],
            [
                'stub' => 'resource.stub',
                'destination' => app_path(
                    "{$resourceDir}/{$model}Resource.php",
                ),
                'replacements' => $replacements,
            ],
            [
                'stub' => 'collection.stub',
                'destination' => app_path(
                    "{$resourceDir}/{$model}Collection.php",
                ),
                'replacements' => $replacements,
            ],
        ];

        if ($generateTest) {
            $testDir = $this->buildPath('tests/Feature/Api', $version);
            $files[] = [
                'stub' => 'pest.test.stub',
                'destination' => base_path("{$testDir}/{$model}Test.php"),
                'replacements' => $replacements,
            ];
        }

        if ($generateFactory) {
            $files[] = [
                'stub' => 'factory.stub',
                'destination' => database_path("factories/{$model}Factory.php"),
                'replacements' => $replacements,
            ];
        }

        if ($generateSeeder) {
            $files[] = [
                'stub' => 'seeder.stub',
                'destination' => database_path("seeders/{$model}Seeder.php"),
                'replacements' => $replacements,
            ];
        }

        if ($generatePolicy) {
            $files[] = [
                'stub' => 'policy.stub',
                'destination' => app_path("Policies/{$model}Policy.php"),
                'replacements' => $replacements,
            ];
        }

        foreach ($files as $file) {
            $directory = dirname($file['destination']);
            if (! File::isDirectory($directory)) {
                File::makeDirectory($directory, 0755, true);
            }

            if (File::exists($file['destination']) && ! $force) {
                $this->line(
                    "  <fg=yellow>⊝ Skipped:</> {$file['destination']}",
                );

                continue;
            }

            $stubManager->generate(
                $file['stub'],
                $file['replacements'],
                $file['destination'],
            );
            $this->line("  <fg=green>✓ Created:</> {$file['destination']}");
        }
    }

    /**
     * Build migration field definitions.
     *
     * @param  array<int, array<string, mixed>>  $fields
     */
    private function buildMigrationFields(
        array $fields,
        bool $hasSoftDeletes,
    ): string {
        $lines = [];
        foreach ($fields as $field) {
            $name = $field['name'];
            $dbType = strtolower($field['db_type']);
            $nullable = $field['nullable'] ? '->nullable()' : '';

            $method = match (true) {
                in_array($dbType, ['int', 'integer']) => 'integer',
                in_array($dbType, ['bigint']) => 'bigInteger',
                in_array($dbType, ['smallint']) => 'smallInteger',
                in_array($dbType, ['tinyint']) => 'tinyInteger',
                in_array($dbType, ['float', 'double']) => 'double',
                in_array($dbType, ['decimal', 'numeric']) => 'decimal',
                in_array($dbType, ['bool', 'boolean']) => 'boolean',
                in_array($dbType, ['date']) => 'date',
                in_array($dbType, ['datetime', 'timestamp']) => 'dateTime',
                in_array($dbType, ['time']) => 'time',
                in_array($dbType, ['text', 'mediumtext', 'longtext']) => 'text',
                in_array($dbType, ['json', 'jsonb']) => 'json',
                in_array($dbType, ['enum']) => 'string',
                str_ends_with($name, '_id') => 'foreignId',
                default => 'string',
            };

            if ($method === 'foreignId') {
                $lines[] = "\$table->foreignId('{$name}'){$nullable}->constrained()->cascadeOnDelete();";
            } else {
                $lines[] = "\$table->{$method}('{$name}'){$nullable};";
            }
        }

        return implode("\n            ", $lines);
    }

    private function buildFillable(array $fillable): string
    {
        return implode(",\n        ", array_map(fn ($f) => "'{$f}'", $fillable));
    }

    /**
     * @param  array<string, string>  $rules
     */
    private function buildRules(array $rules): string
    {
        $lines = [];
        foreach ($rules as $field => $rule) {
            $lines[] = "'{$field}' => '{$rule}',";
        }

        return implode("\n            ", $lines);
    }

    private function buildResourceProperties(array $fillable): string
    {
        $lines = [];
        foreach ($fillable as $f) {
            $lines[] = "'{$f}' => \$this->{$f},";
        }

        return implode("\n            ", $lines);
    }

    /**
     * @param  array<string, string>  $casts
     */
    private function buildCasts(array $casts): string
    {
        if (empty($casts)) {
            return '';
        }

        $lines = [];
        foreach ($casts as $field => $cast) {
            $lines[] = "            '{$field}' => '{$cast}',";
        }

        return implode("\n", $lines)."\n";
    }

    /**
     * @param  array<int, array<string, mixed>>  $relationships
     */
    private function buildRelations(array $relationships): string
    {
        $lines = [];
        foreach ($relationships as $rel) {
            $method = Str::camel($rel['model']);
            $relatedClass = $rel['model'];
            $lines[] = "public function {$method}(): \\Illuminate\\Database\\Eloquent\\Relations\\BelongsTo\n    {\n        return \$this->belongsTo({$relatedClass}::class);\n    }";
        }

        return implode("\n\n    ", $lines);
    }

    /**
     * @param  array<int, array<string, mixed>>  $relationships
     */
    private function buildForeignKeys(array $relationships): string
    {
        $lines = [];
        foreach ($relationships as $rel) {
            $lines[] = "\$table->foreignId('{$rel['foreignKey']}')->constrained()->cascadeOnDelete();";
        }

        return implode("\n            ", $lines);
    }

    /**
     * @param  array<int, array<string, mixed>>  $fields
     */
    private function buildFactoryDefinitions(array $fields): string
    {
        $lines = [];
        foreach ($fields as $field) {
            $name = $field['name'];
            $type = $field['type'];

            if (str_ends_with($name, '_id')) {
                $relatedModel = Str::studly(Str::beforeLast($name, '_id'));
                $lines[] = "'{$name}' => \\App\\Models\\{$relatedModel}::factory(),";

                continue;
            }

            $faker = match ($type) {
                'integer' => '$this->faker->randomNumber()',
                'number' => '$this->faker->randomFloat(2, 0, 1000)',
                'boolean' => '$this->faker->boolean',
                'date', 'datetime' => '$this->faker->dateTime',
                'text' => '$this->faker->paragraph',
                default => str_contains($name, 'email')
                    ? '$this->faker->safeEmail'
                    : (str_contains($name, 'name')
                        ? '$this->faker->name'
                        : (str_contains($name, 'url')
                            ? '$this->faker->url'
                            : (str_contains($name, 'phone')
                                ? '$this->faker->phoneNumber'
                                : (str_contains($name, 'price') ||
                                str_contains($name, 'amount')
                                    ? '$this->faker->randomFloat(2, 10, 999)'
                                    : '$this->faker->word')))),
            };

            $lines[] = "'{$name}' => {$faker},";
        }

        return implode("\n            ", $lines);
    }

    private function buildSearchConditions(array $fillable): string
    {
        if (empty($fillable)) {
            return '';
        }

        $conditions = array_map(
            fn ($f) => "->orWhere('{$f}', 'like', \"%\$search%\")",
            $fillable,
        );

        return "->where(function (\$q) use (\$search) {\n                \$q".
            implode("\n                  ", $conditions).
            ";\n            })";
    }

    /**
     * @param  array<int, array<string, mixed>>  $relationships
     */
    private function buildRelationImports(array $relationships): string
    {
        if (empty($relationships)) {
            return '';
        }

        $imports = [];
        foreach ($relationships as $rel) {
            $imports[] = "use App\\Models\\{$rel['model']};";
        }

        return implode("\n", array_unique($imports));
    }

    private function buildNamespace(string $base, ?string $version): string
    {
        return $version !== null ? "{$base}\\V{$version}" : $base;
    }

    private function buildPath(string $base, ?string $version): string
    {
        return $version !== null ? "{$base}/V{$version}" : $base;
    }
}
