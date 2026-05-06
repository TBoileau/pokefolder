<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Catalog\CatalogSynchronizer;
use App\Message\SyncSetMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class SyncSetMessageHandler
{
    public function __construct(private CatalogSynchronizer $synchronizer)
    {
    }

    public function __invoke(SyncSetMessage $message): void
    {
        $this->synchronizer->syncSet($message->setId);
    }
}
