<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class MaskTextExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('mask', [$this, 'maskText']),
        ];
    }

    public function maskText(string|int $value): string
    {
        $text = (string) $value;
        $length = mb_strlen($text);

        $firstChar = mb_substr($text, 0, 1);
        $lastChar = mb_substr($text, -1);
        $mask = str_repeat('*', $length - 2);

        return $firstChar.$mask.$lastChar;
    }
}
