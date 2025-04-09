<?php

namespace App\Tests\Entity;

use App\Entity\City;
use PHPUnit\Framework\TestCase;

class CityTest extends TestCase
{
    private City $city;

    protected function setUp(): void
    {
        $this->city = new City();
        $this->city->setName('Belgrade');
    }

    public function testGetName(): void
    {
        $this->assertEquals('Belgrade', $this->city->getName());
    }

    public function testLifecycleCallbacks(): void
    {
        // Test PrePersist
        $this->city->setCreatedAt();
        $this->city->setUpdatedAt();

        $this->assertInstanceOf(\DateTimeInterface::class, $this->city->getCreatedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $this->city->getUpdatedAt());
    }
}
