<?php

declare(strict_types=1);

namespace App\Tests\Catalog;

use App\Catalog\CatalogSynchronizer;
use App\Catalog\DTO\TCGdexCard;
use App\Catalog\DTO\TCGdexSet;
use App\Catalog\SetNotFoundException;
use App\Repository\CardRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class CatalogSynchronizerTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private CardRepository $cards;
    private InMemoryTCGdexProvider $provider;
    private CatalogSynchronizer $synchronizer;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $this->em = $container->get(EntityManagerInterface::class);
        $this->cards = $container->get(CardRepository::class);
        $this->provider = new InMemoryTCGdexProvider();
        $this->synchronizer = new CatalogSynchronizer(
            provider: $this->provider,
            em: $this->em,
            cards: $this->cards,
            languages: ['en', 'fr'],
        );
    }

    public function testCreatesOneRowPerActiveVariantPerLanguage(): void
    {
        $this->provider->register('base1', 'en', new TCGdexSet('base1', [
            new TCGdexCard(
                localId: '4',
                name: 'Charizard',
                rarity: 'Rare Holo',
                imageUrl: 'https://example.test/en/4',
                activeVariants: ['normal', 'holo'],
            ),
        ]));
        $this->provider->register('base1', 'fr', new TCGdexSet('base1', [
            new TCGdexCard(
                localId: '4',
                name: 'Dracaufeu',
                rarity: 'Rare Holo',
                imageUrl: 'https://example.test/fr/4',
                activeVariants: ['normal', 'holo'],
            ),
        ]));

        $report = $this->synchronizer->syncSet('base1');

        self::assertSame(4, $report->created);
        self::assertSame(0, $report->updated);
        self::assertSame(0, $report->unchanged);
        self::assertCount(4, $this->cards->findAll());
    }

    public function testIsIdempotent(): void
    {
        $this->provider->register('base1', 'en', new TCGdexSet('base1', [
            new TCGdexCard('4', 'Charizard', 'Rare Holo', null, ['normal']),
        ]));

        $first = $this->synchronizer->syncSet('base1');
        $this->em->clear();
        $second = $this->synchronizer->syncSet('base1');

        self::assertSame(1, $first->created);
        self::assertSame(0, $second->created);
        self::assertSame(0, $second->updated);
        self::assertSame(1, $second->unchanged);
        self::assertCount(1, $this->cards->findAll());
    }

    public function testPropagatesUpstreamChangesToExistingRows(): void
    {
        $this->provider->register('base1', 'en', new TCGdexSet('base1', [
            new TCGdexCard('4', 'Charizard', 'Rare', null, ['normal']),
        ]));
        $this->synchronizer->syncSet('base1');
        $this->em->clear();

        $this->provider->register('base1', 'en', new TCGdexSet('base1', [
            new TCGdexCard('4', 'Charizard', 'Rare Holo', 'https://example.test/v2', ['normal']),
        ]));
        $report = $this->synchronizer->syncSet('base1');

        self::assertSame(0, $report->created);
        self::assertSame(1, $report->updated);

        $entity = $this->cards->findByFunctionalIdentity('base1', '4', 'normal', 'en');
        self::assertNotNull($entity);
        self::assertSame('Rare Holo', $entity->getRarity());
        self::assertSame('https://example.test/v2', $entity->getImageUrl());
    }

    public function testSkipsLanguagesWhereSetIsMissing(): void
    {
        $this->provider->register('base1', 'en', new TCGdexSet('base1', [
            new TCGdexCard('4', 'Charizard', 'Rare Holo', null, ['normal']),
        ]));
        // 'fr' deliberately unregistered

        $report = $this->synchronizer->syncSet('base1');

        self::assertSame(1, $report->created);
        self::assertCount(1, $this->cards->findAll());
    }

    public function testRaisesSetNotFoundWhenAbsentInEveryLanguage(): void
    {
        $this->expectException(SetNotFoundException::class);
        $this->synchronizer->syncSet('does-not-exist');
    }
}
