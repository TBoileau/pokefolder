<?php

declare(strict_types=1);

use Castor\Attribute\AsContext;
use Castor\Context;

use function Castor\import;

import(__DIR__ . '/.castor');

#[AsContext(default: true, name: 'dev')]
function dev_context(): Context
{
    return new Context(
        environment: ['APP_ENV' => 'dev'],
        workingDirectory: __DIR__,
    );
}

#[AsContext(name: 'ci')]
function ci_context(): Context
{
    return new Context(
        environment: ['APP_ENV' => 'test', 'CI' => 'true'],
        workingDirectory: __DIR__,
    );
}
