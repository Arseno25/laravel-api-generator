<?php

namespace Arseno25\LaravelApiMagic\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class ApiExample
{
    /**
     * @param  array<string, mixed>|null  $request  Example request body
     * @param  array<string, mixed>|null  $response  Example response body
     */
    public function __construct(
        public ?array $request = null,
        public ?array $response = null,
    ) {}
}
