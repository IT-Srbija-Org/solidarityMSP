<?php

namespace App\Tests\Controller;

use App\Service\StatisticsService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ApiControllerTest extends WebTestCase
{
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testV1Numbers(): void
    {
        // Mock the StatisticsService
        $statisticsService = $this->createMock(StatisticsService::class);
        $statisticsService->method('getGeneralNumbers')->willReturn([
            'transactionSumConfirmedAmount' => 1000,
            'damagedEducatorSumAmount' => 2000,
            'damagedEducatorMissingSumAmount' => 1000,
            'totalDamagedEducators' => 50,
            'totalActiveDonors' => 30,
            'avgConfirmedAmountPerEducator' => 20,
        ]);

        // Override the service in the container
        self::getContainer()->set(StatisticsService::class, $statisticsService);

        $this->client->request('GET', '/api/v1/numbers');

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $content = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('totalConfirmedAmount', $content);
        $this->assertArrayHasKey('totalRequiredAmount', $content);
        $this->assertArrayHasKey('totalEducators', $content);
        $this->assertArrayHasKey('totalActiveDonors', $content);
        $this->assertArrayHasKey('avgConfirmedAmountPerEducator', $content);
        $this->assertArrayHasKey('avgRequiredAmountPerEducator', $content);

        $this->assertEquals(1000, $content['totalConfirmedAmount']);
        $this->assertEquals(2000, $content['totalRequiredAmount']);
        $this->assertEquals(50, $content['totalEducators']);
        $this->assertEquals(30, $content['totalActiveDonors']);
        $this->assertEquals(20, $content['avgConfirmedAmountPerEducator']);
        $this->assertEquals(0, $content['avgRequiredAmountPerEducator']);
    }

    public function testV2Numbers(): void
    {
        // Mock the StatisticsService
        $statisticsService = $this->createMock(StatisticsService::class);
        $statisticsService->method('getGeneralNumbers')->willReturn([
            'transactionSumConfirmedAmount' => 1000,
            'damagedEducatorSumAmount' => 2000,
            'damagedEducatorMissingSumAmount' => 1000,
            'totalDamagedEducators' => 50,
            'totalActiveDonors' => 30,
            'avgConfirmedAmountPerEducator' => 20,
        ]);

        // Override the service in the container
        self::getContainer()->set(StatisticsService::class, $statisticsService);

        $this->client->request('GET', '/api/v2/numbers');

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $content = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('transactionSumConfirmedAmount', $content);
        $this->assertArrayHasKey('damagedEducatorMissingSumAmount', $content);
        $this->assertArrayHasKey('damagedEducatorSumAmount', $content);
        $this->assertArrayHasKey('totalDamagedEducators', $content);
        $this->assertArrayHasKey('totalActiveDonors', $content);
        $this->assertArrayHasKey('avgConfirmedAmountPerEducator', $content);

        $this->assertEquals(1000, $content['transactionSumConfirmedAmount']);
        $this->assertEquals(1000, $content['damagedEducatorMissingSumAmount']);
        $this->assertEquals(2000, $content['damagedEducatorSumAmount']);
        $this->assertEquals(50, $content['totalDamagedEducators']);
        $this->assertEquals(30, $content['totalActiveDonors']);
        $this->assertEquals(20, $content['avgConfirmedAmountPerEducator']);
    }
}
