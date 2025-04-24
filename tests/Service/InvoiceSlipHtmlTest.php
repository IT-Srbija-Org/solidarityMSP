<?php

namespace App\Tests\Service;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Twig\Environment;

class InvoiceSlipHtmlTest extends KernelTestCase
{
    public function testInvoiceSlipHtmlMapsDataToCorrectSelectors(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        /** @var Environment $twig */
        $twig = $container->get(Environment::class);

        $data = [
            'payer' => 'Test User',
            'recipient' => 'Recipient Name',
            'purpose' => 'Test Purpose',
            'amount' => '1.234,56',
            'account' => '123-4567890123456-11',
            'reference' => 'REF123',
            'place' => 'Test City',
            'date' => '19.04.2024',
            'model' => '97',
            'currency' => 'RSD',
            'payment_code' => '289',
            'bg_url' => 'data:image/png;base64,FAKEIMAGE',
            'img_width' => 2480,
            'img_height' => 1181,
        ];

        $html = $twig->render('donor/transaction/print.html.twig', $data);
        $crawler = new Crawler($html);

        $this->assertSame('Test User', $crawler->filter('#payer')->text());
        $this->assertSame('Recipient Name', $crawler->filter('#recipient')->text());
        $this->assertSame('Test Purpose', $crawler->filter('#purpose')->text());
        $this->assertSame('1.234,56', $crawler->filter('#amount')->text());
        $this->assertSame('123-4567890123456-11', $crawler->filter('#account')->text());
        $this->assertSame('REF123', $crawler->filter('#reference')->text());
        $this->assertSame('Test City', $crawler->filter('#place')->text());
        $this->assertSame('19.04.2024', $crawler->filter('#date')->text());
        $this->assertSame('97', $crawler->filter('#model')->text());
        $this->assertSame('RSD', $crawler->filter('#currency')->text());
        $this->assertSame('289', $crawler->filter('#payment_code')->text());
    }
}
