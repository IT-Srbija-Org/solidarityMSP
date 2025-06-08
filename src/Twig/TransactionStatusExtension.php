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
            new TwigFilter('transaction_status', [$this, 'get']),
        ];
    }

    public function get(Transaction|int $value, bool $isDonorView = false): string
    {
        if ($value instanceof Transaction) {
            $status = $value->getStatus();
            if ($isDonorView && $value->isStatusNotPaid() && $value->isUserDonorConfirmed()) {
                $status = Transaction::STATUS_PAID;
            }

            return $this->getStatus($status);
        }

        return $this->getStatus($value);
    }

    private function getStatus(int $status): string
    {
        $allStatus = Transaction::STATUS;
        $statusName = $this->translator->trans($allStatus[$status]) ?? 'None';

        $iconPath = match ($status) {
            Transaction::STATUS_NEW => '/icons/hourglass.svg',
            Transaction::STATUS_WAITING_CONFIRMATION => '/icons/status-waiting-confirmation.svg',
            Transaction::STATUS_EXPIRED => '/icons/status-expired.svg',
            Transaction::STATUS_CONFIRMED => '/icons/status-confirmed.svg',
            Transaction::STATUS_NOT_PAID => '/icons/status-not-paid.svg',
            Transaction::STATUS_CANCELLED => '/icons/status-cancelled.svg',
            Transaction::STATUS_PAID => '/icons/status-confirmed.svg',
        };

        $icon = ' <img src="'.$iconPath.'" alt="'.$statusName.'" class="w-5 h-5 inline-block mr-1.5 relative -translate-y-0.5" />';

        return $icon.' <span>'.$statusName.'</span>';
    }
}
