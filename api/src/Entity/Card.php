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
use Symfony\Component\Serializer\Attribute\Groups;
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
    columns: ['pokemon_set_id', 'number_in_set', 'variant', 'language'],
)]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
    ],
    normalizationContext: ['groups' => ['card:read']],
    order: ['numberInSet' => 'ASC'],
    paginationItemsPerPage: 1000,
)]
#[ApiFilter(SearchFilter::class, properties: [
    'pokemonSet' => 'exact',
    'pokemonSet.serie' => 'exact',
    'rarity' => 'exact',
    'language' => 'exact',
    'variant' => 'exact',
    'name' => 'ipartial',
    'numberInSet' => 'ipartial',
])]
#[ApiFilter(OrderFilter::class, properties: ['numberInSet', 'name'])]
class Card
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[Groups(['binder_slot:read', 'owned_card:read', 'card:read'])]
    private Uuid $id;

    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    #[ORM\Column]
    private DateTimeImmutable $updatedAt;

    public function __construct(
        #[ORM\ManyToOne(targetEntity: PokemonSet::class)]
        #[ORM\JoinColumn(name: 'pokemon_set_id', nullable: false, onDelete: 'CASCADE')]
        #[Groups(['binder_slot:read', 'owned_card:read', 'card:read'])]
        private PokemonSet $pokemonSet,
        #[ORM\Column(length: 32)]
        #[Groups(['binder_slot:read', 'owned_card:read', 'card:read'])]
        private string $numberInSet,
        #[ORM\Column(length: 64)]
        #[Groups(['binder_slot:read', 'owned_card:read', 'card:read'])]
        private string $variant,
        #[ORM\Column(length: 8)]
        #[Groups(['binder_slot:read', 'owned_card:read', 'card:read'])]
        private string $language,
        #[ORM\Column(length: 255)]
        #[Groups(['binder_slot:read', 'owned_card:read', 'card:read'])]
        private string $name,
        #[ORM\ManyToOne(targetEntity: Rarity::class)]
        #[ORM\JoinColumn(name: 'rarity_code', referencedColumnName: 'code', nullable: true, onDelete: 'SET NULL')]
        #[Groups(['binder_slot:read', 'owned_card:read', 'card:read'])]
        private ?Rarity $rarity = null,
        #[ORM\Column(length: 500, nullable: true)]
        #[Groups(['binder_slot:read', 'owned_card:read', 'card:read'])]
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
     * identity (set, numberInSet, variant, language) is intentionally
     * immutable — only descriptive fields can drift.
     *
     * Returns true if any field actually changed.
     */
    public function updateCatalogueData(string $name, ?Rarity $rarity, ?string $imageUrl): bool
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

    public function getPokemonSet(): PokemonSet
    {
        return $this->pokemonSet;
    }

    public function getSetId(): string
    {
        return $this->pokemonSet->getId();
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

    public function getRarity(): ?Rarity
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
