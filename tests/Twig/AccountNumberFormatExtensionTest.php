<?php

namespace App\Tests\Twig;

use App\Twig\AccountNumberFormatExtension;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AccountNumberFormatExtensionTest extends TestCase
{
    private AccountNumberFormatExtension $extension;

    protected function setUp(): void
    {
        $this->extension = new AccountNumberFormatExtension();
    }

    public function testGetFilters(): void
    {
        $filters = $this->extension->getFilters();

        $this->assertCount(1, $filters);
        $this->assertSame('account_number_format', $filters[0]->getName());
        $this->assertSame([$this->extension, 'formatAccountNumber'], $filters[0]->getCallable());
    }

    #[DataProvider('accountNumberProvider')]
    public function testFormatAccountNumber(?string $input, string $expected): void
    {
        $this->assertSame($expected, $this->extension->formatAccountNumber($input));
    }

    public static function accountNumberProvider(): array
    {
        return [
            // Valid 18-digit numbers
            ['123123456789012312', '123-1234567890123-12'],
            ['123-1234567890123-12', '123-1234567890123-12'],
            ['123 1234567890123 12', '123-1234567890123-12'],
            ['123-1234-5678-9012-3-12', '123-1234567890123-12'],

            // Invalid lengths
            [null, ''],
            ['', ''],
            ['123', '123'],
            ['1234567890', '1234567890'],
            ['1231234567890123', '1231234567890123'], // 16 digits
            ['1231234567890123123', '1231234567890123123'], // 19 digits
        ];
    }
}
