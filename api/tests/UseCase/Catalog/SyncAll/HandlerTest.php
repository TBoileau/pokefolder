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
    private InMemoryTCGdexProvider $provider;
    private MessageBusInterface $bus;
    private InMemoryTransport $transport;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $this->provider = new InMemoryTCGdexProvider();
        $this->bus = $container->get(MessageBusInterface::class);
        /** @var InMemoryTransport $transport */
        $transport = $container->get('messenger.transport.async');
        $this->transport = $transport;
    }

    public function testDispatchesOneSyncSetInputPerSet(): void
    {
        $this->provider->register('base1', 'en', new TCGdexSet('base1', []));
        $this->provider->register('jungle', 'en', new TCGdexSet('jungle', []));
        $this->provider->register('fossil', 'en', new TCGdexSet('fossil', []));

        $handler = new Handler($this->provider, $this->bus, ['en']);

        $output = ($handler)(new Input());

        self::assertSame(3, $output->dispatched);

        $sent = $this->transport->getSent();
        self::assertCount(3, $sent);
        $dispatchedSetIds = array_map(
            static fn ($envelope): string => $envelope->getMessage()->setId,
            $sent,
        );
        self::assertSame(['base1', 'jungle', 'fossil'], $dispatchedSetIds);
        foreach ($sent as $envelope) {
            self::assertInstanceOf(SyncSetInput::class, $envelope->getMessage());
        }
    }

    public function testReturnsZeroWhenNoSetsAreAvailable(): void
    {
        $handler = new Handler($this->provider, $this->bus, ['en']);

        $output = ($handler)(new Input());

        self::assertSame(0, $output->dispatched);
        self::assertCount(0, $this->transport->getSent());
    }

    public function testQueriesTheFirstConfiguredLanguageForTheSetList(): void
    {
        // Set exists only in 'fr', not in 'en'
        $this->provider->register('promo-fr-only', 'fr', new TCGdexSet('promo-fr-only', []));

        // First configured language is 'en' → handler queries 'en' → no sets found
        $handler = new Handler($this->provider, $this->bus, ['en', 'fr']);
        $output = ($handler)(new Input());

        self::assertSame(0, $output->dispatched);

        // First configured language is 'fr' → handler queries 'fr' → 1 set found
        $handler = new Handler($this->provider, $this->bus, ['fr', 'en']);
        $output = ($handler)(new Input());

        self::assertSame(1, $output->dispatched);
    }
}
