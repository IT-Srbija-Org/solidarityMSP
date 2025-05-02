<?php

namespace App\Tests\Service;

use App\Service\IpsQrCodeService;
use PHPUnit\Framework\TestCase;

class IpsQrCodeServiceTest extends TestCase
{
    private IpsQrCodeService $service;

    protected function setUp(): void
    {
        $this->service = new IpsQrCodeService();
    }

    public function testThrowsOnMissingRequiredArguments(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->createIpsQrString([]);
    }

    public function testWorksWithRequiredArgumentsAndDefaults(): void
    {
        $args = [
            'bankAccountNumber' => '123456789012345611',
            'payeeName' => 'JEST Ltd., Test',
            'payeeCityName' => 'Beograd',
            'amount' => '1295,',
            'paymentPurpose' => 'Test Purpose',
        ];

        $expected = "K:PR|V:01|C:1|R:123456789012345611|N:JEST Ltd., Test\n\rNOTPROVIDED\n\rBeograd|I:RSD1295,|S:Test Purpose";
        $actual = $this->service->createIpsQrString($args);

        $this->assertSame($this->removeNewlines($expected), $this->removeNewlines($actual));
    }

    public function testWorksWithAllArgumentsProvided(): void
    {
        $args = [
            'identificationCode' => 'PR',
            'version' => '01',
            'characterSet' => '1',
            'bankAccountNumber' => '123456789012345611',
            'payeeName' => 'JEST Ltd., Test',
            'amount' => '1295,',
            'payeeCityName' => 'Beograd',
            'paymentCode' => '123',
            'paymentPurpose' => 'Testing',
        ];

        $expected = "K:PR|V:01|C:1|R:123456789012345611|N:JEST Ltd., Test\n\rNOTPROVIDED\n\rBeograd|I:RSD1295,|SF:123|S:Testing";
        $actual = $this->service->createIpsQrString($args);

        $this->assertSame($this->removeNewlines($expected), $this->removeNewlines($actual));
    }

    public function testWorksWithReferenceCode(): void
    {
        $args = [
            'bankAccountNumber' => '123456789012345611',
            'payeeName' => 'JEST Ltd., Test',
            'amount' => '1295,',
            'payeeCityName' => 'Beograd',
            'paymentPurpose' => 'Test Purpose',
            'referenceCode' => '972012345',
        ];

        $expected = "K:PR|V:01|C:1|R:123456789012345611|N:JEST Ltd., Test\n\rNOTPROVIDED\n\rBeograd|I:RSD1295,|S:Test Purpose|RO:00972012345";
        $actual = $this->service->createIpsQrString($args);

        $this->assertSame($this->removeNewlines($expected), $this->removeNewlines($actual));
    }

    private function removeNewlines(string $string): string
    {
        return preg_replace('/\s+/', ' ', $string);
    }
}
