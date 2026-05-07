<?php

declare(strict_types=1);

namespace App\Api\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Card;
use App\Entity\Rarity;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use function sprintf;
use function strlen;

/**
 * Adds `?pokemonSet={iri}` and `?serie={iri}` filters on the Rarity
 * collection: only returns rarities used by at least one Card in the
 * requested scope. Used by the catalog filters UI to hide rarity
 * options that would yield zero results for the selected serie + set.
 *
 * If both params are provided, `pokemonSet` wins (more specific scope).
 */
final readonly class RarityCardScopeExtension implements QueryCollectionExtensionInterface
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
        if (Rarity::class !== $resourceClass) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            return;
        }

        $setId = $this->extractId($request->query->get('pokemonSet'), '/api/pokemon_sets/');
        $serieId = $this->extractId($request->query->get('serie'), '/api/series/');

        if (null === $setId && null === $serieId) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0] ?? 'o';

        if (null !== $setId) {
            $param = $queryNameGenerator->generateParameterName('rarityScopeSet');
            $queryBuilder
                ->andWhere(sprintf(
                    'EXISTS (SELECT 1 FROM %s rsc WHERE rsc.rarity = %s AND rsc.pokemonSet = :%s)',
                    Card::class,
                    $alias,
                    $param,
                ))
                ->setParameter($param, $setId);

            return;
        }

        $param = $queryNameGenerator->generateParameterName('rarityScopeSerie');
        $queryBuilder
            ->andWhere(sprintf(
                'EXISTS (SELECT 1 FROM %s rsc INNER JOIN rsc.pokemonSet rsps WHERE rsc.rarity = %s AND rsps.serie = :%s)',
                Card::class,
                $alias,
                $param,
            ))
            ->setParameter($param, $serieId);
    }

    private function extractId(?string $value, string $prefix): ?string
    {
        if (null === $value || '' === $value) {
            return null;
        }

        return str_starts_with($value, $prefix) ? substr($value, strlen($prefix)) : $value;
    }
}
