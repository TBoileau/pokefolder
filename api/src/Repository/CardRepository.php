<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Card;
use App\Entity\PokemonSet;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Card>
 */
final class CardRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Card::class);
    }

    public function findByFunctionalIdentity(
        PokemonSet $pokemonSet,
        string $numberInSet,
        string $variant,
        string $language,
    ): ?Card {
        return $this->findOneBy([
            'pokemonSet' => $pokemonSet,
            'numberInSet' => $numberInSet,
            'variant' => $variant,
            'language' => $language,
        ]);
    }
}
