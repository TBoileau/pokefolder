<?php

declare(strict_types=1);

namespace App\Tests\UseCase\Catalog\SyncAll;

use App\Service\Catalog\DTO\TCGdexSet;
use App\Tests\Service\Catalog\Provider\InMemoryTCGdexProvider;
use App\UseCase\Catalog\SyncAll\Handler;
use App\UseCase\Catalog\SyncAll\Input;
use App\UseCase\Catalog\SyncSet\Input as SyncSetInput;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;

final class HandlerTest extends KernelTestCase
{
    private InMemoryTCGdexProvider $inMemoryTCGdexProvider;

    private MessageBusInterface $messageBus;

    private InMemoryTransport $inMemoryTransport;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $this->inMemoryTCGdexProvider = new InMemoryTCGdexProvider();
        $this->messageBus = $container->get(MessageBusInterface::class);
        /** @var InMemoryTransport $transport */
        $transport = $container->get('messenger.transport.async');
        $this->inMemoryTransport = $transport;
    }

    public function testDispatchesOneSyncSetInputPerSet(): void
    {
        $this->inMemoryTCGdexProvider->register('base1', 'en', new TCGdexSet('base1', []));
        $this->inMemoryTCGdexProvider->register('jungle', 'en', new TCGdexSet('jungle', []));
        $this->inMemoryTCGdexProvider->register('fossil', 'en', new TCGdexSet('fossil', []));

        $handler = new Handler($this->inMemoryTCGdexProvider, $this->messageBus, ['en']);

        $output = ($handler)(new Input());

        self::assertSame(3, $output->dispatched);

        $sent = $this->inMemoryTransport->getSent();
        self::assertCount(3, $sent);
        $dispatchedSetIds = [];
        foreach ($sent as $envelope) {
            $message = $envelope->getMessage();
            self::assertInstanceOf(SyncSetInput::class, $message);
            $dispatchedSetIds[] = $message->setId;
        }

        self::assertSame(['base1', 'jungle', 'fossil'], $dispatchedSetIds);
    }

    public function testReturnsZeroWhenNoSetsAreAvailable(): void
    {
        $handler = new Handler($this->inMemoryTCGdexProvider, $this->messageBus, ['en']);

        $output = ($handler)(new Input());

        self::assertSame(0, $output->dispatched);
        self::assertCount(0, $this->inMemoryTransport->getSent());
    }

    public function testQueriesTheFirstConfiguredLanguageForTheSetList(): void
    {
        // Set exists only in 'fr', not in 'en'
        $this->inMemoryTCGdexProvider->register('promo-fr-only', 'fr', new TCGdexSet('promo-fr-only', []));

        // First configured language is 'en' → handler queries 'en' → no sets found
        $handler = new Handler($this->inMemoryTCGdexProvider, $this->messageBus, ['en', 'fr']);
        $output = ($handler)(new Input());

        self::assertSame(0, $output->dispatched);

        // First configured language is 'fr' → handler queries 'fr' → 1 set found
        $handler = new Handler($this->inMemoryTCGdexProvider, $this->messageBus, ['fr', 'en']);
        $output = ($handler)(new Input());

        self::assertSame(1, $output->dispatched);
    }
}
