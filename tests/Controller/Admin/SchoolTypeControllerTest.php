<?php

namespace App\Tests\Controller\Admin;

use App\DataFixtures\SchoolTypeFixtures;
use App\DataFixtures\UserFixtures;
use App\Repository\SchoolTypeRepository;
use App\Repository\UserRepository;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class SchoolTypeControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private AbstractDatabaseTool $databaseTool;
    private ?UserRepository $userRepository;
    private ?SchoolTypeRepository $schoolTypeRepository;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();

        $this->databaseTool = $container->get(DatabaseToolCollection::class)->get();
        $this->loadFixtures();

        $this->userRepository = $container->get(UserRepository::class);
        $this->schoolTypeRepository = $container->get(SchoolTypeRepository::class);
    }

    private function loadFixtures(): void
    {
        $this->databaseTool->loadFixtures([
            UserFixtures::class,
            SchoolTypeFixtures::class,
        ]);
    }

    private function loginAsAdmin(): void
    {
        $adminUser = $this->userRepository->findOneBy(['email' => 'admin@gmail.com']);
        $this->client->loginUser($adminUser);
    }

    public function testSchoolTypeListRequiresAuthentication(): void
    {
        $this->client->request('GET', '/admin/school-type/');

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

    public function testSchoolTypeListAccessibleByAdmin(): void
    {
        $this->loginAsAdmin();
        $this->client->request('GET', '/admin/school-type/');

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testSchoolTypeNewRequiresAuthentication(): void
    {
        $this->client->request('GET', '/admin/school-type/new');

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

    public function testSchoolTypeNewAccessibleByAdmin(): void
    {
        $this->loginAsAdmin();
        $this->client->request('GET', '/admin/school-type/new');

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testSchoolTypeEditRequiresAuthentication(): void
    {
        // Get a school type ID from fixtures
        $schoolType = $this->schoolTypeRepository->findOneBy(['name' => 'Srednja škola']);
        $schoolTypeId = $schoolType->getId();

        $this->client->request('GET', "/admin/school-type/{$schoolTypeId}/edit");

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

    public function testSchoolTypeEditAccessibleByAdmin(): void
    {
        $this->loginAsAdmin();

        // Get a school type ID from fixtures
        $schoolType = $this->schoolTypeRepository->findOneBy(['name' => 'Srednja škola']);
        $schoolTypeId = $schoolType->getId();

        $this->client->request('GET', "/admin/school-type/{$schoolTypeId}/edit");

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }
}
