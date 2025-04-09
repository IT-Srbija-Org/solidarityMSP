<?php

namespace App\Tests\Security;

use App\Entity\User;
use App\Security\UserChecker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserInterface;

class UserCheckerTest extends TestCase
{
    private UserChecker $userChecker;

    protected function setUp(): void
    {
        $this->userChecker = new UserChecker();
    }

    public function testCheckPreAuthWithNonUserInterface(): void
    {
        // This should not throw any exception
        $mockUser = $this->createMock(UserInterface::class);

        $this->userChecker->checkPreAuth($mockUser);
        // If we get here, the test passes
        $this->assertTrue(true);
    }

    public function testCheckPreAuthWithInactiveUser(): void
    {
        $user = new User();
        $user->setIsActive(false);
        $user->setIsVerified(true);

        $this->expectException(CustomUserMessageAccountStatusException::class);
        $this->expectExceptionMessage('Vaš nalog je deaktiviran.');

        $this->userChecker->checkPreAuth($user);
    }

    public function testCheckPreAuthWithUnverifiedUser(): void
    {
        $user = new User();
        $user->setIsActive(true);
        $user->setIsVerified(false);

        $this->expectException(CustomUserMessageAccountStatusException::class);
        $this->expectExceptionMessage('Email adresa nije verifikovana.');

        $this->userChecker->checkPreAuth($user);
    }

    public function testCheckPreAuthWithValidUser(): void
    {
        $user = new User();
        $user->setIsActive(true);
        $user->setIsVerified(true);

        // This should not throw any exception
        $this->userChecker->checkPreAuth($user);

        // If we get here, the test passes
        $this->assertTrue(true);
    }

    public function testCheckPostAuth(): void
    {
        $user = new User();

        // This should not do anything
        $this->userChecker->checkPostAuth($user);

        // If we get here, the test passes
        $this->assertTrue(true);
    }
}
