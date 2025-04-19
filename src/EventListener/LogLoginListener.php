<?php

namespace App\EventListener;

use App\Entity\LogLogin;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Core\Event\AuthenticationSuccessEvent;

final class LogLoginListener
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    #[AsEventListener(event: 'security.authentication.success')]
    public function onSecurityAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        $user = $event->getAuthenticationToken()->getUser();
        if (!$user instanceof User) {
            return;
        }

        if (!$this->allowToLog($user)) {
            return;
        }

        $logLogin = new LogLogin();
        $logLogin->setUser($user);
        $logLogin->setIp($this->getClientIp());
        $logLogin->setHeaders($this->getRequestHeaders());

        $this->entityManager->persist($logLogin);
        $this->entityManager->flush();
    }

    private function allowToLog(User $user): bool
    {
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return true;
        }

        if (in_array('ROLE_DELEGATE', $user->getRoles())) {
            return true;
        }

        return false;
    }

    private function getClientIp(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

    private function getRequestHeaders(): array
    {
        $headers = function_exists('apache_request_headers') ? apache_request_headers() : [];
        unset($headers['Cookie']);

        return $headers;
    }
}
