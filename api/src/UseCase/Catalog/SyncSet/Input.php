<?php

declare(strict_types=1);

namespace App\UseCase\Catalog\SyncSet;

/**
 * Request to import a single TCGdex set into the local Card catalog.
 * Routed to the async transport (RabbitMQ in dev/prod, in-memory in tests).
 */
final readonly class Input
{
    public function __construct(public string $setId)
    {
    }
}
