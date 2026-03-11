<?php

namespace Arseno25\LaravelApiMagic\Commands;

use Arseno25\LaravelApiMagic\Generators\StubManager;
use Arseno25\LaravelApiMagic\Parsers\SchemaParser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\note;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\select;
use function Laravel\Prompts\table;
use function Laravel\Prompts\text;
use function Laravel\Prompts\warning;

final class GenerateApiCommand extends Command
{
    protected $signature = 'api:magic
        {model? : The name of the Model}
        {schema? : Field schema (e.g., "title:string|required,price:integer|min:0"). Leave empty to generate with placeholder fields.}
        {--test : Generate a Pest feature test}
        {--factory : Generate a Factory}
        {--seeder : Generate a Seeder}
        {--policy : Generate a Policy}
        {--soft-deletes : Add soft deletes to model and migration}
        {--belongsTo= : BelongsTo relations (e.g., "category,user")}
        {--hasMany= : HasMany relations (e.g., "comments,review")}
        {--belongsToMany= : BelongsToMany relations (e.g., "tag,category")}
        {--v= : API version number (e.g., 1, 2). Omit for no versioning.}
        {--force : Overwrite existing files}';

    protected $description = 'Generate a complete API with Model, Migration, Controller, Request, Resource, and optional Pest Test';

    public function handle(SchemaParser $parser, StubManager $stubManager): int
    {
        $noInteraction = $this->input->hasParameterOption('--no-interaction');

        // Display Header
        if (! $noInteraction) {
            intro('✨ API Magic - Interactive Setup');
        }

        // Get model name (interactive if not provided)
        $model = $this->argument('model');
        if (empty($model)) {
            if ($noInteraction) {
                error('Model name is required in non-interactive mode.');

                return self::FAILURE;
            }

            $model = text(
                label: 'What is the Model name?',
                placeholder: 'e.g., Post, Product, User',
                required: true,
            );
        }

        $model = Str::singular(Str::studly($model));
        $table = Str::snake(Str::pluralStudly($model));

        // Get schema input
        $schemaInput = $this->argument('schema');
        if (empty($schemaInput)) {
            if ($noInteraction) {
                warning(
                    'Running in non-interactive mode. Generating with placeholder fields...',
                );
                $schemaInput = '';
            } elseif (
                confirm(
                    label: 'Would you like to define fields interactively?',
                    default: true,
                )
            ) {
                $schemaInput = $this->collectFieldsInteractively();
            } else {
                warning(
                    'No schema provided. Generating with placeholder fields...',
                );
                $schemaInput = '';
            }
        }

        // Get relations
        $belongsTo = $this->stringOption('belongsTo');
        $hasMany = $this->stringOption('hasMany');
        $belongsToMany = $this->stringOption('belongsToMany');

        if (
            empty($belongsTo) &&
            empty($hasMany) &&
            empty($belongsToMany) &&
            ! $noInteraction
        ) {
            [
                $belongsTo,
                $hasMany,
                $belongsToMany,
            ] = $this->collectRelationsInteractively();
        } else {
            $belongsTo = $this->parseRelations($belongsTo);
            $hasMany = $this->parseRelations($hasMany);
            $belongsToMany = $this->parseRelations($belongsToMany);
        }

        // Get API version (null = no versioning)
        $version = $this->stringOption('v');
        if (! $noInteraction && $version === null) {
            $versionChoice = select(
                label: 'API versioning?',
                options: [
                    'none' => 'No versioning (e.g., /api/products)',
                    '1' => 'v1 (e.g., /api/v1/products)',
                    '2' => 'v2 (e.g., /api/v2/products)',
                    '3' => 'v3 (e.g., /api/v3/products)',
                ],
                default: 'none',
            );
            $version =
                $versionChoice === 'none' ? null : (string) $versionChoice;
        }

        // Get test option
        $generateTest = $this->booleanOption('test');
        if (! $noInteraction && ! $generateTest) {
            $generateTest = confirm(
                label: 'Generate Pest feature test?',
                default: false,
            );
        }

        $generateFactory = $this->booleanOption('factory');
        $generateSeeder = $this->booleanOption('seeder');
        $generatePolicy = $this->booleanOption('policy');
        $useSoftDeletes = $this->booleanOption('soft-deletes');

        // Show summary and confirm
        if (! $noInteraction) {
            $this->displaySummary([
                'model' => $model,
                'fields' => $schemaInput,
                'relations' => [
                    'belongsTo' => $belongsTo,
                    'hasMany' => $hasMany,
                    'belongsToMany' => $belongsToMany,
                ],
                'version' => $version,
                'test' => $generateTest,
                'factory' => $generateFactory,
                'seeder' => $generateSeeder,
                'policy' => $generatePolicy,
                'softDeletes' => $useSoftDeletes,
            ]);

            if (! confirm(label: 'Proceed to generate API?', default: true)) {
                warning('Generation cancelled.');

                return self::SUCCESS;
            }
        }

        // Check force option
        $force = $this->booleanOption('force');
        if (! $noInteraction && ! $force) {
            $existingFiles = $this->checkExistingFiles(
                $model,
                $version,
                $generateTest,
            );
            if (! empty($existingFiles)) {
                warning('The following files already exist:');
                foreach ($existingFiles as $file) {
                    $this->line("  <fg=red>✗</> {$file}");
                }

                $force = confirm(
                    label: 'Overwrite existing files?',
                    default: false,
                );
            }
        }

        // Parse schema and generate files
        $fields = $parser->parse(
            $schemaInput,
            $belongsTo,
            $hasMany,
            $belongsToMany,
        );

        info("⚙️  Generating API for {$model}...");

        // Build namespaces and paths based on version
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

        // Build route prefix: no version → "" | with version → "v1/"
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
            '{{ fields }}' => $fields['migration'],
            '{{ fillable }}' => $fields['fillable'],
            '{{ casts }}' => $fields['casts'],
            '{{ rules }}' => $fields['rules'],
            '{{ resourceProperties }}' => $fields['resourceProperties'],
            '{{ relations }}' => $fields['relations'],
            '{{ relationImports }}' => $this->buildRelationImports(
                $belongsTo,
                $hasMany,
                $belongsToMany,
            ),
            '{{ foreignKeys }}' => $fields['foreignKeys'],
            '{{ factoryDefinitions }}' => $fields['factoryDefinitions'],
            '{{ searchConditions }}' => $this->buildSearchConditions(
                $fields['searchableFields'],
            ),
            '{{ apiResourceUrl }}' => Str::kebab(Str::plural($model)),
            '{{ apiPrefix }}' => $apiPrefix,
            '{{ apiVersion }}' => $version ?? '',
            '{{ softDeletes }}' => $useSoftDeletes
                ? '$table->softDeletes();'
                : '',
            '{{ softDeletesTrait }}' => $useSoftDeletes
                ? '    use SoftDeletes;'
                : '',
            '{{ searchablefields }}' => ! empty($fields['searchableFields']),
            '{{ seederCount }}' => (string) config(
                'api-magic.generator.seeder_count',
                10,
            ),
        ];

