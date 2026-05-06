<?php

declare(strict_types=1);

namespace test;

use Castor\Attribute\AsTask;

use function Castor\io;

#[AsTask(description: 'Run every test suite (api PHPUnit ; app Vitest will be added when front tests exist).')]
function all(): void
{
    io()->title('Running test suites');
    \api\test();
}
