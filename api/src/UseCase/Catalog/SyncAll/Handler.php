<?php

declare(strict_types=1);

namespace App\UseCase\Catalog\SyncAll;

use App\UseCase\Catalog\SyncSeries\Input as SyncSeriesInput;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Kick-off orchestrator: dispatches one SyncSeries message per configured
 * language. Each downstream message is independent and idempotent.
 */
#[AsMessageHandler]
final readonly class Handler
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

    public function __invoke(Input $input): void
    {
        foreach ($this->languages as $language) {
            $this->messageBus->dispatch(new SyncSeriesInput($language, $input->force));
        }
    }
}
