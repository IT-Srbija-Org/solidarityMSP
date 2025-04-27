<?php

namespace App\Twig;

use App\Service\CloudFlareTurnstileService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ShowCaptchaExtension extends AbstractExtension
{
    public function __construct(private CloudFlareTurnstileService $cloudFlareTurnstileService)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('show_captcha', [$this, 'showCaptcha']),
        ];
    }

    public function showCaptcha(): bool
    {
        return $this->cloudFlareTurnstileService->showCaptcha();
    }
}
