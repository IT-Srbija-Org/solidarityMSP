<?php

namespace App\Tests\Repository;

use App\Entity\UserDonor;
use App\Repository\UserDonorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UserDonorRepositoryTest extends KernelTestCase
{
    private ?EntityManagerInterface $entityManager;
    private AbstractDatabaseTool $databaseTool;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        // Load the database tool and fixtures
        $this->databaseTool = static::getContainer()->get(DatabaseToolCollection::class)->get();
        $this->loadFixtures();
    }

    private function loadFixtures(): void
    {
        $this->databaseTool->loadFixtures([
            'App\DataFixtures\UserFixtures',
            'App\DataFixtures\UserDonorFixtures',
        ]);
    }

    public function testSearchMethod(): void
    {
        /** @var UserDonorRepository $userDonorRepository */
        $userDonorRepository = $this->entityManager->getRepository(UserDonor::class);

        // Test empty search (should return all donors)
        $result = $userDonorRepository->search([]);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('items', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('current_page', $result);
        $this->assertArrayHasKey('total_pages', $result);

        // Test the total count is at least 2 (from fixtures)
        $this->assertGreaterThanOrEqual(2, $result['total']);

        // Test search with monthly criteria
        $result = $userDonorRepository->search(['isMonthly' => true]);
        $this->assertCount(1, $result['items']);
        $this->assertTrue($result['items'][0]->isMonthly());

        $result = $userDonorRepository->search(['isMonthly' => false]);
        $this->assertCount(1, $result['items']);
        $this->assertFalse($result['items'][0]->isMonthly());

        // Test pagination
        $result = $userDonorRepository->search([], 1, 1);
        $this->assertCount(1, $result['items']);
        $this->assertEquals(1, $result['current_page']);
        $this->assertEquals(2, $result['total_pages']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up the entity manager to avoid memory leaks
        if ($this->entityManager) {
            $this->entityManager->close();
            $this->entityManager = null;
        }
    }
}
