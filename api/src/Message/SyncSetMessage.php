<?php

declare(strict_types=1);

namespace App\Message;

/**
 * Request to import a single TCGdex set into the local Card catalogue.
 * Routed to the async transport (RabbitMQ in dev/prod, in-memory in tests).
 */
final readonly class SyncSetMessage
{
    public function __construct(public string $setId)
    {
    }
}
