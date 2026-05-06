<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\CardRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * Catalogue entry mirroring a Pokémon TCG card from TCGdex. Read-only from
 * the application's perspective: only updated by the catalogue synchroniser.
 *
 * Functional identity: (set, numberInSet, variant, language). See CONTEXT.md.
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
)]
class Card
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\Column(name: 'set_id', length: 64)]
    private string $set;

    #[ORM\Column(length: 32)]
    private string $numberInSet;

    #[ORM\Column(length: 64)]
    private string $variant;

    #[ORM\Column(length: 8)]
    private string $language;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(length: 64)]
    private string $rarity;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $imageUrl = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct(
        string $set,
        string $numberInSet,
        string $variant,
        string $language,
        string $name,
        string $rarity,
        ?string $imageUrl = null,
        ?Uuid $id = null,
    ) {
        $this->id = $id ?? Uuid::v7();
        $this->set = $set;
        $this->numberInSet = $numberInSet;
        $this->variant = $variant;
        $this->language = $language;
        $this->name = $name;
        $this->rarity = $rarity;
        $this->imageUrl = $imageUrl;
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getSet(): string
    {
        return $this->set;
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

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
