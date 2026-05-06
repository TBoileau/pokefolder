<?php

declare(strict_types=1);

namespace App\Tests\UseCase\Catalog\SyncCards;

use App\Entity\PokemonSet;
use App\Entity\Rarity;
use App\Entity\Serie;
use App\Exception\Catalog\SetNotFoundException;
use App\Repository\CardRepository;
use App\Repository\PokemonSetRepository;
use App\Repository\RarityRepository;
use App\Service\Catalog\DTO\TCGdexCard;
use App\Service\Catalog\DTO\TCGdexCardResume;
use App\Service\Catalog\DTO\TCGdexSetDetail;
use App\Tests\Service\Catalog\Provider\InMemoryTCGdexProvider;
use App\UseCase\Catalog\SyncCards\Handler;
use App\UseCase\Catalog\SyncCards\Input;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

use function count;

final class HandlerTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    private InMemoryTCGdexProvider $provider;

    private Handler $handler;

    private PokemonSet $set;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $this->em = $container->get(EntityManagerInterface::class);
        $this->provider = new InMemoryTCGdexProvider();
        $this->handler = new Handler(
            tcGdexProvider: $this->provider,
            entityManager: $this->em,
            pokemonSetRepository: $container->get(PokemonSetRepository::class),
            cardRepository: $container->get(CardRepository::class),
            rarityRepository: $container->get(RarityRepository::class),
        );

        $serie = new Serie('base');
        $serie->upsertTranslation('en', 'Base');

        $this->em->persist($serie);

        $this->set = new PokemonSet('base1', $serie);
        $this->set->upsertTranslation('en', 'Base Set');

        $this->em->persist($this->set);
        $this->em->flush();
    }

    public function testCreatesOneCardPerActiveVariant(): void
    {
        $this->provider->registerSet('base1', 'en', $this->fakeSet([
            new TCGdexCardResume(id: 'base1-4', localId: '4', name: 'Charizard'),
        ]));
        $this->provider->registerCard('base1', '4', 'en', new TCGdexCard(
            id: 'base1-4',
            localId: '4',
            name: 'Charizard',
            rarity: 'Rare Holo',
            imageUrl: 'https://example.test/charizard',
            activeVariants: ['normal', 'holo'],
        ));

        ($this->handler)(new Input('base1', 'en'));

        $cards = $this->em->getRepository(\App\Entity\Card::class)->findAll();
        self::assertCount(2, $cards);

        $rarities = $this->em->getRepository(Rarity::class)->findAll();
        self::assertCount(1, $rarities);
        self::assertSame('rare-holo', $rarities[0]->getCode());
    }

    public function testIsIdempotentByDefault(): void
    {
        $this->provider->registerSet('base1', 'en', $this->fakeSet([
            new TCGdexCardResume(id: 'base1-4', localId: '4', name: 'Charizard'),
        ]));
        $this->provider->registerCard('base1', '4', 'en', new TCGdexCard(
            id: 'base1-4',
            localId: '4',
            name: 'Charizard',
            rarity: 'Rare Holo',
            imageUrl: 'https://example.test/charizard',
            activeVariants: ['normal'],
        ));

        ($this->handler)(new Input('base1', 'en'));
        $this->em->clear();

        // Replace fixture to detect the skip-if-exists fast-path: if the
        // handler refetched, name would change.
        $this->provider->registerCard('base1', '4', 'en', new TCGdexCard(
            id: 'base1-4',
            localId: '4',
            name: 'CHANGED NAME',
            rarity: 'Common',
            imageUrl: 'https://example.test/changed',
            activeVariants: ['normal'],
        ));
        ($this->handler)(new Input('base1', 'en'));

        $cards = $this->em->getRepository(\App\Entity\Card::class)->findAll();
        self::assertCount(1, $cards);
        self::assertSame('Charizard', $cards[0]->getName());
    }

    public function testForcePropagatesUpstreamChanges(): void
    {
        $this->provider->registerSet('base1', 'en', $this->fakeSet([
            new TCGdexCardResume(id: 'base1-4', localId: '4', name: 'Charizard'),
        ]));
        $this->provider->registerCard('base1', '4', 'en', new TCGdexCard(
            id: 'base1-4',
            localId: '4',
            name: 'Charizard',
            rarity: 'Rare Holo',
            imageUrl: 'https://example.test/charizard',
            activeVariants: ['normal'],
        ));

        ($this->handler)(new Input('base1', 'en'));
        $this->em->clear();

        $this->provider->registerCard('base1', '4', 'en', new TCGdexCard(
            id: 'base1-4',
            localId: '4',
            name: 'Charizard v2',
            rarity: 'Rare Holo',
            imageUrl: 'https://example.test/charizard-v2',
            activeVariants: ['normal'],
        ));
        ($this->handler)(new Input('base1', 'en', force: true));

        $cards = $this->em->getRepository(\App\Entity\Card::class)->findAll();
        self::assertCount(1, $cards);
        self::assertSame('Charizard v2', $cards[0]->getName());
        self::assertSame('https://example.test/charizard-v2', $cards[0]->getImageUrl());
    }

    public function testRaisesSetNotFoundWhenSetIsAbsent(): void
    {
        $this->expectException(SetNotFoundException::class);
        ($this->handler)(new Input('does-not-exist', 'en'));
    }

    /**
     * @param list<TCGdexCardResume> $cards
     */
    private function fakeSet(array $cards): TCGdexSetDetail
    {
        return new TCGdexSetDetail(
            id: 'base1',
            name: 'Base Set',
            serieId: 'base',
            logo: null,
            symbol: null,
            releaseDate: null,
            cardCountTotal: count($cards),
            cardCountOfficial: count($cards),
            legalStandard: false,
            legalExpanded: false,
            tcgOnlineId: null,
            abbreviationOfficial: null,
            abbreviationNormal: null,
            cards: $cards,
        );
    }
}
