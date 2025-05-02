<?php

namespace App\Tests\Service;

use App\Entity\Transaction;
use App\Entity\User;
use App\Service\InvoiceSlipService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class InvoiceSlipServiceTest extends TestCase
{
    public function testPrepareSlipDataReturnsExpectedArray(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getFullName')->willReturn('Test User');

        $city = $this->createMock(\App\Entity\City::class);
        $city->method('getName')->willReturn('Test City');

        $damagedEducator = $this->createMock(\App\Entity\DamagedEducator::class);
        $damagedEducator->method('getName')->willReturn('Recipient Name');
        $damagedEducator->method('getCity')->willReturn($city);

        $transaction = $this->createMock(Transaction::class);
        $transaction->method('getDamagedEducator')->willReturn($damagedEducator);
        $transaction->method('getAmount')->willReturn(123456);
        $transaction->method('getAccountNumber')->willReturn('123-4567890123456-11');
        $transaction->method('getCreatedAt')->willReturn(new \DateTime('2024-04-19 12:00:00'));

        $params = $this->createMock(ParameterBagInterface::class);
        $service = new InvoiceSlipService($params);

        $result = $service->prepareSlipData($transaction, $user);

        $this->assertSame([
            'payer' => 'Test User',
            'recipient' => 'Recipient Name, Test City',
            'purpose' => 'Transakcija po nalogu građana',
            'amount' => '123456,00',
            'account' => '123-4567890123456-11',
            'reference' => 0,
            'place' => '',
            'date' => '19.04.2024',
            'model' => '',
            'currency' => 'RSD',
            'payment_code' => '289',
        ], $result);
    }
}
