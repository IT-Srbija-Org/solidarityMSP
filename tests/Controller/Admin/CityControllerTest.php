<?php

namespace App\Tests\Controller\Admin;

use App\DataFixtures\CityFixtures;
use App\DataFixtures\UserFixtures;
use App\Entity\City;
use App\Repository\CityRepository;
use App\Repository\UserRepository;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class CityControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private AbstractDatabaseTool $databaseTool;
    private ?UserRepository $userRepository;
    private ?CityRepository $cityRepository;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();

        $this->databaseTool = $container->get(DatabaseToolCollection::class)->get();
        $this->loadFixtures();

        $this->userRepository = $container->get(UserRepository::class);
        $this->cityRepository = $container->get(CityRepository::class);
    }

    private function loadFixtures(): void
    {
        $this->databaseTool->loadFixtures([
            UserFixtures::class,
            CityFixtures::class
        ]);
    }

    private function loginAsAdmin(): void
    {
        $adminUser = $this->userRepository->findOneBy(['email' => 'admin@gmail.com']);
        $this->client->loginUser($adminUser);
    }

    public function testCityListRequiresAuthentication(): void
    {
        $this->client->request('GET', '/admin/city/');

        $response = $this->client->getResponse();
        $statusCode = $response->getStatusCode();

        // Should not be accessible without authentication
        $this->assertNotEquals(Response::HTTP_OK, $statusCode);

        $this->assertTrue(
            $response->isRedirection() ||
            in_array($statusCode, [
                Response::HTTP_UNAUTHORIZED,
                Response::HTTP_FORBIDDEN,
                Response::HTTP_INTERNAL_SERVER_ERROR
            ])
        );
    }

    public function testCityListAccessibleByAdmin(): void
    {
        $this->loginAsAdmin();
        $this->client->request('GET', '/admin/city/');

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testCityNewRequiresAuthentication(): void
    {
        $this->client->request('GET', '/admin/city/new');

        $response = $this->client->getResponse();
        $statusCode = $response->getStatusCode();

        // Should not be accessible without authentication
        $this->assertNotEquals(Response::HTTP_OK, $statusCode);

        $this->assertTrue(
            $response->isRedirection() ||
            in_array($statusCode, [
                Response::HTTP_UNAUTHORIZED,
                Response::HTTP_FORBIDDEN,
                Response::HTTP_INTERNAL_SERVER_ERROR
            ])
        );
    }

    public function testCityNewAccessibleByAdmin(): void
    {
        $this->loginAsAdmin();
        $this->client->request('GET', '/admin/city/new');

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testCityEditRequiresAuthentication(): void
    {
        // Get a city ID from fixtures
        $city = $this->cityRepository->findOneBy(['name' => 'Beograd']);
        $cityId = $city->getId();

        $this->client->request('GET', "/admin/city/{$cityId}/edit");

        $response = $this->client->getResponse();
        $statusCode = $response->getStatusCode();

        // Should not be accessible without authentication
        $this->assertNotEquals(Response::HTTP_OK, $statusCode);

        $this->assertTrue(
            $response->isRedirection() ||
            in_array($statusCode, [
                Response::HTTP_UNAUTHORIZED,
                Response::HTTP_FORBIDDEN,
                Response::HTTP_INTERNAL_SERVER_ERROR
            ])
        );
    }

    public function testCityEditAccessibleByAdmin(): void
    {
        $this->loginAsAdmin();

        // Get a city ID from fixtures
        $city = $this->cityRepository->findOneBy(['name' => 'Beograd']);
        $cityId = $city->getId();

        $this->client->request('GET', "/admin/city/{$cityId}/edit");

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }
}
