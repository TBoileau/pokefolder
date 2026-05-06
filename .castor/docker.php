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

#[AsTask(description: 'Tail docker compose logs (Ctrl-C to stop).')]
function logs(): void
{
    io()->title('Tailing docker logs');
    run('docker compose logs -f');
}

#[AsTask(description: 'Drop docker volumes and restart services (postgres + rabbitmq).')]
function reset(): void
{
    io()->title('Resetting docker (down -v + up --wait)');
    run('docker compose down -v');
    run('docker compose up -d --wait');
}

#[AsTask(name: 'purge-queue', description: 'Purge the local RabbitMQ "messages" queue.')]
function purge_queue(): void
{
    io()->title('Purging RabbitMQ queue "messages"');
    run('docker exec pokefolder-rabbitmq rabbitmqctl purge_queue messages');
}
