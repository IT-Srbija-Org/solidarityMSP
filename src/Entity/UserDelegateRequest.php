<?php

namespace App\Entity;

use App\Repository\UserDelegateRequestRepository;
use App\Validator\Phone;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserDelegateRequestRepository::class)]
#[ORM\HasLifecycleCallbacks]
class UserDelegateRequest
{
    public const STATUS_NEW = 1;
    public const STATUS_CONFIRMED = 2;
    public const STATUS_REJECTED = 3;

    public const STATUS = [
        self::STATUS_NEW => 'New',
        self::STATUS_CONFIRMED => 'Confirmed',
        self::STATUS_REJECTED => 'Rejected',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'userDelegateRequest')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 50)]
    #[Phone]
    private ?string $phone = null;

    #[ORM\ManyToOne(inversedBy: 'userDelegateRequests')]
    #[ORM\JoinColumn(nullable: false)]
    private ?SchoolType $schoolType = null;

    #[ORM\ManyToOne(inversedBy: 'userDelegateRequests')]
    #[ORM\JoinColumn(nullable: false)]
    private ?City $city = null;

    #[ORM\ManyToOne(inversedBy: 'userDelegateRequests')]
    #[ORM\JoinColumn(nullable: false)]
    private ?School $school = null;

    #[ORM\Column]
    #[Assert\LessThan(value: 1000, message: 'Ukupan broj zaposlenih u školi ne može da bude veći od 1000')]
    private ?int $totalEducators = null;

    #[ORM\Column]
    #[Assert\LessThan(propertyPath: 'totalEducators', message: 'Ukupno u obustavi ne može da bude veće od ukupnog broja zaposlenih')]
    private ?int $totalBlockedEducators = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $comment = null;

    #[ORM\Column]
    private ?int $status = 1;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $adminComment = null;

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

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getSchoolType(): ?SchoolType
    {
        return $this->schoolType;
    }

    public function setSchoolType(?SchoolType $schoolType): static
    {
        $this->schoolType = $schoolType;

        return $this;
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

    public function getSchool(): ?School
    {
        return $this->school;
    }

    public function setSchool(?School $school): static
    {
        $this->school = $school;

        return $this;
    }

    public function getTotalEducators(): ?int
    {
        return $this->totalEducators;
    }

    public function setTotalEducators(int $totalEducators): static
    {
        $this->totalEducators = $totalEducators;

        return $this;
    }

    public function getTotalBlockedEducators(): ?int
    {
        return $this->totalBlockedEducators;
    }

    public function setTotalBlockedEducators(int $totalBlockedEducators): static
    {
        $this->totalBlockedEducators = $totalBlockedEducators;

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

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $status): static
    {
        $this->status = $status;

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

    public function getAdminComment(): ?string
    {
        return $this->adminComment;
    }

    public function setAdminComment(?string $adminComment): static
    {
        $this->adminComment = $adminComment;

        return $this;
    }
}
