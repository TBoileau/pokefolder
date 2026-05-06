<?php

declare(strict_types=1);

namespace App\Command;

use App\UseCase\Catalog\SyncAll\Input as SyncAllInput;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;

use function sprintf;

#[AsCommand(
    name: 'app:catalog:sync',
    description: 'Dispatch a global TCGdex catalog sync (one SyncSeries per language, fanned out to SyncSets and SyncCards).',
)]
final class SyncCommand extends Command
{
    public function __construct(private readonly MessageBusInterface $messageBus)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'force',
            null,
            InputOption::VALUE_NONE,
            'Refetch everything from TCGdex even if already present locally.',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $symfonyStyle = new SymfonyStyle($input, $output);
        $force = (bool) $input->getOption('force');

        $this->messageBus->dispatch(new SyncAllInput(force: $force));

        $symfonyStyle->success(sprintf(
            'Global sync dispatched (force=%s). Run "bin/console messenger:consume async" to process the queue.',
            $force ? 'true' : 'false',
        ));

        return Command::SUCCESS;
    }
}
