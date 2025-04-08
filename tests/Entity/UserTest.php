<?php

namespace App\Tests\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        $this->user = new User();
        $this->user->setEmail('test@example.com');
        $this->user->setFirstName('Test');
        $this->user->setLastName('User');
    }

    public function testGetEmail(): void
    {
        $this->assertEquals('test@example.com', $this->user->getEmail());
    }

    public function testGetFullName(): void
    {
        $this->assertEquals('Test User', $this->user->getFullName());
    }

    public function testDefaultValues(): void
    {
        $this->assertTrue($this->user->isActive());
        $this->assertFalse($this->user->isVerified());
    }

    public function testUserIdentifier(): void
    {
        $this->assertEquals('test@example.com', $this->user->getUserIdentifier());
    }

    public function testRoles(): void
    {
        // Default role should be ROLE_USER
        $this->assertContains('ROLE_USER', $this->user->getRoles());

        // Test setting roles
        $this->user->setRoles(['ROLE_ADMIN']);
        $roles = $this->user->getRoles();

        $this->assertContains('ROLE_ADMIN', $roles);
        $this->assertContains('ROLE_USER', $roles); // ROLE_USER should always be included
        $this->assertCount(2, $roles);
    }

    public function testLifecycleCallbacks(): void
    {
        // Test PrePersist
        $this->user->setCreatedAt();
        $this->user->setUpdatedAt();

        $this->assertInstanceOf(\DateTimeInterface::class, $this->user->getCreatedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $this->user->getUpdatedAt());
    }
}
