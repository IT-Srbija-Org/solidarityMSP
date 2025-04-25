<?php

namespace App\Twig;

use App\Entity\Transaction;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class TransactionStatusExtension extends AbstractExtension
{
    public function __construct(private TranslatorInterface $translator)
    {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('transactionStatus', [$this, 'getStatus']),
        ];
    }

    public function getStatus(int $status): string
    {
        $allStatus = Transaction::STATUS;
        $statusName = $this->translator->trans($allStatus[$status]) ?? 'None';

        $icon = match ($status) {
            Transaction::STATUS_NEW => '<span class="ti ti-clock text-xl text-gray-500 relative top-0.5"></span>',
            Transaction::STATUS_WAITING_CONFIRMATION => '<span class="loading loading-spinner text-primary relative -top-0.5"></span>',
            Transaction::STATUS_CONFIRMED => '<span class="ti ti-circle-check text-xl text-success relative top-0.5"></span>',
            Transaction::STATUS_CANCELLED => '<span class="ti ti-circle-x text-xl text-error relative top-0.5"></span>',
        };

        return $icon.' <span class="text-gray-500">'.$statusName.'</span>';
    }
}
