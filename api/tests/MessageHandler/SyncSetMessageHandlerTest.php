<?php

declare(strict_types=1);

namespace App\Tests\MessageHandler;

use App\Catalog\CatalogSynchronizer;
use App\Catalog\DTO\TCGdexCard;
use App\Catalog\DTO\TCGdexSet;
use App\Message\SyncSetMessage;
use App\MessageHandler\SyncSetMessageHandler;
use App\Repository\CardRepository;
use App\Tests\Catalog\InMemoryTCGdexProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class SyncSetMessageHandlerTest extends KernelTestCase
{
    public function testHandlerDelegatesToCatalogSynchronizer(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $cards = $container->get(CardRepository::class);

        $provider = new InMemoryTCGdexProvider();
        $provider->register('base1', 'en', new TCGdexSet('base1', [
            new TCGdexCard('4', 'Charizard', 'Rare Holo', null, ['normal']),
        ]));

        $synchronizer = new CatalogSynchronizer($provider, $em, $cards, ['en', 'fr']);
        $handler = new SyncSetMessageHandler($synchronizer);

        $handler(new SyncSetMessage('base1'));

        self::assertCount(1, $cards->findAll());
        self::assertNotNull($cards->findByFunctionalIdentity('base1', '4', 'normal', 'en'));
    }
}
