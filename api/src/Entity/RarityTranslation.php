<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'rarity_translation')]
#[ORM\UniqueConstraint(name: 'uniq_rarity_translation_lang', columns: ['rarity_code', 'language'])]
class RarityTranslation
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    public function __construct(
        #[ORM\ManyToOne(targetEntity: Rarity::class, inversedBy: 'translations')]
        #[ORM\JoinColumn(name: 'rarity_code', referencedColumnName: 'code', nullable: false, onDelete: 'CASCADE')]
        private Rarity $rarity,
        #[ORM\Column(length: 8)]
        private string $language,
        #[ORM\Column(length: 255)]
        private string $name,
    ) {
        $this->id = Uuid::v7();
    }

    public function getRarity(): Rarity
    {
        return $this->rarity;
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
}
