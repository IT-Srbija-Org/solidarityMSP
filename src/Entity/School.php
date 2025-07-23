<?php

namespace App\Entity;

use App\Repository\SchoolRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SchoolRepository::class)]
#[ORM\Index(name: 'idx_school_processing', columns: ['processing'])]
#[ORM\HasLifecycleCallbacks]
class School
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\NotBlank(message: 'Naziv je obavezno polje')]
    #[Assert\Length(max: 255, maxMessage: 'Naziv ne može biti duži od {{ limit }} karaktera')]
    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[Assert\NotBlank(message: 'Tip škole je obavezno polje')]
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?SchoolType $type = null;

    #[Assert\NotBlank(message: 'Grad je obavezno polje')]
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?City $city = null;

    #[ORM\Column]
    private ?bool $processing = true;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    /**
     * @var Collection<int, UserDelegateSchool>
     */
    #[ORM\OneToMany(targetEntity: UserDelegateSchool::class, mappedBy: 'school')]
    private Collection $userDelegateSchools;

    /**
     * @var Collection<int, DamagedEducator>
     */
    #[ORM\OneToMany(targetEntity: DamagedEducator::class, mappedBy: 'school')]
    private Collection $damagedEducators;

    /**
     * @var Collection<int, UserDelegateRequest>
     */
    #[ORM\OneToMany(targetEntity: UserDelegateRequest::class, mappedBy: 'school')]
    private Collection $userDelegateRequests;

    #[ORM\Column]
    private ?bool $havePayoutPriority = false;

    public function __construct()
    {
        $this->userDelegateSchools = new ArrayCollection();
        $this->damagedEducators = new ArrayCollection();
        $this->userDelegateRequests = new ArrayCollection();
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

    public function getType(): ?SchoolType
    {
        return $this->type;
    }

    public function setType(?SchoolType $type): static
    {
        $this->type = $type;

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
     * @return Collection<int, UserDelegateSchool>
     */
    public function getUserDelegateSchools(): Collection
    {
        return $this->userDelegateSchools;
    }

    /**
     * @return Collection<int, DamagedEducator>
     */
    public function getDamagedEducators(): Collection
    {
        return $this->damagedEducators;
    }

    /**
     * @return Collection<int, UserDelegateRequest>
     */
    public function getUserDelegateRequests(): Collection
    {
        return $this->userDelegateRequests;
    }

    public function isUniversity(): bool
    {
        if (preg_match('/Univerzitet|Akademija/i', $this->getType()->getName())) {
            return true;
        }

        return false;
    }

    public function isProcessing(): ?bool
    {
        return $this->processing;
    }

    public function setProcessing(bool $processing): static
    {
        $this->processing = $processing;

        return $this;
    }

    public function isHavePayoutPriority(): ?bool
    {
        return $this->havePayoutPriority;
    }

    public function setHavePayoutPriority(bool $havePayoutPriority): static
    {
        $this->havePayoutPriority = $havePayoutPriority;

        return $this;
    }
}
