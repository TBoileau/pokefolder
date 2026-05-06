<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Binder;
use App\Entity\BinderSlot;
use App\Entity\OwnedCard;
use App\Service\Binder\BinderInventoryInterface;
use App\Service\Binder\BinderSlotLookupInterface;
use App\Service\Binder\BinderSlotPosition;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BinderSlot>
 */
final class BinderSlotRepository extends ServiceEntityRepository implements BinderSlotLookupInterface, BinderInventoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BinderSlot::class);
    }

    public function findByOwnedCard(OwnedCard $ownedCard): ?BinderSlot
    {
        return $this->findOneBy(['ownedCard' => $ownedCard]);
    }

    public function findByPosition(Binder $binder, BinderSlotPosition $position): ?BinderSlot
    {
        return $this->findOneBy([
            'binder' => $binder,
            'pageNumber' => $position->pageNumber,
            'face' => $position->face,
            'row' => $position->row,
            'col' => $position->col,
        ]);
    }

    public function setIdsInBinder(Binder $binder): array
    {
        /** @var list<array{setId: string}> $rows */
        $rows = $this->createQueryBuilder('s')
            ->select('DISTINCT c.setId AS setId')
            ->innerJoin('s.ownedCard', 'oc')
            ->innerJoin('oc.card', 'c')
            ->where('s.binder = :binder')
            ->setParameter('binder', $binder)
            ->getQuery()
            ->getArrayResult();

        return array_map(static fn (array $row): string => $row['setId'], $rows);
    }

    public function occupiedCountFor(Binder $binder): int
    {
        /** @var int|string $count */
        $count = $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.binder = :binder')
            ->andWhere('s.ownedCard IS NOT NULL')
            ->setParameter('binder', $binder)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $count;
    }
}
