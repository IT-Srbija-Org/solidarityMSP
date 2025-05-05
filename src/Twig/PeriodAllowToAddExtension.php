<?php

namespace App\Twig;

use App\Entity\DamagedEducatorPeriod;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class PeriodAllowToAddExtension extends AbstractExtension
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('period_allow_to_add', [$this, 'allowToAdd']),
        ];
    }

    public function allowToAdd(DamagedEducatorPeriod $period, User $user): bool
    {
        return $this->entityManager->getRepository(DamagedEducatorPeriod::class)->allowToAdd($user, $period);
    }
}
