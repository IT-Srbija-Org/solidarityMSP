<?php

namespace App\Tests\Entity;

use App\Entity\User;
use App\Entity\UserDonor;
use PHPUnit\Framework\TestCase;

class UserDonorTest extends TestCase
{
    private UserDonor $userDonor;
    private User $user;

    protected function setUp(): void
    {
        $this->user = new User();
        $this->user->setEmail('test@example.com');
        $this->user->setFirstName('Test');
        $this->user->setLastName('User');

        $this->userDonor = new UserDonor();
        $this->userDonor->setUser($this->user);
        $this->userDonor->setIsMonthly(true);
        $this->userDonor->setAmount(5000);
        $this->userDonor->setComment('Test comment');
    }

    public function testGetUser(): void
    {
        $this->assertSame($this->user, $this->userDonor->getUser());
    }

    public function testIsMonthly(): void
    {
        $this->assertTrue($this->userDonor->isMonthly());

        $this->userDonor->setIsMonthly(false);
        $this->assertFalse($this->userDonor->isMonthly());
    }

    public function testGetAmount(): void
    {
        $this->assertEquals(5000, $this->userDonor->getAmount());

        $this->userDonor->setAmount(3000);
        $this->assertEquals(3000, $this->userDonor->getAmount());
    }

    public function testGetComment(): void
    {
        $this->assertEquals('Test comment', $this->userDonor->getComment());

        $this->userDonor->setComment(null);
        $this->assertNull($this->userDonor->getComment());

        $this->userDonor->setComment('New comment');
        $this->assertEquals('New comment', $this->userDonor->getComment());
    }

    public function testLifecycleCallbacks(): void
    {
        // Test PrePersist
        $this->userDonor->setCreatedAt();
        $this->userDonor->setUpdatedAt();

        $this->assertInstanceOf(\DateTimeInterface::class, $this->userDonor->getCreatedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $this->userDonor->getUpdatedAt());
    }
}