        $files = [
            [
                'stub' => 'model.stub',
                'destination' => app_path("Models/{$model}.php"),
                'replacements' => $replacements,
            ],
            [
                'stub' => 'migration.stub',
                'destination' => database_path(
                    'migrations/'.
                        date('Y_m_d_His').
                        "_create_{$table}_table.php",
                ),
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

        outro('✨ API Generated Successfully!');

        $routeResource = $routePrefix.$table;
        $versionLabel = $version !== null ? " (v{$version})" : '';
        $nextSteps = "1. Run: php artisan migrate\n";
        $nextSteps .= "2. Add to routes/api.php:\n";
        $nextSteps .= "   Route::apiResource('{$routeResource}', {$model}Controller::class);";
        if ($generatePolicy) {
            $nextSteps .=
                "\n3. Register or rely on Laravel policy discovery if you want authorization enforced.";
        }

        note($nextSteps, "📌 Next steps{$versionLabel}");

        return self::SUCCESS;
    }

    /**
     * Build a namespace with optional version suffix.
     */
    private function buildNamespace(string $base, ?string $version): string
    {
        return $version !== null ? "{$base}\\V{$version}" : $base;
    }

    /**
     * Build a directory path with optional version suffix.
     */
    private function buildPath(string $base, ?string $version): string
    {
        return $version !== null ? "{$base}/V{$version}" : $base;
    }

    /**
     * @return list<string>
     */
    private function parseRelations(?string $relations): array
    {
        if (empty($relations)) {
            return [];
        }

        return array_map(
            fn ($r) => Str::studly(trim($r)),
            explode(',', $relations),
        );
    }

    /**
     * @param  list<string>  $belongsTo
     * @param  list<string>  $hasMany
     * @param  list<string>  $belongsToMany
     */
    private function buildRelationImports(
        array $belongsTo,
        array $hasMany,
        array $belongsToMany,
    ): string {
        $imports = [];

        if (! empty($belongsTo)) {
            $imports[] =
                "use Illuminate\Database\Eloquent\Relations\BelongsTo;";
        }
        if (! empty($hasMany)) {
            $imports[] = "use Illuminate\Database\Eloquent\Relations\HasMany;";
        }
        if (! empty($belongsToMany)) {
            $imports[] =
                "use Illuminate\Database\Eloquent\Relations\BelongsToMany;";
        }

        return empty($imports) ? '' : implode("\n", $imports);
    }

    /**
     * @param  array{
     *     model: string,
     *     fields: string,
     *     relations: array{belongsTo: list<string>, hasMany: list<string>, belongsToMany: list<string>},
     *     version: string|null,
     *     test: bool,
     *     factory: bool,
     *     seeder: bool,
     *     policy: bool,
     *     softDeletes: bool
     * }  $data
     */
    private function displaySummary(array $data): void
    {
        $fields = $this->parseFieldsFromSchema($data['fields']);
        $fieldCount = count($fields);

        $fieldNames = ! empty($fields)
            ? implode(', ', array_keys($fields))
            : 'None';
        if (strlen($fieldNames) > 40) {
            $fieldNames = substr($fieldNames, 0, 40).'...';
        }

        $fieldsText =
            $fieldCount > 0
                ? "{$fieldCount} field(s) <fg=gray>({$fieldNames})</>"
                : 'None';

        $relations = [];
        if (! empty($data['relations']['belongsTo'])) {
            $relations[] =
                count($data['relations']['belongsTo']).' BelongsTo';
        }
        if (! empty($data['relations']['hasMany'])) {
            $relations[] = count($data['relations']['hasMany']).' HasMany';
        }
        if (! empty($data['relations']['belongsToMany'])) {
            $relations[] =
                count($data['relations']['belongsToMany']).' BelongsToMany';
        }
        $relationsText = ! empty($relations)
            ? implode(' | ', $relations)
            : 'None';

        $versionText =
            $data['version'] !== null
                ? "API v{$data['version']}"
                : 'No versioning';

        info('📊 Configuration Summary');

        table(
            headers: ['Component', 'Details'],
            rows: [
                ['📦 Model', $data['model']],
                ['📝 Fields', $fieldsText],
                ['🔗 Relations', $relationsText],
                ['🔢 Version', $versionText],
                ['🧪 Pest Test', $data['test'] ? '✓ Enabled' : '✗ Disabled'],
                ['🏭 Factory', $data['factory'] ? '✓ Enabled' : '✗ Disabled'],
                ['🌱 Seeder', $data['seeder'] ? '✓ Enabled' : '✗ Disabled'],
                ['🛡️ Policy', $data['policy'] ? '✓ Enabled' : '✗ Disabled'],
                [
                    '🗑️  Soft Deletes',
                    $data['softDeletes'] ? '✓ Enabled' : '✗ Disabled',
                ],
            ],
        );
    }

    /**
     * @return array<string, bool>
     */
    private function parseFieldsFromSchema(string $schema): array
    {
        if (empty($schema)) {
            return [];
        }

        $fields = [];
        $items = explode(',', $schema);

        foreach ($items as $item) {
            $parts = explode(':', trim($item), 2);
            if (! empty($parts[0])) {
                $fields[trim($parts[0])] = true;
            }
        }

        return $fields;
    }

    /**
     * @return list<string>
     */
    private function checkExistingFiles(
        string $model,
        ?string $version,
        bool $generateTest,
    ): array {
        $existing = [];
        $controllerDir = $this->buildPath('Http/Controllers/Api', $version);
        $resourceDir = $this->buildPath('Http/Resources', $version);

        $files = [
            app_path("Models/{$model}.php"),
            app_path("{$controllerDir}/{$model}Controller.php"),
            app_path("Http/Requests/Store{$model}Request.php"),
            app_path("Http/Requests/Update{$model}Request.php"),
            app_path("{$resourceDir}/{$model}Resource.php"),
        ];

        if ($this->option('policy')) {
            $files[] = app_path("Policies/{$model}Policy.php");
        }

        if ($generateTest) {
            $testDir = $this->buildPath('tests/Feature/Api', $version);
            $files[] = base_path("{$testDir}/{$model}Test.php");
        }

        foreach ($files as $file) {
            if (File::exists($file)) {
                $existing[] = $file;
            }
        }

        return $existing;
    }

    private function collectFieldsInteractively(): string
    {
        info('🔹 Define Fields (Leave field name empty to finish)');

        $fields = [];
        $fieldTypes = [
            'string',
            'text',
            'integer',
            'decimal',
            'boolean',
            'datetime',
            'date',
        ];

        while (true) {
            $fieldName = text(
                label: 'Field name:',
                placeholder: 'e.g., title, description, price (Press Enter to finish)',
            );

            if (empty($fieldName)) {
                break;
            }

            $fieldType = select(
                label: "Select type for {$fieldName}:",
                options: $fieldTypes,
                default: 'string',
            );

            $isRequired = confirm(
                label: "Is {$fieldName} required?",
                default: true,
            );

            $additionalRules = text(
                label: "Additional validation rules for {$fieldName}? (optional)",
                placeholder: 'e.g. min:5|max:255',
            );

            $rules = [];
            if ($isRequired) {
                $rules[] = 'required';
            }
            if (! empty($additionalRules)) {
                $rules[] = $additionalRules;
            }

            $ruleString = implode('|', $rules);
            $fields[] = "{$fieldName}:{$fieldType}:{$ruleString}";

            $parts = [$fieldType, $isRequired ? 'required' : 'optional'];
            if (! empty($additionalRules)) {
                $parts[] = $additionalRules;
            }

            $this->line(
                "  <fg=green>✓</> Added: <fg=white>{$fieldName}</> <fg=gray>(".
                    implode(', ', $parts).
                    ")</>\n",
            );
        }

        return implode(',', $fields);
    }

    /**
     * @return array{0: list<string>, 1: list<string>, 2: list<string>}
     */
    private function collectRelationsInteractively(): array
    {
        $belongsTo = [];
        $hasMany = [];
        $belongsToMany = [];

        if (! confirm(label: 'Define relationships?', default: false)) {
            return [$belongsTo, $hasMany, $belongsToMany];
        }

        while (confirm(label: 'Add belongsTo relationship?', default: false)) {
            $relatedModel = text(
                label: 'Related model name:',
                placeholder: 'e.g., Category, User',
                required: true,
            );

            $relatedModel = Str::studly(trim($relatedModel));
            $belongsTo[] = $relatedModel;
            $this->line(
                "  <fg=green>✓</> Added belongsTo: <fg=white>{$relatedModel}</>\n",
            );
        }

        while (confirm(label: 'Add hasMany relationship?', default: false)) {
            $relatedModel = text(
                label: 'Related model name:',
                placeholder: 'e.g., Comment, Review',
                required: true,
            );

            $relatedModel = Str::studly(trim($relatedModel));
            $hasMany[] = $relatedModel;
            $this->line(
                "  <fg=green>✓</> Added hasMany: <fg=white>{$relatedModel}</>\n",
            );
        }

        while (
            confirm(label: 'Add belongsToMany relationship?', default: false)
        ) {
            $relatedModel = text(
                label: 'Related model name:',
                placeholder: 'e.g., Tag, Category',
                required: true,
            );

            $relatedModel = Str::studly(trim($relatedModel));
            $belongsToMany[] = $relatedModel;
            $this->line(
                "  <fg=green>✓</> Added belongsToMany: <fg=white>{$relatedModel}</>\n",
            );
        }

        return [$belongsTo, $hasMany, $belongsToMany];
    }

    /**
     * @param  list<string>  $searchableFields
     */
    private function buildSearchConditions(array $searchableFields): string
    {
        if (empty($searchableFields)) {
            return '';
        }

        $conditions = [];

        foreach ($searchableFields as $i => $field) {
            if ($i === 0) {
                $conditions[] = "                \$q->where('{$field}', 'like', \"%{\$searchTerm}%\")";
            } else {
                $conditions[] = "                    ->orWhere('{$field}', 'like', \"%{\$searchTerm}%\")";
            }
        }

        // Add semicolon to the last line
        $lastIndex = count($conditions) - 1;
        $conditions[$lastIndex] .= ';';

        return implode("\n", $conditions);
    }

    private function booleanOption(string $name): bool
    {
        return (bool) $this->option($name);
    }

    private function stringOption(string $name): ?string
    {
        $value = $this->option($name);

        if (! is_string($value) && ! is_int($value)) {
            return null;
        }

        $trimmedValue = trim((string) $value);

        return $trimmedValue !== '' ? $trimmedValue : null;
    }
}
