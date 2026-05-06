<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Enum\Condition;
use App\Repository\OwnedCardRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * One physical copy of a Card the user owns. 1 row = 1 physical card.
 * See CONTEXT.md and ADR-0002 for the rationale (over a quantity field).
 */
#[ORM\Entity(repositoryClass: OwnedCardRepository::class)]
#[ORM\Table(name: 'owned_card')]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
        new Post(),
        new Patch(),
        new Delete(),
    ],
    normalizationContext: ['groups' => ['owned_card:read']],
    denormalizationContext: ['groups' => ['owned_card:write']],
    order: ['createdAt' => 'DESC'],
)]
#[ApiFilter(SearchFilter::class, properties: [
    'card' => 'exact',
    'card.setId' => 'exact',
    'card.language' => 'exact',
    'card.variant' => 'exact',
    'card.name' => 'ipartial',
    'condition' => 'exact',
])]
class OwnedCard
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[Groups(['owned_card:read'])]
    private Uuid $id;

    #[ORM\Column]
    #[Groups(['owned_card:read'])]
    private DateTimeImmutable $createdAt;

    #[ORM\Column]
    #[Groups(['owned_card:read'])]
    private DateTimeImmutable $updatedAt;

    public function __construct(
        #[ORM\ManyToOne(targetEntity: Card::class)]
        #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
        #[Groups(['owned_card:read', 'owned_card:write'])]
        #[Assert\NotNull]
        private Card $card,
        #[ORM\Column(length: 4, enumType: Condition::class)]
        #[Groups(['owned_card:read', 'owned_card:write'])]
        #[Assert\NotNull]
        private Condition $condition,
        ?Uuid $id = null,
    ) {
        $this->id = $id ?? Uuid::v7();
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

    public function getCard(): Card
    {
        return $this->card;
    }

    public function getCondition(): Condition
    {
        return $this->condition;
    }

    public function setCondition(Condition $condition): void
    {
        $this->condition = $condition;
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
