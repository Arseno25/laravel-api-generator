<?php

namespace Arseno25\LaravelApiMagic\Generators;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

final class StubManager
{
    private string $stubPath;

    private string $publishedStubPath;

    public function __construct()
    {
        $this->stubPath = dirname(__DIR__, 2).'/resources/stubs/';
        $this->publishedStubPath = base_path('stubs/vendor/api-magic/');
    }

    public function generate(string $stubFile, array $replacements, string $destination): void
    {
        $stub = $this->getStub($stubFile);
        $content = $this->replacePlaceholders($stub, $replacements);

        $this->ensureDirectoryExists($destination);
        File::put($destination, $content);
    }

    private function getStub(string $filename): string
    {
        // First check if user has published customized stubs
        $publishedPath = $this->publishedStubPath.$filename;

        if (File::exists($publishedPath)) {
            return File::get($publishedPath);
        }

        // Fallback to package stubs
        $path = $this->stubPath.$filename;

        if (! File::exists($path)) {
            throw new \RuntimeException("Stub file not found: {$path}");
        }

        return File::get($path);
    }

    private function replacePlaceholders(string $stub, array $replacements): string
    {
        foreach ($replacements as $key => $value) {
            $stub = str_replace($key, $value, $stub);
        }

        return $stub;
    }

    private function ensureDirectoryExists(string $path): void
    {
        $directory = dirname($path);

        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }
    }

    public function getModelVariable(string $model): string
    {
        return Str::camel($model);
    }

    public function getPluralModelVariable(string $model): string
    {
        return Str::plural(Str::camel($model));
    }

    public function isStubPublished(string $stubFile): bool
    {
        return File::exists($this->publishedStubPath.$stubFile);
    }

    public function getStubPath(string $stubFile): string
    {
        return $this->isStubPublished($stubFile)
            ? $this->publishedStubPath.$stubFile
            : $this->stubPath.$stubFile;
    }
}
