<?php

namespace App\Tests\Repository;

use App\DataFixtures\CityFixtures;
use App\DataFixtures\SchoolFixtures;
use App\DataFixtures\SchoolTypeFixtures;
use App\Entity\City;
use App\Entity\School;
use App\Entity\SchoolType;
use App\Repository\SchoolRepository;
use Doctrine\ORM\EntityManagerInterface;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SchoolRepositoryTest extends KernelTestCase
{
    private ?EntityManagerInterface $entityManager;
    private AbstractDatabaseTool $databaseTool;
    private ?City $beograd;
    private ?SchoolType $srednjaSkola;
    private ?SchoolType $osnovnaSkola;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        // Load the database tool and fixtures
        $this->databaseTool = static::getContainer()->get(DatabaseToolCollection::class)->get();
        $this->loadFixtures();

        // Get references to cities and school types from fixtures
        $cityRepository = $this->entityManager->getRepository(City::class);
        $this->beograd = $cityRepository->findOneBy(['name' => 'Beograd']);

        $schoolTypeRepository = $this->entityManager->getRepository(SchoolType::class);
        $this->srednjaSkola = $schoolTypeRepository->findOneBy(['name' => 'Srednja škola']);
        $this->osnovnaSkola = $schoolTypeRepository->findOneBy(['name' => 'Osnovna škola']);
    }

    private function loadFixtures(): void
    {
        // Load fixtures in the correct order:
        // First group 1 (City and SchoolType), then group 2 (School, which depends on them)
        $this->databaseTool->loadFixtures([
            CityFixtures::class,
            SchoolTypeFixtures::class,
            SchoolFixtures::class,
        ]);
    }

    public function testSearchMethod(): void
    {
        /** @var SchoolRepository $schoolRepository */
        $schoolRepository = $this->entityManager->getRepository(School::class);

        // Test empty search (should return all schools)
        $result = $schoolRepository->search([]);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('items', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('current_page', $result);
        $this->assertArrayHasKey('total_pages', $result);

        // Test the total count is 2 (from school fixtures)
        $this->assertEquals(2, $result['total']);

        // Test search with name criteria
        $result = $schoolRepository->search(['name' => 'Medicinska']);
        $this->assertEquals(1, count($result['items']));
        $this->assertEquals('Medicinska škola Beograd', $result['items'][0]->getName());

        // Test search with city criteria
        $result = $schoolRepository->search(['city' => $this->beograd]);
        $this->assertEquals(2, count($result['items']));

        // Test search with type criteria
        $result = $schoolRepository->search(['type' => $this->osnovnaSkola]);
        $this->assertEquals(1, count($result['items']));
        $this->assertEquals('Osnovna škola Oslobodioci Beograda', $result['items'][0]->getName());

        // Test combined search criteria
        $result = $schoolRepository->search([
            'city' => $this->beograd,
            'type' => $this->srednjaSkola,
        ]);
        $this->assertEquals(1, count($result['items']));
        $this->assertEquals('Medicinska škola Beograd', $result['items'][0]->getName());

        // Test pagination
        $result = $schoolRepository->search([], 1, 1); // Page 1, limit 1
        $this->assertEquals(1, count($result['items'])); // 1 item per page
        $this->assertEquals(2, $result['total']); // 2 schools total
        $this->assertEquals(1, $result['current_page']); // Current page is 1
        $this->assertEquals(2, $result['total_pages']); // 2 pages total (2 items with 1 per page)
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
