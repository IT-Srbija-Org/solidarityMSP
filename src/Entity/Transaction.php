<?php

namespace App\Entity;

use App\Repository\TransactionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TransactionRepository::class)]
#[ORM\Table(name: '`transaction`')]
#[ORM\Index(name: 'idx_status', columns: ['status', 'has_payment_proof_file', 'created_at', 'id'])]
#[ORM\HasLifecycleCallbacks]
class Transaction
{
    public const STATUS_NEW = 1;
    public const STATUS_VALIDATED = 2;
    public const STATUS_CONFIRMED = 3;
    public const STATUS_CANCELLED = 4;

    public const STATUS = [
        self::STATUS_NEW => 'New',
        self::STATUS_VALIDATED => 'Validated',
        self::STATUS_CONFIRMED => 'Confirmed',
        self::STATUS_CANCELLED => 'Cancelled',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'transactions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'transactions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?DamagedEducator $damagedEducator = null;

    #[ORM\Column(length: 50)]
    private ?string $accountNumber = null;

    #[ORM\Column]
    private ?int $amount = null;

    #[ORM\Column]
    private ?int $status = 1;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $statusComment = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $paymentProofFile = null;

    #[ORM\Column]
    private ?bool $hasPaymentProofFile = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getDamagedEducator(): ?DamagedEducator
    {
        return $this->damagedEducator;
    }

    public function setDamagedEducator(?DamagedEducator $damagedEducator): static
    {
        $this->damagedEducator = $damagedEducator;

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

    public function getAmount(): ?int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): static
    {
        $this->amount = $amount;

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

    public function getPaymentProofFile(): ?string
    {
        return $this->paymentProofFile;
    }

    public function setPaymentProofFile(?string $paymentProofFile): static
    {
        $this->paymentProofFile = $paymentProofFile;

        return $this;
    }

    public function hasPaymentProofFile(): ?bool
    {
        return $this->hasPaymentProofFile;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function setHasPaymentProofFile(): static
    {
        $this->hasPaymentProofFile = !empty($this->paymentProofFile);

        return $this;
    }
}
