<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Enum\BinderSlotFace;
use App\Repository\BinderSlotRepository;
use App\Service\Binder\BinderSlotPosition;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

/**
 * One physical slot inside a Binder. Addressed by (binder, pageNumber,
 * face, row, col); may host at most one OwnedCard at a time. The two
 * uniqueness constraints below encode the placement invariants enforced
 * at the domain level by BinderPlacementService.
 */
#[ORM\Entity(repositoryClass: BinderSlotRepository::class)]
#[ORM\Table(name: 'binder_slot')]
#[ORM\HasLifecycleCallbacks]
#[ORM\UniqueConstraint(
    name: 'uniq_binder_slot_position',
    columns: ['binder_id', 'page_number', 'face', 'row_index', 'col_index'],
)]
#[ORM\UniqueConstraint(
    name: 'uniq_binder_slot_owned_card',
    columns: ['owned_card_id'],
)]
#[ApiResource(
    shortName: 'BinderSlot',
    operations: [
        new GetCollection(),
        new Get(),
    ],
    normalizationContext: ['groups' => ['binder_slot:read']],
    order: ['pageNumber' => 'ASC', 'face' => 'ASC', 'row' => 'ASC', 'col' => 'ASC'],
    paginationItemsPerPage: 200,
    paginationMaximumItemsPerPage: 500,
)]
#[ApiFilter(SearchFilter::class, properties: [
    'binder' => 'exact',
    'pageNumber' => 'exact',
    'face' => 'exact',
])]
#[ApiFilter(OrderFilter::class, properties: ['pageNumber', 'face', 'row', 'col'])]
class BinderSlot
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[Groups(['binder_slot:read'])]
    private Uuid $id;

    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    #[ORM\Column]
    private DateTimeImmutable $updatedAt;

    #[ORM\Column(name: 'page_number', type: Types::INTEGER)]
    #[Groups(['binder_slot:read'])]
    private int $pageNumber;

    #[ORM\Column(length: 8, enumType: BinderSlotFace::class)]
    #[Groups(['binder_slot:read'])]
    private BinderSlotFace $face;

    #[ORM\Column(name: 'row_index', type: Types::INTEGER)]
    #[Groups(['binder_slot:read'])]
    private int $row;

    #[ORM\Column(name: 'col_index', type: Types::INTEGER)]
    #[Groups(['binder_slot:read'])]
    private int $col;

    public function __construct(
        #[ORM\ManyToOne(targetEntity: Binder::class)]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        private Binder $binder,
        BinderSlotPosition $position,
        #[ORM\ManyToOne(targetEntity: OwnedCard::class)]
        #[ORM\JoinColumn(nullable: true, onDelete: 'RESTRICT')]
        #[Groups(['binder_slot:read'])]
        private ?OwnedCard $ownedCard = null,
        ?Uuid $id = null,
    ) {
        $this->id = $id ?? Uuid::v7();
        $this->pageNumber = $position->pageNumber;
        $this->face = $position->face;
        $this->row = $position->row;
        $this->col = $position->col;
        $now = new DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getBinder(): Binder
    {
        return $this->binder;
    }

    public function getPageNumber(): int
    {
        return $this->pageNumber;
    }

    public function getFace(): BinderSlotFace
    {
        return $this->face;
    }

    public function getRow(): int
    {
        return $this->row;
    }

    public function getCol(): int
    {
        return $this->col;
    }

    public function getPosition(): BinderSlotPosition
    {
        return new BinderSlotPosition($this->pageNumber, $this->face, $this->row, $this->col);
    }

    public function getOwnedCard(): ?OwnedCard
    {
        return $this->ownedCard;
    }

    public function setOwnedCard(?OwnedCard $ownedCard): void
    {
        $this->ownedCard = $ownedCard;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
