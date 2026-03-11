<?php

namespace Arseno25\LaravelApiMagic\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class ApiResponse
{
    /**
     * @param  array<string, mixed>|null  $example
     */
    public function __construct(
        public int $status = 200,
        public ?string $resource = null,
        public string $description = '',
        public ?array $example = null,
        public bool $isArray = false,
    ) {}
}
