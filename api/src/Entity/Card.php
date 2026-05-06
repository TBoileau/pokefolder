<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\CardRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * Catalogue entry mirroring a Pokémon TCG card from TCGdex. Read-only from
 * the application's perspective: only updated by the catalogue synchroniser.
 *
 * Functional identity: (setId, numberInSet, variant, language). See CONTEXT.md.
 */
#[ORM\Entity(repositoryClass: CardRepository::class)]
#[ORM\Table(name: 'card')]
#[ORM\UniqueConstraint(
    name: 'card_functional_identity_uniq',
    columns: ['set_id', 'number_in_set', 'variant', 'language'],
)]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
    ],
    order: ['setId' => 'ASC', 'numberInSet' => 'ASC'],
)]
#[ApiFilter(SearchFilter::class, properties: [
    'setId' => 'exact',
    'language' => 'exact',
    'variant' => 'exact',
    'name' => 'ipartial',
    'numberInSet' => 'ipartial',
])]
#[ApiFilter(OrderFilter::class, properties: ['setId', 'numberInSet', 'name'])]
class Card
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    #[ORM\Column]
    private DateTimeImmutable $updatedAt;

    public function __construct(
        #[ORM\Column(name: 'set_id', length: 64)]
        private string $setId,
        #[ORM\Column(length: 32)]
        private string $numberInSet,
        #[ORM\Column(length: 64)]
        private string $variant,
        #[ORM\Column(length: 8)]
        private string $language,
        #[ORM\Column(length: 255)]
        private string $name,
        #[ORM\Column(length: 64)]
        private string $rarity,
        #[ORM\Column(length: 500, nullable: true)]
        private ?string $imageUrl = null,
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

    /**
     * Propagates upstream catalogue changes onto this row. Functional
     * identity (setId, numberInSet, variant, language) is intentionally
     * immutable — only descriptive fields can drift.
     *
     * Returns true if any field actually changed.
     */
    public function updateCatalogueData(string $name, string $rarity, ?string $imageUrl): bool
    {
        $changed = false;
        if ($this->name !== $name) {
            $this->name = $name;
            $changed = true;
        }

        if ($this->rarity !== $rarity) {
            $this->rarity = $rarity;
            $changed = true;
        }

        if ($this->imageUrl !== $imageUrl) {
            $this->imageUrl = $imageUrl;
            $changed = true;
        }

        return $changed;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getSetId(): string
    {
        return $this->setId;
    }

    public function getNumberInSet(): string
    {
        return $this->numberInSet;
    }

    public function getVariant(): string
    {
        return $this->variant;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getRarity(): string
    {
        return $this->rarity;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
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
