<?php

namespace Arseno25\LaravelApiMagic\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
class ApiDeprecated
{
    public function __construct(
        public string $message = '',
        public ?string $since = null,
        public ?string $alternative = null,
    ) {}
}
