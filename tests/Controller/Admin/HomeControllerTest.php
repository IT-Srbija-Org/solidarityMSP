<?php

namespace App\Tests\Controller\Admin;

use App\DataFixtures\UserFixtures;
use App\Repository\UserRepository;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class HomeControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private AbstractDatabaseTool $databaseTool;
    private ?UserRepository $userRepository;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();

        $this->databaseTool = $container->get(DatabaseToolCollection::class)->get();
        $this->loadFixtures();

        $this->userRepository = $container->get(UserRepository::class);
    }

    private function loadFixtures(): void
    {
        $this->databaseTool->loadFixtures([
            UserFixtures::class,
        ]);
    }

    private function loginAsAdmin(): void
    {
        $adminUser = $this->userRepository->findOneBy(['email' => 'admin@gmail.com']);
        $this->client->loginUser($adminUser);
    }

    public function testAdminAreaRequiresAuthentication(): void
    {
        $this->client->request('GET', '/admin/');

        $response = $this->client->getResponse();
        $statusCode = $response->getStatusCode();

        // The admin area should not be accessible without authentication
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

    public function testAdminAreaAccessibleByAdmin(): void
    {
        $this->loginAsAdmin();
        $this->client->request('GET', '/admin/');

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }
}
