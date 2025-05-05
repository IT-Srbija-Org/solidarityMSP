<?php

namespace App\Twig;

use App\Entity\DamagedEducator;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class DamagedEducatorStatusExtension extends AbstractExtension
{
    public function __construct(private TranslatorInterface $translator)
    {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('damaged_educator_status', [$this, 'getStatus']),
        ];
    }

    public function getStatus(int $status): string
    {
        $allStatus = DamagedEducator::STATUS;
        $statusName = $this->translator->trans($allStatus[$status]) ?? '-';

        return match ($status) {
            DamagedEducator::STATUS_NEW => '<span class="text-gray-500">'.$statusName.'</span>',
            DamagedEducator::STATUS_DELETED => '<span class="ti ti-circle-x text-xl text-error relative top-0.5"></span> <span class="text-gray-500">'.$statusName.'</span>',
        };
    }
}
