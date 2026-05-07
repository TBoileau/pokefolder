<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\PokemonSetRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * A TCGdex set (e.g. "Base Set", "Sword & Shield Base").
 * Mapped table is `set` but PHP class is `PokemonSet` to avoid clashing
 * with reserved SQL keywords / Set the data structure.
 */
#[ORM\Entity(repositoryClass: PokemonSetRepository::class)]
#[ORM\Table(name: 'pokemon_set')]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(requirements: ['id' => '[A-Za-z0-9._-]+']),
    ],
    normalizationContext: ['groups' => ['set:read']],
    order: ['releaseDate' => 'DESC'],
    paginationItemsPerPage: 200,
)]
#[ApiFilter(SearchFilter::class, properties: ['serie' => 'exact'])]
#[ApiFilter(OrderFilter::class, properties: ['releaseDate', 'id'])]
class PokemonSet
{
    #[Groups(['set:read', 'card:read'])]
    #[ORM\Column(length: 500, nullable: true)]
    private ?string $logo = null;

    #[Groups(['set:read', 'card:read'])]
    #[ORM\Column(length: 500, nullable: true)]
    private ?string $symbol = null;

    #[Groups(['set:read'])]
    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $releaseDate = null;

    #[Groups(['set:read'])]
    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    private ?int $cardCountTotal = null;

    #[Groups(['set:read'])]
    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    private ?int $cardCountOfficial = null;

    #[Groups(['set:read'])]
    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::BOOLEAN, nullable: true)]
    private ?bool $legalStandard = null;

    #[Groups(['set:read'])]
    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::BOOLEAN, nullable: true)]
    private ?bool $legalExpanded = null;

    #[Groups(['set:read'])]
    #[ORM\Column(length: 64, nullable: true)]
    private ?string $tcgOnlineId = null;

    /**
     * @var Collection<int, PokemonSetTranslation>
     */
    #[Groups(['set:read', 'card:read'])]
    #[ORM\OneToMany(targetEntity: PokemonSetTranslation::class, mappedBy: 'pokemonSet', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $translations;

    public function __construct(
        #[Groups(['set:read', 'card:read'])]
        #[ORM\Id]
        #[ORM\Column(length: 64)]
        private string $id,
        #[Groups(['set:read'])]
        #[ORM\ManyToOne(targetEntity: Serie::class)]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        private Serie $serie,
    ) {
        $this->translations = new ArrayCollection();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getSerie(): Serie
    {
        return $this->serie;
    }

    public function setSerie(Serie $serie): void
    {
        $this->serie = $serie;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(?string $logo): void
    {
        $this->logo = $logo;
    }

    public function getSymbol(): ?string
    {
        return $this->symbol;
    }

    public function setSymbol(?string $symbol): void
    {
        $this->symbol = $symbol;
    }

    public function getReleaseDate(): ?DateTimeImmutable
    {
        return $this->releaseDate;
    }

    public function setReleaseDate(?DateTimeImmutable $releaseDate): void
    {
        $this->releaseDate = $releaseDate;
    }

    public function getCardCountTotal(): ?int
    {
        return $this->cardCountTotal;
    }

    public function setCardCountTotal(?int $count): void
    {
        $this->cardCountTotal = $count;
    }

    public function getCardCountOfficial(): ?int
    {
        return $this->cardCountOfficial;
    }

    public function setCardCountOfficial(?int $count): void
    {
        $this->cardCountOfficial = $count;
    }

    public function getLegalStandard(): ?bool
    {
        return $this->legalStandard;
    }

    public function setLegalStandard(?bool $legal): void
    {
        $this->legalStandard = $legal;
    }

    public function getLegalExpanded(): ?bool
    {
        return $this->legalExpanded;
    }

    public function setLegalExpanded(?bool $legal): void
    {
        $this->legalExpanded = $legal;
    }

    public function getTcgOnlineId(): ?string
    {
        return $this->tcgOnlineId;
    }

    public function setTcgOnlineId(?string $id): void
    {
        $this->tcgOnlineId = $id;
    }

    /**
     * @return Collection<int, PokemonSetTranslation>
     */
    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    public function getTranslation(string $language): ?PokemonSetTranslation
    {
        foreach ($this->translations as $translation) {
            if ($translation->getLanguage() === $language) {
                return $translation;
            }
        }

        return null;
    }

    public function upsertTranslation(
        string $language,
        string $name,
        ?string $abbreviationOfficial = null,
        ?string $abbreviationNormal = null,
    ): PokemonSetTranslation {
        $translation = $this->getTranslation($language);
        if ($translation instanceof PokemonSetTranslation) {
            $translation->setName($name);
            $translation->setAbbreviationOfficial($abbreviationOfficial);
            $translation->setAbbreviationNormal($abbreviationNormal);

            return $translation;
        }

        $translation = new PokemonSetTranslation(
            $this,
            $language,
            $name,
            $abbreviationOfficial,
            $abbreviationNormal,
        );
        $this->translations->add($translation);

        return $translation;
    }
}
