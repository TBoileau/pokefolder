<?php

declare(strict_types=1);

namespace App\UseCase\Catalog\SyncAll;

/**
 * Trigger a global TCGdex catalog sync. Carries no payload — the handler
 * resolves the set list from TCGdex itself, then dispatches one
 * App\UseCase\Catalog\SyncSet\Input per set.
 *
 * Routed to the async transport (RabbitMQ in dev/prod, in-memory in tests).
 */
final readonly class Input
{
}
