<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'pokemon_set_translation')]
#[ORM\UniqueConstraint(name: 'uniq_set_translation_lang', columns: ['pokemon_set_id', 'language'])]
class PokemonSetTranslation
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    public function __construct(
        #[ORM\ManyToOne(targetEntity: PokemonSet::class, inversedBy: 'translations')]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        private PokemonSet $pokemonSet,
        #[ORM\Column(length: 8)]
        private string $language,
        #[ORM\Column(length: 255)]
        private string $name,
        #[ORM\Column(length: 32, nullable: true)]
        private ?string $abbreviationOfficial = null,
        #[ORM\Column(length: 32, nullable: true)]
        private ?string $abbreviationNormal = null,
    ) {
        $this->id = Uuid::v7();
    }

    public function getPokemonSet(): PokemonSet
    {
        return $this->pokemonSet;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getAbbreviationOfficial(): ?string
    {
        return $this->abbreviationOfficial;
    }

    public function setAbbreviationOfficial(?string $abbreviation): void
    {
        $this->abbreviationOfficial = $abbreviation;
    }

    public function getAbbreviationNormal(): ?string
    {
        return $this->abbreviationNormal;
    }

    public function setAbbreviationNormal(?string $abbreviation): void
    {
        $this->abbreviationNormal = $abbreviation;
    }
}
