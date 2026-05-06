<?php

declare(strict_types=1);

namespace App\Api\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\BinderSlot;
use App\Entity\OwnedCard;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\RequestStack;

use function sprintf;

/**
 * Adds the `?placed=true|false` filter to the OwnedCard collection
 * endpoint. `placed=false` returns OwnedCards that are not in any slot —
 * the drag source for binder placement (slice #25).
 */
final readonly class PlacedFilterExtension implements QueryCollectionExtensionInterface
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
        if (OwnedCard::class !== $resourceClass) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof \Symfony\Component\HttpFoundation\Request) {
            return;
        }

        $placed = $request->query->get('placed');
        if (null === $placed) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0] ?? 'o';
        $slotAlias = $queryNameGenerator->generateJoinAlias('placedSlot');

        $queryBuilder->leftJoin(
            BinderSlot::class,
            $slotAlias,
            'WITH',
            sprintf('%s.ownedCard = %s', $slotAlias, $alias),
        );

        if ('false' === $placed) {
            $queryBuilder->andWhere(sprintf('%s.id IS NULL', $slotAlias));
        } elseif ('true' === $placed) {
            $queryBuilder->andWhere(sprintf('%s.id IS NOT NULL', $slotAlias));
        }
    }
}
