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
        $this->stubPath = dirname(__DIR__, 2) . "/resources/stubs/";
        $this->publishedStubPath = base_path("stubs/vendor/api-magic/");
    }

    /**
     * @param  array<string, scalar>  $replacements
     */
    public function generate(
        string $stubFile,
        array $replacements,
        string $destination,
    ): void {
        $stub = $this->getStub($stubFile);
        $content = $this->replacePlaceholders($stub, $replacements);

        $this->ensureDirectoryExists($destination);
        File::put($destination, $content);
    }

    private function getStub(string $filename): string
    {
        // First check if user has published customized stubs
        $publishedPath = $this->publishedStubPath . $filename;

        if (File::exists($publishedPath)) {
            return File::get($publishedPath);
        }

        // Fallback to package stubs
        $path = $this->stubPath . $filename;

        if (!File::exists($path)) {
            throw new \RuntimeException("Stub file not found: {$path}");
        }

        return File::get($path);
    }

    /**
     * @param  array<string, scalar>  $replacements
     */
    private function replacePlaceholders(
        string $stub,
        array $replacements,
    ): string {
        foreach ($replacements as $key => $value) {
            $replacement = is_bool($value)
                ? ($value
                    ? "1"
                    : "")
                : (string) $value;
            $stub = str_replace($key, $replacement, $stub);
        }

        // Handle block conditionals @conditionalName ... @endConditionalName
        $stub = preg_replace_callback(
            '/@(\w+)\s*\n(.*?)\s*@end\1/si',
            function ($matches) use ($replacements) {
                $conditionName = strtolower($matches[1]);
                $content = $matches[2];

                // Check if the condition is truthy in replacements (case-insensitive key lookup)
                $key = "{{ " . $conditionName . " }}";
                $isEnabled = false;
                foreach ($replacements as $rKey => $rVal) {
                    if (strtolower($rKey) === $key && $rVal) {
                        $isEnabled = true;
                        break;
                    }
                }

                return $isEnabled ? $content : "";
            },
            $stub,
        );

        if ($stub === null) {
            throw new \RuntimeException(
                "Unable to replace conditional stub blocks.",
            );
        }

        // Handle inline conditional replacements @conditionalName('content')
        $stub = preg_replace_callback(
            '/@(\w+)\(([\'"])(.+?)\2\)/',
            function ($matches) use ($replacements) {
                $conditionName = $matches[1];
                $content = $matches[3];

                // Check if the condition is truthy in replacements
                $key = "{{" . strtolower($conditionName) . "}}";
                $isEnabled = isset($replacements[$key]) && $replacements[$key];

                return $isEnabled ? $content : "";
            },
            $stub,
        );

        if ($stub === null) {
            throw new \RuntimeException(
                "Unable to replace inline conditional stub blocks.",
            );
        }

        return $stub;
    }

    private function ensureDirectoryExists(string $path): void
    {
        $directory = dirname($path);

        if (!File::isDirectory($directory)) {
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
        return File::exists($this->publishedStubPath . $stubFile);
    }

    public function getStubPath(string $stubFile): string
    {
        return $this->isStubPublished($stubFile)
            ? $this->publishedStubPath . $stubFile
            : $this->stubPath . $stubFile;
    }
}
