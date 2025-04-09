<?php

namespace App\Tests\Repository;

use App\DataFixtures\UserFixtures;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UserRepositoryTest extends KernelTestCase
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
            UserFixtures::class,
        ]);
    }

    public function testSearchMethod(): void
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);

        // Test empty search (should return all users)
        $result = $userRepository->search([]);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('items', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('current_page', $result);
        $this->assertArrayHasKey('total_pages', $result);

        // Test the total count is at least 3 (from fixtures)
        $this->assertGreaterThanOrEqual(3, $result['total']);

        // Test search with criteria matching fixture data
        $result = $userRepository->search(['firstName' => 'Marko']);
        $this->assertGreaterThanOrEqual(1, count($result['items']));

        $result = $userRepository->search(['email' => 'admin@gmail.com']);
        $this->assertGreaterThanOrEqual(1, count($result['items']));

        // Check specific user data from fixtures
        $result = $userRepository->search(['lastName' => 'Markovic']);
        $this->assertEquals(1, count($result['items']));
        $this->assertEquals('korisnik@gmail.com', $result['items'][0]->getEmail());
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
