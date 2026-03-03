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
}
