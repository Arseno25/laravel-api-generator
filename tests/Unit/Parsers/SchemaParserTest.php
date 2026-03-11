<?php

use Arseno25\LaravelApiMagic\Parsers\SchemaParser;

uses()->group('parsers', 'schema-parser');

beforeEach(function () {
    $this->parser = new SchemaParser;
});

describe('basic parsing', function () {
    it('parses simple schema string', function () {
        $result = $this->parser->parse('name:string|required');

        expect($result)->toBeArray();
        expect($result)->toHaveKeys([
            'migration',
            'fillable',
            'rules',
            'resourceProperties',
            'relations',
            'foreignKeys',
        ]);
    });

    it('parses multiple fields', function () {
        $result = $this->parser->parse(
            'name:string|required,age:integer,email:email|nullable',
        );

        expect($result['fillable'])->toContain("'name'");
        expect($result['fillable'])->toContain("'age'");
        expect($result['fillable'])->toContain("'email'");
    });

    it('builds migration columns correctly', function () {
        $result = $this->parser->parse('name:string|required,price:integer');

        expect($result['migration'])->toContain("\$table->string('name')");
        expect($result['migration'])->toContain("\$table->integer('price')");
    });

    it('parses integer type', function () {
        $result = $this->parser->parse('count:integer');

        expect($result['migration'])->toContain("integer('count')");
    });

    it('parses text type', function () {
        $result = $this->parser->parse('description:text');

        expect($result['migration'])->toContain("text('description')");
    });

    it('parses boolean type', function () {
        $result = $this->parser->parse('is_active:boolean');

        expect($result['migration'])->toContain("boolean('is_active')");
    });

    it('parses decimal type', function () {
        $result = $this->parser->parse('price:decimal');

        expect($result['migration'])->toContain("decimal('price')");
        expect($result['migration'])->toContain('->default(0)');
    });

    it('parses date type', function () {
        $result = $this->parser->parse('published_at:date');

        expect($result['migration'])->toContain("date('published_at')");
    });
});

describe('nullable handling', function () {
    it('adds nullable to migration when field is nullable', function () {
        $result = $this->parser->parse('description:text|nullable');

        expect($result['migration'])->toContain('->nullable()');
    });

    it('includes nullable in validation rules', function () {
        $result = $this->parser->parse('description:text|nullable');

        expect($result['rules'])->toContain('nullable');
    });
});

describe('validation rules', function () {
    it('includes required rule', function () {
        $result = $this->parser->parse('name:string|required');

        expect($result['rules'])->toContain("'name' => ['required']");
    });

    it('includes email rule for email type', function () {
        $result = $this->parser->parse('email:email|required');

        // email is not a column type, so it's treated as a validation rule
        expect($result['rules'])->toContain("'email' => ['email', 'required']");
    });

    it('includes min rule with integer type', function () {
        $result = $this->parser->parse('age:integer|min:18');

        expect($result['rules'])->toContain("'age' => ['min:18', 'integer']");
    });

    it('includes max rule with integer type', function () {
        $result = $this->parser->parse('age:integer|max:100');

        expect($result['rules'])->toContain("'age' => ['max:100', 'integer']");
    });

    it('includes unique rule', function () {
        $result = $this->parser->parse('slug:string|unique:posts');

        expect($result['rules'])->toContain("'slug' => ['unique:posts']");
    });

    it('combines multiple validation rules', function () {
        $result = $this->parser->parse('email:email|required|unique:users');

        // email is not a column type — all parts are treated as rules
        expect($result['rules'])->toContain(
            "'email' => ['email', 'required', 'unique:users']",
        );
    });

    it('preserves uuid validation rules', function () {
        $result = $this->parser->parse('order_id:uuid|required');

        expect($result['rules'])->toContain(
            "'order_id' => ['required', 'uuid']",
        );
    });
});

describe('fillable generation', function () {
    it('generates fillable array correctly', function () {
        $result = $this->parser->parse('name:string|required,age:integer');

        expect($result['fillable'])->toBe("'name', 'age'");
    });
});

