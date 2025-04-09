<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['email'], message: 'Vec postoji korisnik sa ovim emailom')]
class User implements UserInterface
{
    public const ROLES = [
        'ROLE_USER' => 'Korisnik',
        'ROLE_DELEGATE' => 'Delegat',
        'ROLE_ADMIN' => 'Administrator',
    ];
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\NotBlank(message: 'Ovo polje je obavezno')]
    #[Assert\Email(message: 'Email nije validan')]
    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    #[Assert\NotBlank(message: 'Ovo polje je obavezno')]
    #[Assert\Length(min: 3, max: 100, minMessage: 'Polje mora imati bar {{ limit }} karaktera', maxMessage: 'Polje ne može imati više od {{ limit }} karaktera')]
    #[ORM\Column(length: 255)]
    private ?string $firstName = null;

    #[Assert\NotBlank(message: 'Ovo polje je obavezno')]
    #[Assert\Length(min: 3, max: 100, minMessage: 'Polje mora imati bar {{ limit }} karaktera', maxMessage: 'Polje ne može imati više od {{ limit }} karaktera')]
    #[ORM\Column(length: 255)]
    private ?string $lastName = null;

    #[ORM\Column]
    private ?bool $isActive = true;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column]
    private bool $isVerified = false;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $resetToken = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $resetTokenCreatedAt = null;

    #[ORM\OneToOne(mappedBy: 'user')]
    private ?UserDonor $userDonor = null;

    /**
     * @var Collection<int, UserDelegateSchool>
     */
    #[ORM\OneToMany(targetEntity: UserDelegateSchool::class, mappedBy: 'user')]
    private Collection $userDelegateSchools;

    /**
     * @var Collection<int, Educator>
     */
    #[ORM\OneToMany(targetEntity: Educator::class, mappedBy: 'createdBy')]
    private Collection $educators;

    /**
     * @var Collection<int, Transaction>
     */
    #[ORM\OneToMany(targetEntity: Transaction::class, mappedBy: 'user')]
    private Collection $transactions;

    #[ORM\OneToOne(mappedBy: 'user')]
    private ?UserDelegateRequest $userDelegateRequest = null;

    public function __construct()
    {
        $this->userDelegateSchools = new ArrayCollection();
        $this->educators = new ArrayCollection();
        $this->transactions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @return list<string>
     *
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function addRole(string $role): static
    {
        if (!in_array($role, $this->roles)) {
            $this->roles[] = $role;
        }

        return $this;
    }

    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

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

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    public function getResetToken(): ?string
    {
        return $this->resetToken;
    }

    public function setResetToken(?string $resetToken): static
    {
        $this->resetToken = $resetToken;

        return $this;
    }

    public function getResetTokenCreatedAt(): ?\DateTimeInterface
    {
        return $this->resetTokenCreatedAt;
    }

    public function setResetTokenCreatedAt(?\DateTimeInterface $resetTokenCreatedAt): static
    {
        $this->resetTokenCreatedAt = $resetTokenCreatedAt;

        return $this;
    }

    public function getFullName(): string
    {
        return $this->firstName.' '.$this->lastName;
    }

    public function getUserDonor(): ?UserDonor
    {
        return $this->userDonor;
    }

    /**
     * @return Collection<int, UserDelegateSchool>
     */
    public function getUserDelegateSchools(): Collection
    {
        return $this->userDelegateSchools;
    }

    /**
     * @return Collection<int, Educator>
     */
    public function getEducators(): Collection
    {
        return $this->educators;
    }

    /**
     * @return Collection<int, Transaction>
     */
    public function getTransactions(): Collection
    {
        return $this->transactions;
    }

    public function getUserDelegateRequest(): ?UserDelegateRequest
    {
        return $this->userDelegateRequest;
    }
}
