<?php

declare(strict_types=1);

namespace App\Controller;

use App\UseCase\Catalog\SyncAll\Input as SyncAllInput;
use App\UseCase\Catalog\SyncCards\Input as SyncCardsInput;
use App\UseCase\Catalog\SyncSets\Input as SyncSetsInput;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Thin HTTP entry points that dispatch catalog sync use cases on the
 * async queue. Each accepts ?force=true to override the skip-if-exists
 * default.
 */
final readonly class SyncController
{
    /**
     * @param list<string> $languages
     */
    public function __construct(
        private MessageBusInterface $messageBus,
        #[Autowire(param: 'pokefolder.catalog.languages')]
        private array $languages,
    ) {
    }

    #[Route(path: '/api/sync', name: 'app_sync_all', methods: ['POST'])]
    public function syncAll(Request $request): JsonResponse
    {
        $force = $request->query->getBoolean('force');
        $this->messageBus->dispatch(new SyncAllInput(force: $force));

        return new JsonResponse(
            ['scope' => 'all', 'force' => $force, 'status' => 'dispatched'],
            Response::HTTP_ACCEPTED,
        );
    }

    #[Route(
        path: '/api/sync/series/{serieId}',
        name: 'app_sync_serie',
        requirements: ['serieId' => '[A-Za-z0-9_-]+'],
        methods: ['POST'],
    )]
    public function syncSerie(string $serieId, Request $request): JsonResponse
    {
        $force = $request->query->getBoolean('force');
        foreach ($this->languages as $language) {
            $this->messageBus->dispatch(new SyncSetsInput($serieId, $language, $force));
        }

        return new JsonResponse(
            ['scope' => 'serie', 'serieId' => $serieId, 'force' => $force, 'status' => 'dispatched'],
            Response::HTTP_ACCEPTED,
        );
    }

    #[Route(
        path: '/api/sync/sets/{setId}',
        name: 'app_sync_set',
        requirements: ['setId' => '[A-Za-z0-9_-]+'],
        methods: ['POST'],
    )]
    public function syncSet(string $setId, Request $request): JsonResponse
    {
        $force = $request->query->getBoolean('force');
        foreach ($this->languages as $language) {
            $this->messageBus->dispatch(new SyncCardsInput($setId, $language, $force));
        }

        return new JsonResponse(
            ['scope' => 'set', 'setId' => $setId, 'force' => $force, 'status' => 'dispatched'],
            Response::HTTP_ACCEPTED,
        );
    }
}
