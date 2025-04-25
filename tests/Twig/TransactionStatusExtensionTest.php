<?php

namespace App\Tests\Twig;

use App\Entity\Transaction;
use App\Twig\TransactionStatusExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class TransactionStatusExtensionTest extends TestCase
{
    private TransactionStatusExtension $extension;
    private TranslatorInterface $translator;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->extension = new TransactionStatusExtension($this->translator);
    }

    public function testGetFilters()
    {
        $filters = $this->extension->getFilters();

        $this->assertCount(1, $filters);
        $this->assertSame('transaction_status', $filters[0]->getName());
        $this->assertSame([$this->extension, 'getStatus'], $filters[0]->getCallable());
    }

    public function testGetStatusForNew()
    {
        $this->translator->method('trans')
            ->with('WaitingPayment')
            ->willReturn('Waiting Payment');

        $result = $this->extension->getStatus(Transaction::STATUS_NEW);

        $this->assertStringContainsString('clock', $result);
        $this->assertStringContainsString('Waiting Payment', $result);
    }

    public function testGetStatusForWaitingConfirmation()
    {
        $this->translator->method('trans')
            ->with('WaitingConfirmation')
            ->willReturn('Waiting Confirmation');

        $result = $this->extension->getStatus(Transaction::STATUS_WAITING_CONFIRMATION);

        $this->assertStringContainsString('loading-spinner', $result);
        $this->assertStringContainsString('Waiting Confirmation', $result);
    }

    public function testGetStatusForConfirmed()
    {
        $this->translator->method('trans')
            ->with('Confirmed')
            ->willReturn('Confirmed');

        $result = $this->extension->getStatus(Transaction::STATUS_CONFIRMED);

        $this->assertStringContainsString('circle-check', $result);
        $this->assertStringContainsString('Confirmed', $result);
    }

    public function testGetStatusForCancelled()
    {
        $this->translator->method('trans')
            ->with('Cancelled')
            ->willReturn('Cancelled');

        $result = $this->extension->getStatus(Transaction::STATUS_CANCELLED);

        $this->assertStringContainsString('circle-x', $result);
        $this->assertStringContainsString('Cancelled', $result);
    }
}
