<?php

namespace App\Twig;

use App\Entity\DamagedEducatorPeriod;
use App\Entity\User;
use App\Repository\DamagedEducatorPeriodRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class PeriodAllowToAddExtension extends AbstractExtension
{
    public function __construct(private DamagedEducatorPeriodRepository $damagedEducatorPeriodRepository)
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
        return $this->damagedEducatorPeriodRepository->allowToAdd($user, $period);
    }
}
