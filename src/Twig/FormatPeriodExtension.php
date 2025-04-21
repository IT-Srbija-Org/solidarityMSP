<?php

namespace App\Twig;

use App\Entity\DamagedEducatorPeriod;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class FormatPeriodExtension extends AbstractExtension
{
    public function __construct(private TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('format_period', [$this, 'formatPeriod']),
        ];
    }

    public function formatPeriod(DamagedEducatorPeriod $period, bool $showShortType = false): string
    {
        if ($showShortType) {
            $month = $this->translator->trans($period->getDate()->format('M'));

            $type = match ($period->getType()) {
                DamagedEducatorPeriod::TYPE_FIRST_HALF => ' (1/2)',
                DamagedEducatorPeriod::TYPE_SECOND_HALF => ' (2/2)',
                default => '',
            };
        } else {
            $month = $this->translator->trans($period->getDate()->format('F'));

            $type = match ($period->getType()) {
                DamagedEducatorPeriod::TYPE_FIRST_HALF => ' (Prva polovina)',
                DamagedEducatorPeriod::TYPE_SECOND_HALF => ' (Druga polovina)',
                default => '',
            };
        }

        $year = $period->getYear();

        return $month.$type.', '.$year;
    }
}