describe('resource properties', function () {
    it('generates resource properties correctly', function () {
        $result = $this->parser->parse('name:string|required,age:integer');

        expect($result['resourceProperties'])->toContain(
            "'name' => \$this->name",
        );
        expect($result['resourceProperties'])->toContain(
            "'age' => \$this->age",
        );
    });
});

describe('relation parsing', function () {
    it('parses belongsTo relation', function () {
        $result = $this->parser->parse('name:string|required', ['Category']);

        expect($result['relations'])->toContain('BelongsTo');
        expect($result['relations'])->toContain('category(): BelongsTo');
    });

    it('parses hasMany relation', function () {
        $result = $this->parser->parse('name:string|required', [], ['Comment']);

        expect($result['relations'])->toContain('HasMany');
        expect($result['relations'])->toContain('comments(): HasMany');
    });

    it('adds foreign keys for belongsTo relations', function () {
        $result = $this->parser->parse('name:string|required', ['Category']);

        expect($result['migration'])->toContain(
            "foreignId('category_id')->constrained()->cascadeOnDelete()",
        );
    });

    it('builds foreign keys array for fillable', function () {
        $result = $this->parser->parse('name:string|required', ['Category']);

        expect($result['foreignKeys'])->toContain("'category_id'");
    });

    it('parses multiple belongsTo relations', function () {
        $result = $this->parser->parse('name:string|required', [
            'Category',
            'User',
        ]);

        expect($result['relations'])->toContain('BelongsTo');
        expect($result['migration'])->toContain("foreignId('category_id')");
        expect($result['migration'])->toContain("foreignId('user_id')");
    });

    it('parses multiple hasMany relations', function () {
        $result = $this->parser->parse(
            'name:string|required',
            [],
            ['Comment', 'Tag'],
        );

        expect($result['relations'])->toContain('HasMany');
    });

    it('handles both belongsTo and hasMany', function () {
        $result = $this->parser->parse(
            'name:string|required',
            ['Category'],
            ['Comment'],
        );

        expect($result['relations'])->toContain('BelongsTo');
        expect($result['relations'])->toContain('HasMany');
    });
});

describe('migration generation', function () {
    it('includes id column', function () {
        $result = $this->parser->parse('name:string|required');

        expect($result['migration'])->toContain('$table->id();');
    });

    it('includes timestamps', function () {
        $result = $this->parser->parse('name:string|required');

        expect($result['migration'])->toContain('$table->timestamps();');
    });

    it('handles decimal types with default values', function () {
        $result = $this->parser->parse('price:decimal');

        expect($result['migration'])->toContain("decimal('price')");
        expect($result['migration'])->toContain('->default(0)');
    });

    it('handles float types with default values', function () {
        $result = $this->parser->parse('price:float');

        expect($result['migration'])->toContain("float('price')");
        expect($result['migration'])->toContain('->default(0)');
    });

    it('handles double types with default values', function () {
        $result = $this->parser->parse('price:double');

        expect($result['migration'])->toContain("double('price')");
        expect($result['migration'])->toContain('->default(0)');
    });
});

describe('edge cases', function () {
    it('handles empty schema string', function () {
        $result = $this->parser->parse('');

        expect($result['fillable'])->toBeEmpty();
    });

    it('handles field names with underscores', function () {
        $result = $this->parser->parse('first_name:string|required');

        expect($result['fillable'])->toContain("'first_name'");
    });

    it('trims whitespace from field names', function () {
        $result = $this->parser->parse(' name :string|required ');

        expect($result['fillable'])->toContain("'name'");
    });

    it('defaults to string type when type is not specified', function () {
        $result = $this->parser->parse('name');

        expect($result['migration'])->toContain("string('name')");
    });

    it('defaults to nullable when no rules are specified', function () {
        $result = $this->parser->parse('name:string');

        // Without explicit required, field defaults to nullable
        expect($result['rules'])->toContain("'name' => ['nullable']");
    });
});
