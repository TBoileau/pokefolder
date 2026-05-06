<?php

declare(strict_types=1);

namespace App\Api\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Card;
use App\Entity\OwnedCard;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\RequestStack;

use function sprintf;

/**
 * Adds a multi-field full-text-ish search via `?search=foo` on:
 * - /api/cards (searches Card.name, Card.numberInSet, set/serie/rarity translations)
 * - /api/owned_cards (same fields, joined via OwnedCard.card)
 */
final readonly class CardSearchExtension implements QueryCollectionExtensionInterface
{
    public function __construct(private RequestStack $requestStack)
    {
    }

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = [],
    ): void {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof \Symfony\Component\HttpFoundation\Request) {
            return;
        }

        $term = trim($request->query->get('search', ''));
        if ('' === $term) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0] ?? 'o';

        if (Card::class === $resourceClass) {
            $cardAlias = $alias;
        } elseif (OwnedCard::class === $resourceClass) {
            $cardAlias = $queryNameGenerator->generateJoinAlias('searchCard');
            $queryBuilder->innerJoin(sprintf('%s.card', $alias), $cardAlias);
        } else {
            return;
        }

        $setAlias = $queryNameGenerator->generateJoinAlias('searchSet');
        $serieAlias = $queryNameGenerator->generateJoinAlias('searchSerie');
        $rarityAlias = $queryNameGenerator->generateJoinAlias('searchRarity');
        $setTransAlias = $queryNameGenerator->generateJoinAlias('searchSetTrans');
        $serieTransAlias = $queryNameGenerator->generateJoinAlias('searchSerieTrans');
        $rarityTransAlias = $queryNameGenerator->generateJoinAlias('searchRarityTrans');

        $queryBuilder
            ->leftJoin(sprintf('%s.pokemonSet', $cardAlias), $setAlias)
            ->leftJoin(sprintf('%s.serie', $setAlias), $serieAlias)
            ->leftJoin(sprintf('%s.rarity', $cardAlias), $rarityAlias)
            ->leftJoin(sprintf('%s.translations', $setAlias), $setTransAlias)
            ->leftJoin(sprintf('%s.translations', $serieAlias), $serieTransAlias)
            ->leftJoin(sprintf('%s.translations', $rarityAlias), $rarityTransAlias);

        $param = $queryNameGenerator->generateParameterName('searchTerm');
        $queryBuilder
            ->andWhere(sprintf(
                'LOWER(%1$s.name) LIKE :%7$s '
                .'OR LOWER(%1$s.numberInSet) LIKE :%7$s '
                .'OR LOWER(%2$s.name) LIKE :%7$s '
                .'OR LOWER(%3$s.name) LIKE :%7$s '
                .'OR LOWER(%4$s.name) LIKE :%7$s '
                .'OR LOWER(%5$s.id) LIKE :%7$s '
                .'OR LOWER(%6$s.id) LIKE :%7$s',
                $cardAlias,
                $setTransAlias,
                $serieTransAlias,
                $rarityTransAlias,
                $setAlias,
                $serieAlias,
                $param,
            ))
            ->setParameter($param, '%'.strtolower($term).'%');
    }
}
