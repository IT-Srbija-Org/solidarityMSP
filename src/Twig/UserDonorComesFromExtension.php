<?php

namespace App\Twig;

use App\Entity\UserDonor;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class UserDonorComesFromExtension extends AbstractExtension
{
    public function __construct(private TranslatorInterface $translator)
    {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('user_donor_comes_from', [$this, 'getComesFrom']),
        ];
    }

    public function getComesFrom(int $comesFrom): string
    {
        $allComesFrom = UserDonor::COMES_FROM;
        $comesFromName = $this->translator->trans($allComesFrom[$comesFrom]) ?? 'None';

        return $comesFromName;
    }
}
