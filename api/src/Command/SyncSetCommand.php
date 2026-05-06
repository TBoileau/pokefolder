<?php

declare(strict_types=1);

namespace App\Command;

use App\Catalog\CatalogSynchronizer;
use App\Catalog\SetNotFoundException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'pokefolder:sync-set',
    description: 'Synchronise a single TCGdex set into the local Card catalogue (synchronously).',
)]
final class SyncSetCommand extends Command
{
    public function __construct(private readonly CatalogSynchronizer $synchronizer)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('setId', InputArgument::REQUIRED, 'TCGdex set identifier (e.g. "base1", "swsh1")');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $setId = (string) $input->getArgument('setId');

        $io->title(\sprintf('Synchronising TCGdex set "%s"', $setId));

        try {
            $report = $this->synchronizer->syncSet($setId);
        } catch (SetNotFoundException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $io->success(\sprintf(
            'Sync complete: %d created, %d updated, %d unchanged (%d total).',
            $report->created,
            $report->updated,
            $report->unchanged,
            $report->processed(),
        ));

        return Command::SUCCESS;
    }
}
