<?php

declare(strict_types=1);

namespace App\Command;

use App\UseCase\Catalog\SyncSet\Input;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;

use function assert;
use function is_string;
use function sprintf;

#[AsCommand(
    name: 'pokefolder:sync-set',
    description: 'Dispatch a targeted TCGdex catalog sync for one set on the async queue.',
)]
final class SyncSetCommand extends Command
{
    public function __construct(private readonly MessageBusInterface $messageBus)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('setId', InputArgument::REQUIRED, 'TCGdex set identifier (e.g. "base1", "swsh1")');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $symfonyStyle = new SymfonyStyle($input, $output);
        $setId = $input->getArgument('setId');
        assert(is_string($setId));

        $this->messageBus->dispatch(new Input($setId));

        $symfonyStyle->success(sprintf(
            'Sync dispatched for set "%s". Run "bin/console messenger:consume async" to process it.',
            $setId,
        ));

        return Command::SUCCESS;
    }
}
