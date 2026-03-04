<?php

namespace Arseno25\LaravelApiMagic\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class ApiWebhook
{
    /**
     * @param  array<string, mixed>|null  $payload  Example webhook payload schema
     */
    public function __construct(
        public string $event,
        public string $description = '',
        public ?array $payload = null,
    ) {}
}
