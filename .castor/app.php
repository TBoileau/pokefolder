<?php

declare(strict_types=1);

namespace app;

use Castor\Attribute\AsTask;

use function Castor\context;
use function Castor\io;
use function Castor\run;

const APP_DIR = __DIR__ . '/../app';

function app_context(): \Castor\Context
{
    return context()->withWorkingDirectory(APP_DIR);
}

#[AsTask(description: 'Install pnpm dependencies for the front app.')]
function install(): void
{
    io()->title('Installing front dependencies');
    run('pnpm install --frozen-lockfile', context: app_context());
}
