<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\SerieRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Top-level grouping of TCGdex sets (e.g. "Sword & Shield", "Base").
 * `id` is the TCGdex serie code (stable across languages).
 */
#[ORM\Entity(repositoryClass: SerieRepository::class)]
#[ORM\Table(name: 'serie')]
class Serie
{
    #[ORM\Column(length: 500, nullable: true)]
    private ?string $logo = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $releaseDate = null;

    /**
     * @var Collection<int, SerieTranslation>
     */
    #[ORM\OneToMany(targetEntity: SerieTranslation::class, mappedBy: 'serie', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $translations;

    public function __construct(#[ORM\Id]
        #[ORM\Column(length: 64)]
        private string $id)
    {
        $this->translations = new ArrayCollection();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(?string $logo): void
    {
        $this->logo = $logo;
    }

    public function getReleaseDate(): ?DateTimeImmutable
    {
        return $this->releaseDate;
    }

    public function setReleaseDate(?DateTimeImmutable $releaseDate): void
    {
        $this->releaseDate = $releaseDate;
    }

    /**
     * @return Collection<int, SerieTranslation>
     */
    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    public function getTranslation(string $language): ?SerieTranslation
    {
        foreach ($this->translations as $translation) {
            if ($translation->getLanguage() === $language) {
                return $translation;
            }
        }

        return null;
    }

    public function upsertTranslation(string $language, string $name): SerieTranslation
    {
        $translation = $this->getTranslation($language);
        if ($translation instanceof SerieTranslation) {
            $translation->setName($name);

            return $translation;
        }

        $translation = new SerieTranslation($this, $language, $name);
        $this->translations->add($translation);

        return $translation;
    }
}
