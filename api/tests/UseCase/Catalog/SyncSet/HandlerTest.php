<?php

declare(strict_types=1);

namespace App\Tests\UseCase\Catalog\SyncSet;

use App\Exception\Catalog\SetNotFoundException;
use App\Repository\CardRepository;
use App\Service\Catalog\DTO\TCGdexCard;
use App\Service\Catalog\DTO\TCGdexSet;
use App\Tests\Service\Catalog\Provider\InMemoryTCGdexProvider;
use App\UseCase\Catalog\SyncSet\Handler;
use App\UseCase\Catalog\SyncSet\Input;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class HandlerTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    private CardRepository $cardRepository;

    private InMemoryTCGdexProvider $inMemoryTCGdexProvider;

    private Handler $handler;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->cardRepository = $container->get(CardRepository::class);
        $this->inMemoryTCGdexProvider = new InMemoryTCGdexProvider();
        $this->handler = new Handler(
            tcGdexProvider: $this->inMemoryTCGdexProvider,
            entityManager: $this->entityManager,
            cardRepository: $this->cardRepository,
            languages: ['en', 'fr'],
        );
    }

    public function testCreatesOneRowPerActiveVariantPerLanguage(): void
    {
        $this->inMemoryTCGdexProvider->register('base1', 'en', new TCGdexSet('base1', [
            new TCGdexCard(
                localId: '4',
                name: 'Charizard',
                rarity: 'Rare Holo',
                imageUrl: 'https://example.test/en/4',
                activeVariants: ['normal', 'holo'],
            ),
        ]));
        $this->inMemoryTCGdexProvider->register('base1', 'fr', new TCGdexSet('base1', [
            new TCGdexCard(
                localId: '4',
                name: 'Dracaufeu',
                rarity: 'Rare Holo',
                imageUrl: 'https://example.test/fr/4',
                activeVariants: ['normal', 'holo'],
            ),
        ]));

        $output = ($this->handler)(new Input('base1'));

        self::assertSame(4, $output->created);
        self::assertSame(0, $output->updated);
        self::assertSame(0, $output->unchanged);
        self::assertCount(4, $this->cardRepository->findAll());
    }

    public function testIsIdempotent(): void
    {
        $this->inMemoryTCGdexProvider->register('base1', 'en', new TCGdexSet('base1', [
            new TCGdexCard('4', 'Charizard', 'Rare Holo', null, ['normal']),
        ]));

        $output = ($this->handler)(new Input('base1'));
        $this->entityManager->clear();
        $second = ($this->handler)(new Input('base1'));

        self::assertSame(1, $output->created);
        self::assertSame(0, $second->created);
        self::assertSame(0, $second->updated);
        self::assertSame(1, $second->unchanged);
        self::assertCount(1, $this->cardRepository->findAll());
    }

    public function testPropagatesUpstreamChangesToExistingRows(): void
    {
        $this->inMemoryTCGdexProvider->register('base1', 'en', new TCGdexSet('base1', [
            new TCGdexCard('4', 'Charizard', 'Rare', null, ['normal']),
        ]));
        ($this->handler)(new Input('base1'));
        $this->entityManager->clear();

        $this->inMemoryTCGdexProvider->register('base1', 'en', new TCGdexSet('base1', [
            new TCGdexCard('4', 'Charizard', 'Rare Holo', 'https://example.test/v2', ['normal']),
        ]));
        $output = ($this->handler)(new Input('base1'));

        self::assertSame(0, $output->created);
        self::assertSame(1, $output->updated);

        $entity = $this->cardRepository->findByFunctionalIdentity('base1', '4', 'normal', 'en');
        self::assertNotNull($entity);
        self::assertSame('Rare Holo', $entity->getRarity());
        self::assertSame('https://example.test/v2', $entity->getImageUrl());
    }

    public function testSkipsLanguagesWhereSetIsMissing(): void
    {
        $this->inMemoryTCGdexProvider->register('base1', 'en', new TCGdexSet('base1', [
            new TCGdexCard('4', 'Charizard', 'Rare Holo', null, ['normal']),
        ]));
        // 'fr' deliberately unregistered

        $output = ($this->handler)(new Input('base1'));

        self::assertSame(1, $output->created);
        self::assertCount(1, $this->cardRepository->findAll());
    }

    public function testRaisesSetNotFoundWhenAbsentInEveryLanguage(): void
    {
        $this->expectException(SetNotFoundException::class);
        ($this->handler)(new Input('does-not-exist'));
    }
}
