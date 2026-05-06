<?php

declare(strict_types=1);

namespace qa;

use Castor\Attribute\AsTask;

use function Castor\io;
use function Castor\parallel;

#[AsTask(description: 'Run every read-only check (api + app) in parallel — CI gate.')]
function all(): void
{
    io()->title('Running QA checks (parallel)');
    parallel(
        \api\phpstan(...),
        \api\rector_check(...),
        \api\cs_fixer_check(...),
        \api\composer_validate(...),
        \api\composer_audit(...),
        \api\lint_yaml(...),
        \api\lint_container(...),
        \api\doctrine_validate(...),
        \app\check(...),
        \app\typecheck(...),
        \app\audit(...),
    );
}

#[AsTask(description: 'Apply all autofixers in sequence: api:rector → api:cs-fixer → app:format. Order matters: Rector can introduce code that CS Fixer needs to reformat.')]
function fix(): void
{
    io()->title('Running QA fixers (sequential)');
    \api\rector();
    \api\cs_fixer();
    \app\format();
}
