<?php

namespace App\Entity;

use App\Repository\UserDonorRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserDonorRepository::class)]
#[ORM\Index(name: 'idx_amount', columns: ['amount'])]
#[ORM\HasLifecycleCallbacks]
class UserDonor
{
    public const COMES_FROM_TV = 1;
    public const COMES_FROM_SOCIAL = 2;
    public const COMES_FROM_FAMILY = 3;
    public const COMES_FROM_NEWS = 4;
    public const COMES_FROM_SCHOOL = 5;

    public const COMES_FROM = [
        self::COMES_FROM_TV => 'UserDonorComesFromTV',
        self::COMES_FROM_SOCIAL => 'UserDonorComesFromSocial',
        self::COMES_FROM_FAMILY => 'UserDonorComesFromFamily',
        self::COMES_FROM_NEWS => 'UserDonorComesFromNews',
        self::COMES_FROM_SCHOOL => 'UserDonorComesFromSchool',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'userDonor')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column]
    private ?bool $isMonthly = null;

    #[ORM\Column]
    private ?int $amount = null;

    #[ORM\Column(nullable: true)]
    private ?int $comesFrom = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $comment = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function isMonthly(): ?bool
    {
        return $this->isMonthly;
    }

    public function setIsMonthly(bool $isMonthly): static
    {
        $this->isMonthly = $isMonthly;

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

    public function getComesFrom(): ?int
    {
        return $this->comesFrom;
    }

    public function setComesFrom(?int $comesFrom): static
    {
        $this->comesFrom = $comesFrom;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): static
    {
        $this->comment = $comment;

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
}
