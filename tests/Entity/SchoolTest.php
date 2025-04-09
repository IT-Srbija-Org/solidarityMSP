<?php

namespace App\Tests\Entity;

use App\Entity\City;
use App\Entity\School;
use App\Entity\SchoolType;
use PHPUnit\Framework\TestCase;

class SchoolTest extends TestCase
{
    private School $school;
    private City $city;
    private SchoolType $schoolType;

    protected function setUp(): void
    {
        $this->city = new City();
        $this->city->setName('Belgrade');

        $this->schoolType = new SchoolType();
        $this->schoolType->setName('Elementary');

        $this->school = new School();
        $this->school->setName('Test School');
        $this->school->setCity($this->city);
        $this->school->setType($this->schoolType);
    }

    public function testGetName(): void
    {
        $this->assertEquals('Test School', $this->school->getName());
    }

    public function testGetCity(): void
    {
        $this->assertSame($this->city, $this->school->getCity());
        $this->assertEquals('Belgrade', $this->school->getCity()->getName());
    }

    public function testGetType(): void
    {
        $this->assertSame($this->schoolType, $this->school->getType());
        $this->assertEquals('Elementary', $this->school->getType()->getName());
    }

    public function testLifecycleCallbacks(): void
    {
        // Test PrePersist
        $this->school->setCreatedAt();
        $this->school->setUpdatedAt();

        $this->assertInstanceOf(\DateTimeInterface::class, $this->school->getCreatedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $this->school->getUpdatedAt());
    }
}
