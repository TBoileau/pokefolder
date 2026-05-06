<?php

declare(strict_types=1);

namespace docker;

use Castor\Attribute\AsTask;

use function Castor\io;
use function Castor\run;

#[AsTask(description: 'Start docker compose services (postgres + rabbitmq) and wait until healthy.')]
function up(): void
{
    io()->title('Starting docker services');
    run('docker compose up -d --wait');
}

#[AsTask(description: 'Stop docker compose services (volumes preserved).')]
function down(): void
{
    io()->title('Stopping docker services');
    run('docker compose down');
}
