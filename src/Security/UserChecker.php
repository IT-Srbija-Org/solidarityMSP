<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if (!$user->isActive()) {
            throw new CustomUserMessageAccountStatusException('Vaš nalog je deaktiviran.');
        }

        if (!$user->isVerified()) {
            throw new CustomUserMessageAccountStatusException('Email adresa nije verifikovana.');
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        // You can leave this empty if you don't need post-auth checks
    }
}
