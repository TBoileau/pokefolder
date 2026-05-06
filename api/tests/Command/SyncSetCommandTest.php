<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\UseCase\Catalog\SyncCards\Input;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;

final class SyncSetCommandTest extends KernelTestCase
{
    public function testCommandDispatchesSyncCardsMessagePerLanguage(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $commandTester = new CommandTester($application->find('app:catalog:sync-set'));
        $exitCode = $commandTester->execute(['setId' => 'base1']);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('dispatched', $commandTester->getDisplay());

        /** @var InMemoryTransport $transport */
        $transport = self::getContainer()->get('messenger.transport.async');
        $sent = $transport->getSent();

        // 2 languages configured (en, fr) → 2 messages.
        self::assertCount(2, $sent);
        foreach ($sent as $envelope) {
            $message = $envelope->getMessage();
            self::assertInstanceOf(Input::class, $message);
            self::assertSame('base1', $message->setId);
            self::assertFalse($message->force);
        }
    }
}
