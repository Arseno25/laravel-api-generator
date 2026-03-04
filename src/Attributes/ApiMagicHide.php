<?php

namespace Arseno25\LaravelApiMagic\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class ApiMagicHide
{
    /**
     * Marker attribute to prevent a Controller or Method from being
     * picked up by the API documentation generator.
     */
}
