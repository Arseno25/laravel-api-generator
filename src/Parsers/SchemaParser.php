<?php

namespace Arseno25\LaravelApiMagic\Parsers;

use Illuminate\Support\Str;

final class SchemaParser
{
    private array $typeMap = [
        'string' => 'string',
        'text' => 'text',
        'integer' => 'integer',
        'int' => 'integer',
        'bigint' => 'bigInteger',
        'decimal' => 'decimal',
        'float' => 'float',
        'double' => 'double',
        'boolean' => 'boolean',
        'bool' => 'boolean',
        'date' => 'date',
        'datetime' => 'dateTime',
        'timestamp' => 'timestamp',
        'json' => 'json',
        'uuid' => 'uuid',
    ];

    private array $nullableTypes = [
        'text', 'dateTime', 'timestamp', 'json', 'uuid',
    ];

    public function parse(string $schema, array $belongsTo = [], array $hasMany = [], array $belongsToMany = []): array
    {
        $fields = $this->extractFields($schema);

        return [
            'migration' => $this->buildMigrationColumns($fields, $belongsTo),
            'fillable' => $this->buildFillable($fields),
            'rules' => $this->buildValidationRules($fields),
            'resourceProperties' => $this->buildResourceProperties($fields),
            'relations' => $this->buildRelations($belongsTo, $hasMany, $belongsToMany),
            'foreignKeys' => $this->buildForeignKeys($belongsTo),
            'factoryDefinitions' => $this->buildFactoryDefinitions($fields, $belongsTo),
            'searchableFields' => $this->getSearchableFields($fields),
            'belongsToMany' => $belongsToMany,
        ];
    }

    private function extractFields(string $schema): array
    {
        $fields = [];
        $items = explode(',', $schema);

        // Valid validation rule types that should be included in rules (when explicit rules are provided)
        // Note: 'string' is excluded as it's too generic and rarely used as a validation rule
        $validationRuleTypes = [
            'integer', 'int', 'numeric', 'float', 'decimal', 'boolean', 'bool',
            'array', 'email', 'url', 'ip', 'json', 'date', 'datetime', 'timestamp',
            'image', 'file', 'size', 'between', 'min', 'max', 'in', 'not_in',
            'unique', 'exists', 'confirmed', 'required', 'nullable',
        ];

        foreach ($items as $item) {
            $item = trim($item);
            if (empty($item)) {
                continue;
            }

            // Limit to 3 parts: name:type|rules - keeps rules with parameters intact (e.g., min:18)
            $parts = explode(':', $item, 3);
            $name = trim($parts[0]);
            $type = $parts[1] ?? 'string';
            $rules = $parts[2] ?? '';

            // Parse rules
            $parsedRules = $this->parseRules($rules);

            // Check if required BEFORE modifying parsedRules with field type
            // This ensures validation rules display correctly in the UI
            $isRequired = $this->isRequired($rules);

            // Include field type as a validation rule if:
            // 1. There are explicit rules provided AND
            // 2. The field type is a valid validation rule type AND
            // 3. The field type is not already in the rules
            if (! empty($parsedRules) && in_array($type, $validationRuleTypes, true) && ! in_array($type, $parsedRules, true)) {
                array_unshift($parsedRules, $type); // Add type as first rule
            }

            $fields[] = [
                'name' => $name,
                'type' => $this->mapType($type),
                'rules' => $parsedRules,
                'nullable' => ! $isRequired,
            ];
        }

        return $fields;
    }

    private function mapType(string $type): string
    {
        return $this->typeMap[$type] ?? 'string';
    }

    private function parseRules(string $rules): array
    {
        if (empty($rules)) {
            return [];
        }

        return explode('|', $rules);
    }

    private function isRequired(string $rules): bool
    {
        // If nullable is specified, field is not required regardless of required keyword
        if (str_contains($rules, 'nullable')) {
            return false;
        }

        return str_contains($rules, 'required');
    }

    private function buildMigrationColumns(array $fields, array $belongsTo): string
    {
        $lines = [];
        $lines[] = '$table->id();';

        foreach ($fields as $field) {
            $type = $field['type'];
            $name = $field['name'];
            $nullable = $field['nullable'];

            $method = in_array($type, $this->nullableTypes) && ! $nullable
                ? $type
                : $type;

            $line = "\$table->{$method}('{$name}')";

            if ($type === 'decimal' || $type === 'float' || $type === 'double') {
                $line .= '->default(0)';
            }

            if ($nullable) {
                $line .= '->nullable()';
            }

            $lines[] = "{$line};";
        }

        // Add foreign keys for belongsTo relations
        foreach ($belongsTo as $relatedModel) {
            $foreignTable = Str::plural(Str::snake($relatedModel));
            $foreignKey = Str::snake($relatedModel).'_id';
            $lines[] = "\$table->foreignId('{$foreignKey}')->constrained()->cascadeOnDelete();";
        }

        $lines[] = '$table->timestamps();';

        return implode("\n            ", $lines);
    }

    private function buildFillable(array $fields): string
    {
        $names = array_map(fn ($f) => "'{$f['name']}'", $fields);

        return implode(', ', $names);
    }

    private function buildValidationRules(array $fields): string
    {
        $lines = [];

        foreach ($fields as $field) {
            $rules = $field['rules'];
            $name = $field['name'];

            // If no rules provided, add default based on nullable
            if (empty($rules)) {
                if ($field['nullable']) {
                    $rules[] = 'nullable';
                } else {
                    $rules[] = 'required';
                }
            }

            if (! empty($rules)) {
                $rulesString = implode('|', $rules);
                $lines[] = "            '{$name}' => '{$rulesString}',";
            }
        }

        if (empty($lines)) {
            return '            // No validation rules defined';
        }

        return implode("\n", $lines);
    }

