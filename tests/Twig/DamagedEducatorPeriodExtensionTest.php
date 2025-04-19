<?php

namespace App\Tests\Twig;

use App\Entity\DamagedEducatorPeriod;
use App\Twig\DamagedEducatorPeriodExtension;
use PHPUnit\Framework\TestCase;

class DamagedEducatorPeriodExtensionTest extends TestCase
{
    private DamagedEducatorPeriodExtension $extension;

    protected function setUp(): void
    {
        $this->extension = new DamagedEducatorPeriodExtension();
    }

    public function testGetFilters(): void
    {
        $filters = $this->extension->getFilters();

        $this->assertCount(1, $filters);
        $this->assertSame('showPeriodMonth', $filters[0]->getName());
    }

    public function testShowPeriodMonth(): void
    {
        // Create a mock DamagedEducatorPeriod with a specific date
        $date = new \DateTime('2023-05-15');
        $period = $this->createMock(DamagedEducatorPeriod::class);
        $period->method('getDate')->willReturn($date);

        // Test the filter
        $result = $this->extension->showPeriodMonth($period);

        $this->assertSame('May', $result);
    }

    public function testShowPeriodMonthWithDifferentMonths(): void
    {
        $testCases = [
            ['2023-01-15', 'Jan'],
            ['2023-06-20', 'Jun'],
            ['2023-12-01', 'Dec'],
        ];

        foreach ($testCases as [$dateString, $expected]) {
            $date = new \DateTime($dateString);
            $period = $this->createMock(DamagedEducatorPeriod::class);
            $period->method('getDate')->willReturn($date);

            $result = $this->extension->showPeriodMonth($period);
            $this->assertSame($expected, $result);
        }
    }
}
