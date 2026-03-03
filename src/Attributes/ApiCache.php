<?php

namespace Arseno25\LaravelApiMagic\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class ApiCache
{
    public function __construct(
        public int $ttl = 60,
        public ?string $store = null,
    ) {}
}
