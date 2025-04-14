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
        $monthName = $damagedEducatorPeriod->getDate()->format('M');

        return ($damagedEducatorPeriod->isFirstHalf() ? '1/2' : '2/2').' '.$monthName;
    }
}
