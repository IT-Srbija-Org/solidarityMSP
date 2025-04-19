<?php

namespace App\Entity;

use App\Repository\LogEntityChangeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LogEntityChangeRepository::class)]
#[ORM\Index(name: 'idx_action', columns: ['action'])]
#[ORM\Index(name: 'idx_user', columns: ['changed_by_user_id'])]
#[ORM\HasLifecycleCallbacks]
class LogEntityChange
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $action = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $entityName = null;

    #[ORM\Column(nullable: true)]
    private ?int $entityId = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\ManyToOne]
    private ?User $changedByUser = null;

    #[ORM\Column(nullable: true)]
    private ?array $changes = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(string $action): static
    {
        $this->action = $action;

        return $this;
    }

    public function getEntityName(): ?string
    {
        return $this->entityName;
    }

    public function setEntityName(string $entityName): static
    {
        $this->entityName = $entityName;

        return $this;
    }

    public function getEntityId(): ?int
    {
        return $this->entityId;
    }

    public function setEntityId(?int $entityId): static
    {
        $this->entityId = $entityId;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    #[ORM\PrePersist]
    public function setCreatedAt(): static
    {
        $this->createdAt = new \DateTime();

        return $this;
    }

    public function getChangedByUser(): ?User
    {
        return $this->changedByUser;
    }

    public function setChangedByUser(?User $changedByUser): static
    {
        $this->changedByUser = $changedByUser;

        return $this;
    }

    public function getChanges(): ?array
    {
        return $this->changes;
    }

    public function setChanges(?array $changes): static
    {
        $this->changes = $changes;

        return $this;
    }
}
