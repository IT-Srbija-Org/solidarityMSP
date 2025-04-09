<?php

namespace App\Tests\Security;

use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

class EmailVerifierTest extends TestCase
{
    public function testConstructor()
    {
        // Simply test that we can create the class without errors
        $verifyEmailHelper = $this->createMock(VerifyEmailHelperInterface::class);
        $mailer = $this->createMock(MailerInterface::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $emailVerifier = new EmailVerifier($verifyEmailHelper, $mailer, $entityManager);

        // If we get here without exceptions, the test passes
        $this->assertInstanceOf(EmailVerifier::class, $emailVerifier);
    }
}
