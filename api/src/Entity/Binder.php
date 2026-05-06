<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Repository\BinderRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A physical card binder owned by the user. Capacity is derived from
 * pageCount × cols × rows × (doubleSided ? 2 : 1). See CONTEXT.md.
 */
#[ORM\Entity(repositoryClass: BinderRepository::class)]
#[ORM\Table(name: 'binder')]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
        new Post(),
        new Patch(),
        new Delete(),
    ],
    normalizationContext: ['groups' => ['binder:read']],
    denormalizationContext: ['groups' => ['binder:write']],
    order: ['createdAt' => 'DESC'],
)]
#[ApiFilter(OrderFilter::class, properties: ['name', 'createdAt'])]
class Binder
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[Groups(['binder:read'])]
    private Uuid $id;

    #[ORM\Column]
    #[Groups(['binder:read'])]
    private DateTimeImmutable $createdAt;

    #[ORM\Column]
    #[Groups(['binder:read'])]
    private DateTimeImmutable $updatedAt;

    public function __construct(
        #[ORM\Column(length: 100)]
        #[Groups(['binder:read', 'binder:write'])]
        #[Assert\NotBlank]
        #[Assert\Length(min: 1, max: 100)]
        private string $name,
        #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER)]
        #[Groups(['binder:read', 'binder:write'])]
        #[Assert\Positive]
        private int $pageCount,
        #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER)]
        #[Groups(['binder:read', 'binder:write'])]
        #[Assert\Positive]
        private int $cols,
        #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER)]
        #[Groups(['binder:read', 'binder:write'])]
        #[Assert\Positive]
        private int $rows,
        #[ORM\Column(type: \Doctrine\DBAL\Types\Types::BOOLEAN, options: ['default' => true])]
        #[Groups(['binder:read', 'binder:write'])]
        private bool $doubleSided = true,
        #[ORM\Column(length: 500, nullable: true)]
        #[Groups(['binder:read', 'binder:write'])]
        private ?string $description = null,
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

    #[Groups(['binder:read'])]
    public function getCapacity(): int
    {
        return $this->pageCount * $this->cols * $this->rows * ($this->doubleSided ? 2 : 1);
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getPageCount(): int
    {
        return $this->pageCount;
    }

    public function setPageCount(int $pageCount): void
    {
        $this->pageCount = $pageCount;
    }

    public function getCols(): int
    {
        return $this->cols;
    }

    public function setCols(int $cols): void
    {
        $this->cols = $cols;
    }

    public function getRows(): int
    {
        return $this->rows;
    }

    public function setRows(int $rows): void
    {
        $this->rows = $rows;
    }

    public function isDoubleSided(): bool
    {
        return $this->doubleSided;
    }

    public function setDoubleSided(bool $doubleSided): void
    {
        $this->doubleSided = $doubleSided;
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
