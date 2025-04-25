<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AccountNumberFormatExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('account_number_format', [$this, 'formatAccountNumber']),
        ];
    }

    /**
     * Formats a Serbian account number as 3-13-2 (e.g., 123-1234567890123-12).
     */
    public function formatAccountNumber(?string $number): string
    {
        if (empty($number)) {
            return '';
        }

        // Remove all non-digit characters
        $digits = preg_replace('/\D+/', '', $number);

        if (18 === strlen($digits)) {
            return preg_replace('/^(\d{3})(\d{13})(\d{2})$/', '$1-$2-$3', $digits);
        }

        // fallback: return as is
        return $number;
    }
}
