<?php

namespace App\Service;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CloudFlareTurnstileService
{
    public function __construct(private Security $security, private HttpClientInterface $client, private ParameterBagInterface $params)
    {
    }

    public function showCaptcha(): bool
    {
        if (!$this->params->get('CLOUDFLARE_TURNSTILE_SECRET_KEY')) {
            return false;
        }

        $user = $this->security->getUser();
        if ($user) {
            return false;
        }

        return true;
    }

    public function isValid(?string $token): bool
    {
        if (!$this->showCaptcha()) {
            return true;
        }

        $response = $this->client->request('POST', 'https://challenges.cloudflare.com/turnstile/v0/siteverify', [
            'body' => [
                'secret' => $this->params->get('CLOUDFLARE_TURNSTILE_SECRET_KEY'),
                'response' => $token,
            ],
        ]);

        if (200 !== $response->getStatusCode()) {
            return false;
        }

        return $response->toArray()['success'] ?? false;
    }
}
