<?php

namespace App\Tests\Repository;

use App\DataFixtures\SchoolTypeFixtures;
use App\Entity\SchoolType;
use App\Repository\SchoolTypeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SchoolTypeRepositoryTest extends KernelTestCase
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
            SchoolTypeFixtures::class,
        ]);
    }

    public function testFindAll(): void
    {
        /** @var SchoolTypeRepository $schoolTypeRepository */
        $schoolTypeRepository = $this->entityManager->getRepository(SchoolType::class);

        // Test findAll method
        $result = $schoolTypeRepository->findAll();
        $this->assertIsArray($result);
        $this->assertCount(2, $result); // Should have 2 school types from fixtures

        // Test findBy method with criteria
        $result = $schoolTypeRepository->findBy(['name' => 'Srednja škola']);
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('Srednja škola', $result[0]->getName());
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
