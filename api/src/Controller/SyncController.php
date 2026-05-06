<?php

declare(strict_types=1);

namespace App\Controller;

use App\UseCase\Catalog\SyncAll\Input as SyncAllInput;
use App\UseCase\Catalog\SyncSet\Input as SyncSetInput;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Thin HTTP entry point that dispatches the catalog sync use cases on the
 * async queue. Keeps the front out of the Symfony Messenger details — it
 * just hits POST /api/sync or POST /api/sync/{setId}.
 */
final readonly class SyncController
{
    public function __construct(private MessageBusInterface $messageBus)
    {
    }

    #[Route(path: '/api/sync', name: 'app_sync_all', methods: ['POST'])]
    public function syncAll(): JsonResponse
    {
        $this->messageBus->dispatch(new SyncAllInput());

        return new JsonResponse(
            ['scope' => 'all', 'status' => 'dispatched'],
            Response::HTTP_ACCEPTED,
        );
    }

    #[Route(path: '/api/sync/{setId}', name: 'app_sync_set', requirements: ['setId' => '[A-Za-z0-9_-]+'], methods: ['POST'])]
    public function syncSet(string $setId): JsonResponse
    {
        $this->messageBus->dispatch(new SyncSetInput($setId));

        return new JsonResponse(
            ['scope' => 'set', 'setId' => $setId, 'status' => 'dispatched'],
            Response::HTTP_ACCEPTED,
        );
    }
}
