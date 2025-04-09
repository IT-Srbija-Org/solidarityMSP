<?php

namespace App\Tests\Entity;

use App\Entity\SchoolType;
use PHPUnit\Framework\TestCase;

class SchoolTypeTest extends TestCase
{
    private SchoolType $schoolType;

    protected function setUp(): void
    {
        $this->schoolType = new SchoolType();
        $this->schoolType->setName('Elementary');
    }

    public function testGetName(): void
    {
        $this->assertEquals('Elementary', $this->schoolType->getName());
    }

    public function testLifecycleCallbacks(): void
    {
        // Test PrePersist
        $this->schoolType->setCreatedAt();
        $this->schoolType->setUpdatedAt();

        $this->assertInstanceOf(\DateTimeInterface::class, $this->schoolType->getCreatedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $this->schoolType->getUpdatedAt());
    }
}
