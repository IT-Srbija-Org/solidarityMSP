<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class PreExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('pre', [$this, 'pre']),
        ];
    }

    public function pre(array $data): string
    {
        return '<pre>'.json_encode($data, JSON_PRETTY_PRINT).'</pre>';
    }
}
