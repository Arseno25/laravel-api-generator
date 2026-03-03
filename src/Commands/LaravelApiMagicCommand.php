<?php

namespace Arseno25\LaravelApiMagic\Commands;

use Illuminate\Console\Command;

class LaravelApiMagicCommand extends Command
{
    public $signature = 'laravel-api-magic';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
