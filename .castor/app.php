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

#[AsTask(description: 'Run Biome linter (writes diagnostics, no fixes).')]
function lint(): void
{
    io()->title('Running Biome lint');
    run('pnpm exec biome lint .', context: app_context());
}

#[AsTask(description: 'Format sources with Biome (writes to disk).')]
function format(): void
{
    io()->title('Running Biome format (write)');
    run('pnpm exec biome format --write .', context: app_context());
}

#[AsTask(name: 'format:check', description: 'Check formatting with Biome (CI: no writes).')]
function format_check(): void
{
    io()->title('Running Biome format (check)');
    run('pnpm exec biome format .', context: app_context());
}

#[AsTask(description: 'Run Biome combined check (lint + format) — CI: no writes.')]
function check(): void
{
    io()->title('Running Biome check');
    run('pnpm exec biome check .', context: app_context());
}

#[AsTask(description: 'Run TypeScript type-check on the app project (no emit).')]
function typecheck(): void
{
    io()->title('Running TypeScript type-check');
    run('pnpm exec tsc --noEmit -p tsconfig.app.json', context: app_context());
}

#[AsTask(description: 'Build the front app for production (tsc -b + vite build).')]
function build(): void
{
    io()->title('Building front for production');
    run('pnpm build', context: app_context());
}

#[AsTask(description: 'Audit pnpm dependencies for known vulnerabilities (moderate or higher).')]
function audit(): void
{
    io()->title('Auditing front dependencies');
    run('pnpm audit --audit-level=moderate', context: app_context());
}
