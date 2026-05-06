<?php

declare(strict_types=1);

namespace api;

use Castor\Attribute\AsTask;

use function Castor\io;
use function Castor\run;

#[AsTask(description: 'Install Composer dependencies for the API.')]
function install(): void
{
    io()->title('Installing API dependencies');
    run(
        'composer install --no-interaction --prefer-dist',
        workingDirectory: __DIR__ . '/../api',
    );
}