    private function buildResourceProperties(array $fields): string
    {
        $lines = [];

        foreach ($fields as $field) {
            $lines[] = "            '{$field['name']}' => \$this->{$field['name']},";
        }

        return implode("\n", $lines);
    }

    private function buildRelations(array $belongsTo, array $hasMany, array $belongsToMany): string
    {
        $lines = [];

        foreach ($belongsTo as $relatedModel) {
            $relationName = Str::camel($relatedModel);
            $lines[] = $this->buildBelongsToMethod($relatedModel, $relationName);
        }

        foreach ($hasMany as $relatedModel) {
            $relationName = Str::camel(Str::plural($relatedModel));
            $lines[] = $this->buildHasManyMethod($relatedModel, $relationName);
        }

        foreach ($belongsToMany as $relatedModel) {
            $relationName = Str::camel(Str::plural($relatedModel));
            $lines[] = $this->buildBelongsToManyMethod($relatedModel, $relationName);
        }

        return empty($lines) ? '' : implode("\n\n", $lines);
    }

    private function buildBelongsToMethod(string $relatedModel, string $relationName): string
    {
        return "    public function {$relationName}(): BelongsTo\n".
        "    {\n".
        "        return \$this->belongsTo({$relatedModel}::class);\n".
        '    }';
    }

    private function buildHasManyMethod(string $relatedModel, string $relationName): string
    {
        $relatedClass = Str::studly(Str::singular($relatedModel));

        return "    public function {$relationName}(): HasMany\n".
        "    {\n".
        "        return \$this->hasMany({$relatedClass}::class);\n".
        '    }';
    }

    private function buildBelongsToManyMethod(string $relatedModel, string $relationName): string
    {
        $relatedClass = Str::studly(Str::singular($relatedModel));

        return "    public function {$relationName}(): BelongsToMany\n".
        "    {\n".
        "        return \$this->belongsToMany({$relatedClass}::class);\n".
        '    }';
    }

    private function buildForeignKeys(array $belongsTo): string
    {
        if (empty($belongsTo)) {
            return '';
        }

        $lines = [];
        foreach ($belongsTo as $relatedModel) {
            $foreignKey = Str::snake($relatedModel).'_id';
            $lines[] = "'{$foreignKey}',";
        }

        return implode("\n            ", $lines);
    }

    private function buildFactoryDefinitions(array $fields, array $belongsTo): string
    {
        $lines = [];

        foreach ($fields as $field) {
            $name = $field['name'];
            $type = $field['type'];
            $faker = $this->getFakerMethod($name, $type);
            $lines[] = "            '{$name}' => {$faker},";
        }

        // Add foreign key factory definitions
        foreach ($belongsTo as $relatedModel) {
            $foreignKey = Str::snake($relatedModel).'_id';
            $lines[] = "            '{$foreignKey}' => {$relatedModel}::factory(),";
        }

        return implode("\n", $lines);
    }

    private function getFakerMethod(string $fieldName, string $fieldType): string
    {
        $name = Str::lower($fieldName);

        // Smart field name detection
        return match (true) {
            str_contains($name, 'email') => 'fake()->unique()->safeEmail()',
            str_contains($name, 'name') => 'fake()->name()',
            str_contains($name, 'title') => 'fake()->sentence()',
            str_contains($name, 'description') => 'fake()->paragraph()',
            str_contains($name, 'phone') => 'fake()->phoneNumber()',
            str_contains($name, 'address') => 'fake()->address()',
            str_contains($name, 'city') => 'fake()->city()',
            str_contains($name, 'country') => 'fake()->country()',
            str_contains($name, 'zip') || str_contains($name, 'postal') => 'fake()->postcode()',
            str_contains($name, 'password') => 'fake()->password()',
            str_contains($name, 'url') || str_contains($name, 'website') => 'fake()->url()',
            str_contains($name, 'company') => 'fake()->company()',
            str_contains($name, 'image') || str_contains($name, 'avatar') || str_contains($name, 'photo') => 'fake()->imageUrl()',
            str_contains($name, 'price') || str_contains($name, 'cost') => 'fake()->randomFloat(2, 1, 1000)',
            $fieldType === 'string' => 'fake()->word()',
            $fieldType === 'text' => 'fake()->paragraph()',
            $fieldType === 'integer' || $fieldType === 'bigInteger' => 'fake()->randomNumber()',
            $fieldType === 'decimal' => 'fake()->randomFloat(2, 0, 1000)',
            $fieldType === 'float' || $fieldType === 'double' => 'fake()->randomFloat()',
            $fieldType === 'boolean' => 'fake()->boolean()',
            $fieldType === 'date' => 'fake()->date()',
            $fieldType === 'dateTime' || $fieldType === 'timestamp' => 'fake()->dateTime()',
            $fieldType === 'json' => 'fake()->words(3)',
            $fieldType === 'uuid' => 'fake()->uuid()',
            default => 'fake()->word()',
        };
    }

    private function getSearchableFields(array $fields): array
    {
        $searchable = [];

        foreach ($fields as $field) {
            $type = $field['type'];
            $name = $field['name'];

            // String and text fields are searchable
            if (in_array($type, ['string', 'text'], true)) {
                $searchable[] = $name;
            }
        }

        return $searchable;
    }
}
