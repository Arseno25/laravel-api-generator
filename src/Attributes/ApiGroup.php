<?php

namespace Arseno25\LaravelApiMagic\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
class ApiGroup
{
    public function __construct(public string $name) {}
}
