<?php

namespace Arseno25\LaravelApiMagic\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
class ApiMagicSchema
{
    /**
     * Manually define the OpenAPI schema for an endpoint's response.
     * Useful when the automated reflection parser fails on edge cases.
     *
     * @param  array<string, mixed>  $schema
     */
    public function __construct(
        public array $schema
    ) {}
}
