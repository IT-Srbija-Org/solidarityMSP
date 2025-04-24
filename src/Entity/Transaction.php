<?php

namespace App\Entity;

use App\Repository\TransactionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TransactionRepository::class)]
#[ORM\Table(name: '`transaction`')]
#[ORM\Index(name: 'idx_status', columns: ['status', 'created_at', 'id'])]
#[ORM\HasLifecycleCallbacks]
class Transaction
{
    public const STATUS_NEW = 1;
    public const STATUS_WAITING_CONFIRMATION = 2;
    public const STATUS_CONFIRMED = 3;
    public const STATUS_CANCELLED = 4;

    public const STATUS = [
        self::STATUS_NEW => 'WaitingPayment',
        self::STATUS_WAITING_CONFIRMATION => 'WaitingConfirmation',
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

    public function isStatusCancelled(): bool
    {
        return self::STATUS_CANCELLED === $this->status;
    }

    public function allowToChangeStatus(): bool
    {
        if (self::STATUS_WAITING_CONFIRMATION == $this->getStatus()) {
            return true;
        }

        if ($this->getUpdatedAt()->diff(new \DateTime())->days < 10) {
            return true;
        }

        return false;
    }
}
