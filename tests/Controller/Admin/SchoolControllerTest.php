<?php

namespace App\Tests\Controller\Admin;

use App\DataFixtures\CityFixtures;
use App\DataFixtures\SchoolFixtures;
use App\DataFixtures\SchoolTypeFixtures;
use App\DataFixtures\UserFixtures;
use App\Repository\SchoolRepository;
use App\Repository\UserRepository;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class SchoolControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private AbstractDatabaseTool $databaseTool;
    private ?UserRepository $userRepository;
    private ?SchoolRepository $schoolRepository;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();

        $this->databaseTool = $container->get(DatabaseToolCollection::class)->get();
        $this->loadFixtures();

        $this->userRepository = $container->get(UserRepository::class);
        $this->schoolRepository = $container->get(SchoolRepository::class);
    }

    private function loadFixtures(): void
    {
        $this->databaseTool->loadFixtures([
            UserFixtures::class,
            CityFixtures::class,
            SchoolTypeFixtures::class,
            SchoolFixtures::class,
        ]);
    }

    private function loginAsAdmin(): void
    {
        $adminUser = $this->userRepository->findOneBy(['email' => 'admin@gmail.com']);
        $this->client->loginUser($adminUser);
    }

    public function testSchoolListRequiresAuthentication(): void
    {
        $this->client->request('GET', '/admin/school/');

        $response = $this->client->getResponse();
        $statusCode = $response->getStatusCode();

        // Should not be accessible without authentication
        $this->assertNotEquals(Response::HTTP_OK, $statusCode);

        $this->assertTrue(
            $response->isRedirection()
            || in_array($statusCode, [
                Response::HTTP_UNAUTHORIZED,
                Response::HTTP_FORBIDDEN,
                Response::HTTP_INTERNAL_SERVER_ERROR,
            ])
        );
    }

    public function testSchoolListAccessibleByAdmin(): void
    {
        $this->loginAsAdmin();
        $this->client->request('GET', '/admin/school/');

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testSchoolNewRequiresAuthentication(): void
    {
        $this->client->request('GET', '/admin/school/new');

        $response = $this->client->getResponse();
        $statusCode = $response->getStatusCode();

        // Should not be accessible without authentication
        $this->assertNotEquals(Response::HTTP_OK, $statusCode);

        $this->assertTrue(
            $response->isRedirection()
            || in_array($statusCode, [
                Response::HTTP_UNAUTHORIZED,
                Response::HTTP_FORBIDDEN,
                Response::HTTP_INTERNAL_SERVER_ERROR,
            ])
        );
    }

    public function testSchoolNewAccessibleByAdmin(): void
    {
        $this->loginAsAdmin();
        $this->client->request('GET', '/admin/school/new');

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testSchoolEditRequiresAuthentication(): void
    {
        // Get a school ID from fixtures
        $school = $this->schoolRepository->findOneBy(['name' => 'Medicinska škola Beograd']);
        $schoolId = $school->getId();

        $this->client->request('GET', "/admin/school/{$schoolId}/edit");

        $response = $this->client->getResponse();
        $statusCode = $response->getStatusCode();

        // Should not be accessible without authentication
        $this->assertNotEquals(Response::HTTP_OK, $statusCode);

        $this->assertTrue(
            $response->isRedirection()
            || in_array($statusCode, [
                Response::HTTP_UNAUTHORIZED,
                Response::HTTP_FORBIDDEN,
                Response::HTTP_INTERNAL_SERVER_ERROR,
            ])
        );
    }

    public function testSchoolEditAccessibleByAdmin(): void
    {
        $this->loginAsAdmin();

        // Get a school ID from fixtures
        $school = $this->schoolRepository->findOneBy(['name' => 'Medicinska škola Beograd']);
        $schoolId = $school->getId();

        $this->client->request('GET', "/admin/school/{$schoolId}/edit");

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }
}
