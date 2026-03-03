<?php

use Arseno25\LaravelApiMagic\Parsers\RequestAnalyzer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\File;

uses()->group('parsers', 'request-analyzer');

beforeEach(function () {
    $this->analyzer = new RequestAnalyzer;
});

describe('FormRequest analysis', function () {
    it('returns empty array for non-existent class', function () {
        $result = $this->analyzer->analyze('App\\Http\\Requests\\NonExistentRequest');

        expect($result)->toBeEmpty();
    });

    it('returns empty array for non-FormRequest class', function () {
        // Create a non-FormRequest class
        $classContent = <<<'PHP'
<?php

namespace App\Http\Requests;

class NotAFormRequest
{
    public function rules(): array
    {
        return [];
    }
}
PHP;

        $tempPath = base_path('app/Http/Requests/NotAFormRequest.php');
        File::ensureDirectoryExists(dirname($tempPath));
        File::put($tempPath, $classContent);

        $result = $this->analyzer->analyze('App\\Http\\Requests\\NotAFormRequest');

        expect($result)->toBeEmpty();

        // Clean up
        File::delete($tempPath);
    });

    it('analyzes a valid FormRequest class', function () {
        // Create a FormRequest class
        $requestContent = <<<'PHP'
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TestProductRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
        ];
    }
}
PHP;

        $tempPath = base_path('app/Http/Requests/TestProductRequest.php');
        File::ensureDirectoryExists(dirname($tempPath));
        File::put($tempPath, $requestContent);
        require_once $tempPath;

        $result = $this->analyzer->analyze('App\\Http\\Requests\\TestProductRequest');

        expect($result)->not->toBeEmpty();
        expect($result)->toHaveKey('name');
        expect($result)->toHaveKey('email');
        expect($result['name']['type'])->toBe('string');
        expect($result['name']['required'])->toBeTrue();
        expect($result['email']['type'])->toBe('email');

        // Clean up
        File::delete($tempPath);
    });
});

describe('rule parsing', function () {
    it('detects required field', function () {
        $requestContent = <<<'PHP'
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TestRequiredRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }
}
PHP;

        $tempPath = base_path('app/Http/Requests/TestRequiredRequest.php');
        File::ensureDirectoryExists(dirname($tempPath));
        File::put($tempPath, $requestContent);
        require_once $tempPath;

        $result = $this->analyzer->analyze('App\\Http\\Requests\\TestRequiredRequest');

        expect($result['name']['required'])->toBeTrue();

        // Clean up
        File::delete($tempPath);
    });

    it('detects nullable field', function () {
        $requestContent = <<<'PHP'
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TestNullableRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'description' => 'nullable|string',
        ];
    }
}
PHP;

        $tempPath = base_path('app/Http/Requests/TestNullableRequest.php');
        File::ensureDirectoryExists(dirname($tempPath));
        File::put($tempPath, $requestContent);
        require_once $tempPath;

        $result = $this->analyzer->analyze('App\\Http\\Requests\\TestNullableRequest');

        expect($result['description']['required'])->toBeFalse();

        // Clean up
        File::delete($tempPath);
    });

    it('extracts enum values from in: rule', function () {
        $requestContent = <<<'PHP'
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TestEnumRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'status' => 'required|in:pending,approved,rejected',
        ];
    }
}
PHP;

        $tempPath = base_path('app/Http/Requests/TestEnumRequest.php');
        File::ensureDirectoryExists(dirname($tempPath));
        File::put($tempPath, $requestContent);
        require_once $tempPath;

        $result = $this->analyzer->analyze('App\\Http\\Requests\\TestEnumRequest');

        expect($result['status']['enum'])->toBe(['pending', 'approved', 'rejected']);

        // Clean up
        File::delete($tempPath);
    });
});

