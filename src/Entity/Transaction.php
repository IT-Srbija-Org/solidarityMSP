<?php

namespace App\Entity;

use App\Repository\TransactionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TransactionRepository::class)]
#[ORM\Table(name: '`transaction`')]
#[ORM\Index(name: 'idx_status', columns: ['status', 'created_at', 'id'])]
#[ORM\Index(name: 'idx_damaged_educator', columns: ['damaged_educator_id', 'status'])]
#[ORM\Index(name: 'idx_remaining_amount', columns: ['user_id', 'status', 'created_at'])]
#[ORM\Index(name: 'idx_user_total_amount', columns: ['user_id', 'account_number', 'status', 'created_at'])]
#[ORM\HasLifecycleCallbacks]
class Transaction
{
    public const STATUS_NEW = 1;
    public const STATUS_WAITING_CONFIRMATION = 2;
    public const STATUS_CONFIRMED = 3;
    public const STATUS_CANCELLED = 4;
    public const STATUS_NOT_PAID = 5;
    public const STATUS_EXPIRED = 6;

    public const STATUS = [
        self::STATUS_NEW => 'TransactionWaitingPayment',
        self::STATUS_WAITING_CONFIRMATION => 'TransactionWaitingConfirmation',
        self::STATUS_EXPIRED => 'TransactionExpired',
        self::STATUS_NOT_PAID => 'TransactionNotPaid',
        self::STATUS_CONFIRMED => 'TransactionConfirmed',
        self::STATUS_CANCELLED => 'TransactionCancelled',
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
    private ?int $status = self::STATUS_NEW;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $statusComment = null;

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

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function cleanStatusComment(): static
    {
        if (self::STATUS_CANCELLED != $this->getStatus()) {
            $this->setStatusComment(null);
        }

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

    public function allowConfirmPayment(): bool
    {
        if (self::STATUS_NEW == $this->getStatus()) {
            return true;
        }

        return false;
    }

    public function allowDeletePaymentConfirmation(): bool
    {
        if (self::STATUS_WAITING_CONFIRMATION == $this->getStatus()) {
            return true;
        }

        return false;
    }

    public function allowShowPrint(): bool
    {
        if (self::STATUS_NEW == $this->getStatus()) {
            return true;
        }

        return false;
    }

    public function allowShowQR(): bool
    {
        if (self::STATUS_NEW == $this->getStatus()) {
            return true;
        }

        return false;
    }

    public function isStatusWaitingConfirmation(): bool
    {
        return self::STATUS_WAITING_CONFIRMATION === $this->status;
    }

    public function isMaskInformation(): bool
    {
        if (in_array($this->getStatus(), [self::STATUS_CANCELLED, self::STATUS_EXPIRED, self::STATUS_NOT_PAID])) {
            return true;
        }

        return false;
    }

    public function allowToChangeStatus(): bool
    {
        if (in_array($this->getStatus(), [self::STATUS_EXPIRED, self::STATUS_WAITING_CONFIRMATION])) {
            return true;
        }

        if (in_array($this->getStatus(), [self::STATUS_CONFIRMED, self::STATUS_NOT_PAID]) && $this->getUpdatedAt()->diff(new \DateTime())->days < 7) {
            return true;
        }

        return false;
    }

    public function getReferenceCode(): int
    {
        return $this->getId();
    }
}
