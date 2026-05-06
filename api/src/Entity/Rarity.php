<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\RarityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * Card rarity, deduplicated by a stable slug across languages.
 * The human label lives in `translations`.
 */
#[ORM\Entity(repositoryClass: RarityRepository::class)]
#[ORM\Table(name: 'rarity')]
#[ApiResource(
    operations: [new GetCollection(), new Get()],
    normalizationContext: ['groups' => ['rarity:read']],
    paginationItemsPerPage: 200,
)]
#[ApiFilter(OrderFilter::class, properties: ['code'])]
class Rarity
{
    /**
     * @var Collection<int, RarityTranslation>
     */
    #[Groups(['rarity:read', 'card:read'])]
    #[ORM\OneToMany(targetEntity: RarityTranslation::class, mappedBy: 'rarity', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $translations;

    public function __construct(
        #[Groups(['rarity:read', 'card:read'])]
        #[ORM\Id]
        #[ORM\Column(length: 64)]
        private string $code,
    ) {
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
