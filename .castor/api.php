<?php

declare(strict_types=1);

namespace api;

use Castor\Attribute\AsTask;
use Castor\Context;

use function Castor\context;
use function Castor\io;
use function Castor\run;

const API_DIR = __DIR__ . '/../api';

function api_context(): Context
{
    return context()->withWorkingDirectory(API_DIR);
}

function api_test_context(): Context
{
    return api_context()->withEnvironment(['APP_ENV' => 'test']);
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

#[AsTask(name: 'composer:validate', description: 'Validate composer.json structure and consistency (--strict).')]
function composer_validate(): void
{
    io()->title('Validating composer.json');
    run('composer validate --strict', context: api_context());
}

#[AsTask(name: 'composer:audit', description: 'Audit installed Composer packages for known security advisories.')]
function composer_audit(): void
{
    io()->title('Auditing Composer dependencies');
    run('composer audit', context: api_context());
}

#[AsTask(name: 'lint:yaml', description: 'Validate YAML files under config/ (parse-tags aware).')]
function lint_yaml(): void
{
    io()->title('Linting YAML config');
    run('bin/console lint:yaml config/ --parse-tags', context: api_context());
}

#[AsTask(name: 'lint:container', description: 'Validate the Symfony DI container (service IDs, factories, parameters).')]
function lint_container(): void
{
    io()->title('Linting Symfony DI container');
    run('bin/console lint:container', context: api_context());
}

#[AsTask(name: 'doctrine:validate', description: 'Validate the Doctrine schema (mapping vs DB).')]
function doctrine_validate(): void
{
    io()->title('Validating Doctrine schema');
    run('bin/console doctrine:schema:validate', context: api_context());
}

#[AsTask(description: 'Run the PHPUnit test suite (env: test).')]
function test(): void
{
    io()->title('Running PHPUnit');
    run('vendor/bin/phpunit', context: api_test_context());
}

#[AsTask(description: 'Apply pending Doctrine migrations on the dev database.')]
function migrate(): void
{
    io()->title('Migrating dev DB');
    run('bin/console doctrine:migrations:migrate --no-interaction', context: api_context());
}

#[AsTask(name: 'migrate:test', description: 'Apply pending Doctrine migrations on the test database.')]
function migrate_test(): void
{
    io()->title('Migrating test DB');
    run('bin/console doctrine:migrations:migrate --no-interaction', context: api_test_context());
}

#[AsTask(name: 'migrate:diff', description: 'Generate a new Doctrine migration from the entity diff.')]
function migrate_diff(): void
{
    io()->title('Generating Doctrine migration diff');
    run('bin/console doctrine:migrations:diff --no-interaction', context: api_context());
}

#[AsTask(name: 'migrate:status', description: 'Show the Doctrine migrations status (dev DB).')]
function migrate_status(): void
{
    io()->title('Doctrine migrations status');
    run('bin/console doctrine:migrations:status', context: api_context());
}

const CARDS_DUMP = API_DIR . '/var/db/cards.sql';

#[AsTask(name: 'db:dump', description: 'Dump the catalog (card table) to api/var/db/cards.sql so we can re-seed without re-syncing TCGdex.')]
function db_dump(): void
{
    io()->title('Dumping card table to api/var/db/cards.sql');
    run('mkdir -p var/db', context: api_context());
    run(
        'docker exec pokefolder-postgres pg_dump -U pokefolder -d pokefolder --data-only --inserts -t card > var/db/cards.sql',
        context: api_context(),
    );
    io()->success('Dump written to api/var/db/cards.sql.');
}

#[AsTask(name: 'db:load', description: 'Reload api/var/db/cards.sql into the dev catalog (truncates the card table first). No-op if the dump is missing.')]
function db_load(): void
{
    if (!\is_file(CARDS_DUMP)) {
        io()->warning('api/var/db/cards.sql not found — run `castor api:db:dump` after a sync to seed it.');

        return;
    }

    io()->title('Loading card dump into dev DB');
    run(
        'docker exec -i pokefolder-postgres psql -U pokefolder -d pokefolder -c "TRUNCATE TABLE card"',
        context: api_context(),
    );
    run(
        'docker exec -i pokefolder-postgres psql -U pokefolder -d pokefolder < var/db/cards.sql',
        context: api_context(),
    );
    io()->success('Card dump loaded into dev DB.');
}

#[AsTask(name: 'db:reset', description: 'Drop and recreate the dev AND test databases, re-apply all migrations, and re-seed the dev catalog from api/var/db/cards.sql when present.')]
function db_reset(): void
{
    io()->title('Resetting dev + test databases');

    foreach (['dev', 'test'] as $env) {
        $envContext = api_context()->withEnvironment(['APP_ENV' => $env]);
        io()->section(\sprintf('[%s] drop + create + migrate', $env));
        run('bin/console doctrine:database:drop --if-exists --force', context: $envContext);
        run('bin/console doctrine:database:create --if-not-exists', context: $envContext);
        run('bin/console doctrine:migrations:migrate --no-interaction', context: $envContext);
    }

    if (\is_file(CARDS_DUMP)) {
        io()->section('[dev] reload card dump');
        db_load();
    }
}
