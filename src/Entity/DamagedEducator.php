<?php

namespace App\Entity;

use App\Repository\DamagedEducatorRepository;
use App\Validator as CustomAssert;
use App\Validator\InvalidAccountNumber;
use App\Validator\Mod97;
use App\Validator\MonthlyLimit;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: DamagedEducatorRepository::class)]
#[ORM\Index(name: 'idx_period', columns: ['period_id', 'school_id', 'account_number'])]
#[ORM\Index(name: 'idx_create_transaction', columns: ['period_id', 'status'])]
#[CustomAssert\DuplicateDamagedEducator]
#[MonthlyLimit]
#[ORM\HasLifecycleCallbacks]
class DamagedEducator
{
    public const MONTHLY_LIMIT = 120000;
    public const STATUS_NEW = 1;
    public const STATUS_DELETED = 2;

    public const STATUS = [
        self::STATUS_NEW => 'DamagedEducatorNew',
        self::STATUS_DELETED => 'DamagedEducatorDeleted',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\NotBlank(message: 'Ime je obavezno polje')]
    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'damagedEducators')]
    #[ORM\JoinColumn(nullable: false)]
    private ?School $school = null;

    #[Assert\NotBlank(message: 'Cifra je obavezno polje')]
    #[ORM\Column]
    private ?int $amount = null;

    #[Assert\NotBlank(message: 'Broj računa je obavezno polje')]
    #[ORM\Column(length: 50)]
    #[Mod97]
    #[InvalidAccountNumber]
    private ?string $accountNumber = null;

    #[ORM\Column]
    private ?int $status = self::STATUS_NEW;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $statusComment = null;

    #[ORM\ManyToOne(inversedBy: 'damagedEducators')]
    #[ORM\JoinColumn]
    private ?User $createdBy = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    /**
     * @var Collection<int, Transaction>
     */
    #[ORM\OneToMany(targetEntity: Transaction::class, mappedBy: 'damagedEducator')]
    private Collection $transactions;

    #[ORM\ManyToOne(inversedBy: 'damagedEducators')]
    #[ORM\JoinColumn(nullable: false)]
    private ?DamagedEducatorPeriod $period = null;

    #[Assert\NotBlank(message: 'Grad je obavezno polje')]
    #[ORM\ManyToOne]
    private ?City $city = null;

    public function __construct()
    {
        $this->transactions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getSchool(): ?School
    {
        return $this->school;
    }

    public function setSchool(?School $school): static
    {
        $this->school = $school;

        return $this;
    }

    public function getAmount(): ?int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function getAccountNumber(): ?string
    {
        return $this->accountNumber;
    }

    public function setAccountNumber(string $accountNumber): static
    {
        $this->accountNumber = $accountNumber;

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getStatusComment(): ?string
    {
        return $this->statusComment;
    }

    public function setStatusComment(?string $statusComment): static
    {
        $this->statusComment = $statusComment;

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): static
    {
        $this->createdBy = $createdBy;

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
     * @return Collection<int, Transaction>
     */
    public function getTransactions(): Collection
    {
        return $this->transactions;
    }

    public function getPeriod(): ?DamagedEducatorPeriod
    {
        return $this->period;
    }

    public function setPeriod(?DamagedEducatorPeriod $period): static
    {
        $this->period = $period;

        return $this;
    }

    public function allowToViewTransactions(): bool
    {
        return false === $this->getPeriod()->isActive();
    }

    public function allowToEdit(): bool
    {
        if (!$this->getPeriod()->isActive()) {
            return false;
        }

        return self::STATUS_DELETED !== $this->status;
    }

    public function allowToDelete(): bool
    {
        return self::STATUS_DELETED !== $this->status;
    }

    public function getCity(): ?City
    {
        return $this->city;
    }

    public function setCity(?City $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function isStatusDeleted(): bool
    {
        return self::STATUS_DELETED === $this->status;
    }
}
