<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

uses()->group("commands", "reverse-engineer");

beforeEach(function () {
    $this->cleanupReverseEngineerArtifacts = function (): void {
        $paths = [
            app_path("Models/Product.php"),
            app_path("Http/Controllers/Api/ProductController.php"),
            app_path("Http/Controllers/Api/V2/ProductController.php"),
            app_path("Http/Requests/StoreProductRequest.php"),
            app_path("Http/Requests/UpdateProductRequest.php"),
            app_path("Http/Requests/V2/StoreProductRequest.php"),
            app_path("Http/Requests/V2/UpdateProductRequest.php"),
            app_path("Http/Resources/ProductResource.php"),
            app_path("Http/Resources/ProductCollection.php"),
            app_path("Http/Resources/V2/ProductResource.php"),
            app_path("Http/Resources/V2/ProductCollection.php"),
            app_path("Policies/ProductPolicy.php"),
        ];

        foreach ($paths as $path) {
            if (File::exists($path)) {
                File::delete($path);
            }
        }

        Schema::dropIfExists("products");
    };

    ($this->cleanupReverseEngineerArtifacts)();
});

afterEach(function () {
    ($this->cleanupReverseEngineerArtifacts)();
});

it("reverse engineers model casts and policy scaffolding", function () {
    Schema::create("products", function (Blueprint $table): void {
        $table->id();
        $table->string("name");
        $table->decimal("price", 10, 2)->nullable();
        $table->boolean("active")->default(true);
        $table->json("metadata")->nullable();
        $table->timestamps();
        $table->softDeletes();
    });

    $this->artisan("api-magic:reverse", [
        "--table" => "products",
        "--policy" => true,
        "--force" => true,
        "--no-interaction" => true,
    ])->assertExitCode(0);

    expect(File::exists(app_path("Models/Product.php")))->toBeTrue();
    expect(File::exists(app_path("Policies/ProductPolicy.php")))->toBeTrue();

    $modelContent = File::get(app_path("Models/Product.php"));
    $policyContent = File::get(app_path("Policies/ProductPolicy.php"));

    expect($modelContent)->toContain("'price' => 'decimal:2'");
    expect($modelContent)->toContain("'active' => 'boolean'");
    expect($modelContent)->toContain("use SoftDeletes;");
    expect($policyContent)->toContain("class ProductPolicy");
    expect($policyContent)->toContain(
        'public function delete(mixed $user, Product $product): bool',
    );
    expect($policyContent)->toContain(
        'public function restore(mixed $user, Product $product): bool',
    );
    expect($policyContent)->toContain("return false;");
});

it("writes versioned requests when a version is provided", function () {
    Schema::create("products", function (Blueprint $table): void {
        $table->id();
        $table->string("name");
        $table->timestamps();
    });

    $this->artisan("api-magic:reverse", [
        "--table" => "products",
        "--v" => "2",
        "--force" => true,
        "--no-interaction" => true,
    ])->assertExitCode(0);

    expect(
        File::exists(app_path("Http/Requests/V2/StoreProductRequest.php")),
    )->toBeTrue();
    expect(
        File::exists(app_path("Http/Requests/V2/UpdateProductRequest.php")),
    )->toBeTrue();

    $controllerContent = File::get(
        app_path("Http/Controllers/Api/V2/ProductController.php"),
    );
    $requestContent = File::get(
        app_path("Http/Requests/V2/StoreProductRequest.php"),
    );

    expect($controllerContent)->toContain(
        "use App\Http\Requests\V2\StoreProductRequest;",
    );
    expect($requestContent)->toContain("namespace App\Http\Requests\V2;");
});
