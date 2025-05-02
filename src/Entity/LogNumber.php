<?php

namespace App\Entity;

use App\Repository\LogStatRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LogStatRepository::class)]
#[ORM\HasLifecycleCallbacks]
class LogNumber
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $totalDonors = null;

    #[ORM\Column]
    private ?int $totalMonthlyDonors = null;

    #[ORM\Column]
    private ?int $totalNonMonthlyDonors = null;

    #[ORM\Column(type: Types::BIGINT)]
    private ?string $sumAmountMonthlyDonors = null;

    #[ORM\Column(type: Types::BIGINT)]
    private ?string $sumAmountNonMonthlyDonors = null;

    #[ORM\Column]
    private ?int $totalDelegates = null;

    #[ORM\Column]
    private ?int $totalActiveSchools = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

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

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTotalDonors(): ?int
    {
        return $this->totalDonors;
    }

    public function setTotalDonors(int $totalDonors): static
    {
        $this->totalDonors = $totalDonors;

        return $this;
    }

    public function getTotalMonthlyDonors(): ?int
    {
        return $this->totalMonthlyDonors;
    }

    public function setTotalMonthlyDonors(int $totalMonthlyDonors): static
    {
        $this->totalMonthlyDonors = $totalMonthlyDonors;

        return $this;
    }

    public function getTotalNonMonthlyDonors(): ?int
    {
        return $this->totalNonMonthlyDonors;
    }

    public function setTotalNonMonthlyDonors(int $totalNonMonthlyDonors): static
    {
        $this->totalNonMonthlyDonors = $totalNonMonthlyDonors;

        return $this;
    }

    public function getSumAmountMonthlyDonors(): ?string
    {
        return $this->sumAmountMonthlyDonors;
    }

    public function setSumAmountMonthlyDonors(string $sumAmountMonthlyDonors): static
    {
        $this->sumAmountMonthlyDonors = $sumAmountMonthlyDonors;

        return $this;
    }

    public function getSumAmountNonMonthlyDonors(): ?string
    {
        return $this->sumAmountNonMonthlyDonors;
    }

    public function setSumAmountNonMonthlyDonors(string $sumAmountNonMonthlyDonors): static
    {
        $this->sumAmountNonMonthlyDonors = $sumAmountNonMonthlyDonors;

        return $this;
    }

    public function getTotalDelegates(): ?int
    {
        return $this->totalDelegates;
    }

    public function setTotalDelegates(int $totalDelegates): static
    {
        $this->totalDelegates = $totalDelegates;

        return $this;
    }

    public function getTotalActiveSchools(): ?int
    {
        return $this->totalActiveSchools;
    }

    public function setTotalActiveSchools(int $totalActiveSchools): static
    {
        $this->totalActiveSchools = $totalActiveSchools;

        return $this;
    }
}
