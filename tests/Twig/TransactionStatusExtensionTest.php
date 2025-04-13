<?php

namespace App\Tests\Unit\Twig;

use App\Entity\Transaction;
use App\Twig\TransactionStatusExtension;
use PHPUnit\Framework\TestCase;

class TransactionStatusExtensionTest extends TestCase
{
    private TransactionStatusExtension $extension;

    protected function setUp(): void
    {
        $this->extension = new TransactionStatusExtension();
    }

    public function testGetStatusWithAllValidStatuses(): void
    {
        $statuses = [
            Transaction::STATUS_NEW => 'New',
            Transaction::STATUS_VALIDATED => 'Validated',
            Transaction::STATUS_CONFIRMED => 'Confirmed',
            Transaction::STATUS_CANCELLED => 'Cancelled',
        ];

        foreach ($statuses as $code => $label) {
            $this->assertSame($label, $this->extension->getStatus($code));
        }
    }

    public function testGetStatusWithInvalidStatus(): void
    {
        $this->assertSame('None', $this->extension->getStatus(0));
        $this->assertSame('None', $this->extension->getStatus(999));
        $this->assertSame('None', $this->extension->getStatus(-1));
    }

    public function testGetStatusWithNonIntegerInput(): void
    {
        $this->expectException(\TypeError::class);
        $this->extension->getStatus('invalid');
    }

    public function testStatusLabelsMatchTransactionConstants(): void
    {
        $reflection = new \ReflectionClass(Transaction::class);
        $constants = $reflection->getConstants();

        foreach ($constants['STATUS'] as $code => $label) {
            $this->assertSame($label, $this->extension->getStatus($code));
        }
    }
}
