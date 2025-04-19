<?php

namespace App\Tests\Twig;

use App\Twig\PhoneNumberFormatExtension;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PhoneNumberFormatExtensionTest extends TestCase
{
    private PhoneNumberFormatExtension $extension;

    protected function setUp(): void
    {
        $this->extension = new PhoneNumberFormatExtension();
    }

    public function testGetFilters(): void
    {
        $filters = $this->extension->getFilters();

        $this->assertCount(1, $filters);
        $this->assertSame('phone_format', $filters[0]->getName());
        $this->assertSame([$this->extension, 'formatPhoneNumber'], $filters[0]->getCallable());
    }

    #[DataProvider('phoneNumberProvider')]
    public function testFormatPhoneNumber(?string $input, string $expected): void
    {
        $this->assertSame($expected, $this->extension->formatPhoneNumber($input));
    }

    public static function phoneNumberProvider(): array
    {
        return [
            // 10-digit numbers
            ['0612345678', '061 234 5678'],
            ['06-12-34-56-78', '061 234 5678'],
            ['06 12 34 56 78', '061 234 5678'],

            // 9-digit numbers
            ['612345678', '612 345 678'],
            ['6-12-34-56-78', '612 345 678'],

            // Other lengths
            ['065454554565', '065 454 554 565'],
        ];
    }

    public function testFormatPhoneNumberWithDifferentGroupings(): void
    {
        // Test that the fallback chunk_split works as expected
        $this->assertSame('123 456 789 012 345', $this->extension->formatPhoneNumber('123456789012345'));
        $this->assertSame('123 456 789 012 345 678', $this->extension->formatPhoneNumber('123456789012345678'));
    }
}
