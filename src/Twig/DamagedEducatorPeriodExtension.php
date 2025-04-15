<?php

namespace App\Twig;

use App\Entity\DamagedEducatorPeriod;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class DamagedEducatorPeriodExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('showPeriodMonth', [$this, 'showPeriodMonth']),
        ];
    }

    public function showPeriodMonth(DamagedEducatorPeriod $damagedEducatorPeriod): string
    {
        return $damagedEducatorPeriod->getDate()->format('M');
    }
}
