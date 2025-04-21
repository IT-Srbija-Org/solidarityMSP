<?php

namespace App\Entity;

use App\Repository\DamagedEducatorPeriodRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DamagedEducatorPeriodRepository::class)]
#[ORM\Index(name: 'idx_search', columns: ['month', 'year', 'type'])]
#[ORM\HasLifecycleCallbacks]
class DamagedEducatorPeriod
{
    public const TYPE_FIRST_HALF = 'first-half';
    public const TYPE_SECOND_HALF = 'second-half';
    public const TYPE_FULL = 'full';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $month = null;

    #[ORM\Column]
    private ?int $year = null;

    #[ORM\Column(length: 30)]
    private ?string $type = self::TYPE_FULL;

    #[ORM\Column]
    private ?bool $active = true;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    /**
     * @var Collection<int, DamagedEducator>
     */
    #[ORM\OneToMany(targetEntity: DamagedEducator::class, mappedBy: 'period')]
    private Collection $damagedEducators;

    public function __construct()
    {
        $this->damagedEducators = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMonth(): ?int
    {
        return $this->month;
    }

    public function setMonth(int $month): static
    {
        $this->month = $month;

        return $this;
    }

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function setYear(int $year): static
    {
        $this->year = $year;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        $date = new \DateTime();
        $date->setDate($this->getYear(), $this->getMonth(), 1);

        return $date;
    }

    public function isActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;

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

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function setUpdatedAt(): static
    {
        $this->updatedAt = new \DateTime();

        return $this;
    }

    /**
     * @return Collection<int, DamagedEducator>
     */
    public function getDamagedEducators(): Collection
    {
        return $this->damagedEducators;
    }
}
