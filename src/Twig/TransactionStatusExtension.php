<?php

namespace App\Twig;

use App\Entity\Transaction;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class TransactionStatusExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('transactionStatus', [$this, 'getStatus']),
        ];
    }

    public function getStatus(int $status): string
    {
        $allStatus = Transaction::STATUS;

        return $allStatus[$status] ?? 'None';
    }
}
