<?php

declare(strict_types=1);

namespace App\Command;

use App\UseCase\Catalog\SyncSets\Input;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\MessageBusInterface;

use function assert;
use function count;
use function is_string;
use function sprintf;

#[AsCommand(
    name: 'app:catalog:sync-serie',
    description: 'Dispatch a targeted TCGdex catalog sync for one serie in every configured language.',
)]
final class SyncSerieCommand extends Command
{
    /**
     * @param list<string> $languages
     */
    public function __construct(
        private readonly MessageBusInterface $messageBus,
        #[Autowire(param: 'pokefolder.catalog.languages')]
        private readonly array $languages,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('serieId', InputArgument::REQUIRED, 'TCGdex serie identifier (e.g. "base", "swsh", "sv")');
        $this->addOption(
            'force',
            null,
            InputOption::VALUE_NONE,
            'Refetch every set/card even if already present locally.',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $symfonyStyle = new SymfonyStyle($input, $output);
        $serieId = $input->getArgument('serieId');
        assert(is_string($serieId));
        $force = (bool) $input->getOption('force');

        foreach ($this->languages as $language) {
            $this->messageBus->dispatch(new Input($serieId, $language, $force));
        }

        $symfonyStyle->success(sprintf(
            'SyncSets dispatched for serie "%s" in %d languages (force=%s).',
            $serieId,
            count($this->languages),
            $force ? 'true' : 'false',
        ));

        return Command::SUCCESS;
    }
}
