<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\RarityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Card rarity, deduplicated by a stable slug across languages.
 * The human label lives in `translations`.
 */
#[ORM\Entity(repositoryClass: RarityRepository::class)]
#[ORM\Table(name: 'rarity')]
class Rarity
{
    /**
     * @var Collection<int, RarityTranslation>
     */
    #[ORM\OneToMany(targetEntity: RarityTranslation::class, mappedBy: 'rarity', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $translations;

    public function __construct(#[ORM\Id]
        #[ORM\Column(length: 64)]
        private string $code)
    {
        $this->translations = new ArrayCollection();
    }

    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @return Collection<int, RarityTranslation>
     */
    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    public function getTranslation(string $language): ?RarityTranslation
    {
        foreach ($this->translations as $translation) {
            if ($translation->getLanguage() === $language) {
                return $translation;
            }
        }

        return null;
    }

    public function upsertTranslation(string $language, string $name): RarityTranslation
    {
        $translation = $this->getTranslation($language);
        if ($translation instanceof RarityTranslation) {
            $translation->setName($name);

            return $translation;
        }

        $translation = new RarityTranslation($this, $language, $name);
        $this->translations->add($translation);

        return $translation;
    }

    public static function slug(string $label): string
    {
        $slug = strtolower(trim($label));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
        $slug = trim($slug, '-');

        return '' === $slug ? 'unknown' : $slug;
    }
}
