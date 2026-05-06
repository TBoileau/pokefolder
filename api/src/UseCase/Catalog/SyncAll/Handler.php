<?php

declare(strict_types=1);

namespace App\UseCase\Catalog\SyncAll;

use App\Service\Catalog\Provider\TCGdexProvider;
use App\UseCase\Catalog\SyncSet\Input as SyncSetInput;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

use function count;

/**
 * Resolves the full TCGdex set list (in the first configured language)
 * and fans out one SyncSet message per set on the async queue. The actual
 * per-set sync is left to the SyncSet handler — this handler only
 * dispatches.
 *
 * Sets that exist in additional languages but not the first configured
 * one are not visible from here; they remain reachable via a targeted
 * `pokefolder:sync-set <setId>` invocation. The list of configured
 * languages is the parameter `pokefolder.catalog.languages`.
 */
#[AsMessageHandler]
final readonly class Handler
{
    /**
     * @param list<string> $languages ISO 639-1 codes (e.g. ['en', 'fr']).
     */
    public function __construct(
        private TCGdexProvider $tcGdexProvider,
        private MessageBusInterface $messageBus,
        #[Autowire(param: 'pokefolder.catalog.languages')]
        private array $languages,
    ) {
    }

    public function __invoke(Input $input): Output
    {
        $sourceLanguage = $this->languages[0] ?? 'en';
        $setIds = $this->tcGdexProvider->listSetIds($sourceLanguage);

        foreach ($setIds as $setId) {
            $this->messageBus->dispatch(new SyncSetInput($setId));
        }

        return new Output(count($setIds));
    }
}
