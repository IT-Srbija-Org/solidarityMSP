<?php

namespace App\Tests\Form\DataTransformer;

use App\Form\DataTransformer\AccountNumberTransformer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AccountNumberTransformerTest extends TestCase
{
    private AccountNumberTransformer $transformer;

    protected function setUp(): void
    {
        $this->transformer = new AccountNumberTransformer();
    }

    #[DataProvider('transformProvider')]
    public function testTransform(?string $input, string $expected): void
    {
        $this->assertEquals($expected, $this->transformer->transform($input));
    }

    #[DataProvider('reverseTransformProvider')]
    public function testReverseTransform(?string $input, ?string $expected): void
    {
        $this->assertEquals($expected, $this->transformer->reverseTransform($input));
    }

    public static function transformProvider(): array
    {
        return [
            'null value returns empty string' => [null, ''],
            'string value returns unchanged' => ['265104031000361092', '265104031000361092'],
        ];
    }

    public static function reverseTransformProvider(): array
    {
        return [
            'null value returns null' => [null, null],
            // Test cases with special characters and spaces
            'account with dashes' => ['160-462754-78', '160000000046275478'],
            'account with dashes and spaces' => ['160 - 462754 - 78', '160000000046275478'],
            'account with multiple spaces' => ['325  950050  02', '325000000095005002'],
            'account with special characters' => ['160.462754#78', '160000000046275478'],
            'account with mixed characters' => ['160--462.754..78', '160000000046275478'],
            // Test cases without special characters but needs padding
            'account without special chars needs padding 1' => ['16046275478', '160000000046275478'],
            'account without special chars needs padding 2' => ['32595005002', '325000000095005002'],
            // Already properly formatted numbers
            'already 18 digits 1' => ['265104031000361092', '265104031000361092'],
            'already 18 digits 2' => ['150000002501288698', '150000002501288698'],
        ];
    }
}
