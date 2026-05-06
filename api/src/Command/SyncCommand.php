<?php

declare(strict_types=1);

namespace App\Command;

use App\UseCase\Catalog\SyncAll\Input as SyncAllInput;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
    name: 'pokefolder:sync',
    description: 'Dispatch a global TCGdex catalog sync (lists every set, then enqueues one SyncSet message per set).',
)]
final class SyncCommand extends Command
{
    public function __construct(private readonly MessageBusInterface $bus)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->bus->dispatch(new SyncAllInput());

        $io->success('Global sync dispatched. Run "bin/console messenger:consume async" to process the queue.');

        return Command::SUCCESS;
    }
}
