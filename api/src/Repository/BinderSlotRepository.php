<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Binder;
use App\Entity\BinderSlot;
use App\Entity\OwnedCard;
use App\Service\Binder\BinderSlotLookupInterface;
use App\Service\Binder\BinderSlotPosition;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BinderSlot>
 */
final class BinderSlotRepository extends ServiceEntityRepository implements BinderSlotLookupInterface
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
}
