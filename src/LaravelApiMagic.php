<?php

namespace Arseno25\LaravelApiMagic;

/**
 * Main LaravelApiMagic service class.
 *
 * Provides a programmatic API for generating API scaffolding
 * and accessing documentation features.
 */
class LaravelApiMagic
{
    /**
     * Get the package version.
     */
    public function version(): string
    {
        return '1.0.0';
    }

    /**
     * Check if documentation routes are enabled.
     */
    public function docsEnabled(): bool
    {
        return (bool) config('api-magic.docs.enabled', true);
    }

    /**
     * Get the documentation URL prefix.
     */
    public function docsPrefix(): string
    {
        return (string) config('api-magic.docs.prefix', 'docs');
    }

    /**
     * Get the exclude patterns for route analysis.
     *
     * @return array<int, string>
     */
    public function excludePatterns(): array
    {
        return (array) config('api-magic.docs.exclude_patterns', []);
    }

    /**
     * @var array<int, callable>
     */
    protected static array $afterParseCallbacks = [];

    /**
     * @var array<int, callable>
     */
    protected static array $beforeParseCallbacks = [];

    /**
     * Clear all registered parse callbacks.
     * Useful for testing state reset.
     */
    public static function clearParseCallbacks(): void
    {
        static::$beforeParseCallbacks = [];
        static::$afterParseCallbacks = [];
    }

    /**
     * Register a callback to be executed before parsing the schema.
     */
    public static function beforeParse(callable $callback): void
    {
        static::$beforeParseCallbacks[] = $callback;
    }

    /**
     * Register a callback to be executed after parsing the schema.
     * The callback receives a reference to the schema array.
     */
    public static function afterParse(callable $callback): void
    {
        static::$afterParseCallbacks[] = $callback;
    }

    /**
     * Execute all registered beforeParse callbacks.
     */
    public static function callBeforeParse(): void
    {
        foreach (static::$beforeParseCallbacks as $callback) {
            $callback();
        }
    }

    /**
     * Execute all registered afterParse callbacks.
     *
     * @param  array<string, mixed>  $schema
     */
    public static function callAfterParse(array &$schema): void
    {
        foreach (static::$afterParseCallbacks as $callback) {
            $callback($schema);
        }
    }
}
