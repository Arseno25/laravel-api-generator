<?php

namespace Arseno25\LaravelApiMagic\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
class ApiMock
{
    public function __construct(
        public int $statusCode = 200,
        public int $count = 5,
    ) {}
}