describe('type guessing', function () {
    it('guesses integer type', function () {
        $requestContent = <<<'PHP'
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TestIntegerRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'count' => 'integer',
        ];
    }
}
PHP;

        $tempPath = base_path('app/Http/Requests/TestIntegerRequest.php');
        File::ensureDirectoryExists(dirname($tempPath));
        File::put($tempPath, $requestContent);
        require_once $tempPath;

        $result = $this->analyzer->analyze('App\\Http\\Requests\\TestIntegerRequest');

        expect($result['count']['type'])->toBe('integer');

        // Clean up
        File::delete($tempPath);
    });

    it('guesses boolean type', function () {
        $requestContent = <<<'PHP'
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TestBooleanRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
PHP;

        $tempPath = base_path('app/Http/Requests/TestBooleanRequest.php');
        File::ensureDirectoryExists(dirname($tempPath));
        File::put($tempPath, $requestContent);
        require_once $tempPath;

        $result = $this->analyzer->analyze('App\\Http\\Requests\\TestBooleanRequest');

        expect($result['is_active']['type'])->toBe('boolean');

        // Clean up
        File::delete($tempPath);
    });

    it('guesses array type', function () {
        $requestContent = <<<'PHP'
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TestArrayRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'tags' => 'array',
        ];
    }
}
PHP;

        $tempPath = base_path('app/Http/Requests/TestArrayRequest.php');
        File::ensureDirectoryExists(dirname($tempPath));
        File::put($tempPath, $requestContent);
        require_once $tempPath;

        $result = $this->analyzer->analyze('App\\Http\\Requests\\TestArrayRequest');

        expect($result['tags']['type'])->toBe('array');

        // Clean up
        File::delete($tempPath);
    });

    it('guesses email type', function () {
        $requestContent = <<<'PHP'
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TestEmailRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email_address' => 'email',
        ];
    }
}
PHP;

        $tempPath = base_path('app/Http/Requests/TestEmailRequest.php');
        File::ensureDirectoryExists(dirname($tempPath));
        File::put($tempPath, $requestContent);
        require_once $tempPath;

        $result = $this->analyzer->analyze('App\\Http\\Requests\\TestEmailRequest');

        expect($result['email_address']['type'])->toBe('email');

        // Clean up
        File::delete($tempPath);
    });

    it('defaults to string type', function () {
        $requestContent = <<<'PHP'
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TestStringRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => 'required',
        ];
    }
}
PHP;

        $tempPath = base_path('app/Http/Requests/TestStringRequest.php');
        File::ensureDirectoryExists(dirname($tempPath));
        File::put($tempPath, $requestContent);
        require_once $tempPath;

        $result = $this->analyzer->analyze('App\\Http\\Requests\\TestStringRequest');

        expect($result['title']['type'])->toBe('string');

        // Clean up
        File::delete($tempPath);
    });
});

describe('description generation', function () {
    it('generates description for required field', function () {
        $requestContent = <<<'PHP'
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TestDescRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required',
        ];
    }
}
PHP;

        $tempPath = base_path('app/Http/Requests/TestDescRequest.php');
        File::ensureDirectoryExists(dirname($tempPath));
        File::put($tempPath, $requestContent);
        require_once $tempPath;

        $result = $this->analyzer->analyze('App\\Http\\Requests\\TestDescRequest');

        expect($result['name']['description'])->toContain('Required');

        // Clean up
        File::delete($tempPath);
    });

    it('generates description with min constraint', function () {
        $requestContent = <<<'PHP'
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TestMinRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'age' => 'min:18',
        ];
    }
}
PHP;

        $tempPath = base_path('app/Http/Requests/TestMinRequest.php');
        File::ensureDirectoryExists(dirname($tempPath));
        File::put($tempPath, $requestContent);
        require_once $tempPath;

        $result = $this->analyzer->analyze('App\\Http\\Requests\\TestMinRequest');

        expect($result['age']['description'])->toContain('Min: 18');

        // Clean up
        File::delete($tempPath);
    });
});

describe('index query parameters', function () {
    it('returns standard pagination parameters', function () {
        $params = $this->analyzer->getIndexQueryParameters();

        expect($params)->toHaveCount(3);

        $paramNames = array_column($params, 'name');
        expect($paramNames)->toContain('page', 'per_page', 'search');
    });

    it('includes page parameter with correct schema', function () {
        $params = $this->analyzer->getIndexQueryParameters();
        $pageParam = collect($params)->first(fn ($p) => $p['name'] === 'page');

        expect($pageParam)->not->toBeNull();
        expect($pageParam['type'])->toBe('integer');
        expect($pageParam['required'])->toBeFalse();
        expect($pageParam['schema']['default'])->toBe(1);
        expect($pageParam['schema']['minimum'])->toBe(1);
    });

    it('includes per_page parameter with constraints', function () {
        $params = $this->analyzer->getIndexQueryParameters();
        $perPageParam = collect($params)->first(fn ($p) => $p['name'] === 'per_page');

        expect($perPageParam)->not->toBeNull();
        expect($perPageParam['schema']['default'])->toBe(15);
        expect($perPageParam['schema']['minimum'])->toBe(1);
        expect($perPageParam['schema']['maximum'])->toBe(100);
    });

    it('includes search parameter', function () {
        $params = $this->analyzer->getIndexQueryParameters();
        $searchParam = collect($params)->first(fn ($p) => $p['name'] === 'search');

        expect($searchParam)->not->toBeNull();
        expect($searchParam['type'])->toBe('string');
        expect($searchParam['required'])->toBeFalse();
        expect($searchParam['description'])->toBe('Search query to filter results');
    });
});
