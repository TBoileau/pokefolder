<?php

declare(strict_types=1);

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Binder;
use App\Entity\BinderSlot;
use App\Entity\Card;
use App\Entity\OwnedCard;
use App\Enum\BinderSlotFace;
use App\Enum\Condition;
use App\Service\Binder\BinderSlotPosition;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

final class BinderSlotTest extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = true;

    public function testGetCollectionFilteredByBinderReturnsNestedOwnedCardAndCard(): void
    {
        $client = self::createClient();
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $binder = new Binder(
            name: 'Test binder',
            pageCount: 5,
            cols: 3,
            rows: 3,
            doubleSided: true,
        );
        $em->persist($binder);

        $card = new Card(
            setId: 'base1',
            numberInSet: '4',
            variant: 'normal',
            language: 'en',
            name: 'Charizard',
            rarity: 'Rare Holo',
            imageUrl: 'https://example.test/4',
        );
        $em->persist($card);

        $owned = new OwnedCard(card: $card, condition: Condition::NearMint);
        $em->persist($owned);

        $em->persist(new BinderSlot(
            $binder,
            new BinderSlotPosition(1, BinderSlotFace::Recto, 1, 1),
            $owned,
        ));
        $em->flush();

        $client->request('GET', '/api/binder_slots?binder=/api/binders/'.$binder->getId()->toRfc4122());

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertJsonContains([
            'totalItems' => 1,
            'member' => [
                [
                    '@type' => 'BinderSlot',
                    'pageNumber' => 1,
                    'face' => 'recto',
                    'row' => 1,
                    'col' => 1,
                    'ownedCard' => [
                        '@type' => 'OwnedCard',
                        'condition' => 'NM',
                        'card' => [
                            '@type' => 'Card',
                            'setId' => 'base1',
                            'numberInSet' => '4',
                            'name' => 'Charizard',
                            'imageUrl' => 'https://example.test/4',
                        ],
                    ],
                ],
            ],
        ]);
    }
}
