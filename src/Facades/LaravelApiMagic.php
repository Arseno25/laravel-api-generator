<?php

namespace Arseno25\LaravelApiMagic\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Arseno25\LaravelApiMagic\LaravelApiMagic
 */
class LaravelApiMagic extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Arseno25\LaravelApiMagic\LaravelApiMagic::class;
    }
}
