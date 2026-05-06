<?php

declare(strict_types=1);

namespace app;

use Castor\Attribute\AsTask;

use function Castor\io;
use function Castor\run;

#[AsTask(description: 'Install pnpm dependencies for the front app.')]
function install(): void
{
    io()->title('Installing front dependencies');
    run(
        'pnpm install --frozen-lockfile',
        workingDirectory: __DIR__ . '/../app',
    );
}
