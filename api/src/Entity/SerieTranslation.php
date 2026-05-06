<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'serie_translation')]
#[ORM\UniqueConstraint(name: 'uniq_serie_translation_lang', columns: ['serie_id', 'language'])]
class SerieTranslation
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    public function __construct(
        #[ORM\ManyToOne(targetEntity: Serie::class, inversedBy: 'translations')]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        private Serie $serie,
        #[Groups(['serie:read', 'set:read', 'card:read'])]
        #[ORM\Column(length: 8)]
        private string $language,
        #[Groups(['serie:read', 'set:read', 'card:read'])]
        #[ORM\Column(length: 255)]
        private string $name,
    ) {
        $this->id = Uuid::v7();
    }

    public function getSerie(): Serie
    {
        return $this->serie;
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
