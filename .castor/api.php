<?php

declare(strict_types=1);

namespace api;

use Castor\Attribute\AsTask;

use function Castor\context;
use function Castor\io;
use function Castor\run;

const API_DIR = __DIR__ . '/../api';

function api_context(): \Castor\Context
{
    return context()->withWorkingDirectory(API_DIR);
}

#[AsTask(description: 'Install Composer dependencies for the API.')]
function install(): void
{
    io()->title('Installing API dependencies');
    run('composer install --no-interaction --prefer-dist', context: api_context());
}

#[AsTask(description: 'Run PHPStan static analysis (level max + bleedingEdge + strict + deprecation).')]
function phpstan(): void
{
    io()->title('Running PHPStan');
    run('vendor/bin/phpstan analyse --memory-limit=1G --no-progress', context: api_context());
}

#[AsTask(description: 'Apply Rector refactorings (writes to source files).')]
function rector(): void
{
    io()->title('Running Rector (apply)');
    run('vendor/bin/rector process', context: api_context());
}

#[AsTask(name: 'rector:check', description: 'Report Rector findings without applying (CI).')]
function rector_check(): void
{
    io()->title('Running Rector (dry-run)');
    run('vendor/bin/rector process --dry-run', context: api_context());
}

#[AsTask(name: 'cs-fixer', description: 'Apply PHP CS Fixer (writes to source files).')]
function cs_fixer(): void
{
    io()->title('Running PHP CS Fixer (fix)');
    run('vendor/bin/php-cs-fixer fix', context: api_context());
}

#[AsTask(name: 'cs-fixer:check', description: 'Report PHP CS Fixer findings without applying (CI).')]
function cs_fixer_check(): void
{
    io()->title('Running PHP CS Fixer (dry-run)');
    run('vendor/bin/php-cs-fixer fix --dry-run --diff', context: api_context());
}
