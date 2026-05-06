<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\UseCase\Catalog\SyncSet\Input;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;

final class SyncSetCommandTest extends KernelTestCase
{
    public function testCommandDispatchesSyncSetMessageToAsyncTransport(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $commandTester = new CommandTester($application->find('pokefolder:sync-set'));
        $exitCode = $commandTester->execute(['setId' => 'base1']);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('dispatched', $commandTester->getDisplay());

        /** @var InMemoryTransport $transport */
        $transport = self::getContainer()->get('messenger.transport.async');
        $sent = $transport->getSent();

        self::assertCount(1, $sent);
        $message = $sent[0]->getMessage();
        self::assertInstanceOf(Input::class, $message);
        self::assertSame('base1', $message->setId);
    }
}
