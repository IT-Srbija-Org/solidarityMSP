<?php

namespace App\Tests\Controller;

use App\DataFixtures\UserFixtures;
use App\Entity\User;
use App\Repository\UserRepository;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class DonorControllerTest extends WebTestCase
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
            UserFixtures::class
        ]);
    }

    private function loginAsUser(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'korisnik@gmail.com']);
        $this->client->loginUser($user);
    }

    public function testRedirectToLoginWhenNotAuthenticated(): void
    {
        $this->client->request('GET', '/prijava-donator');

        $this->assertTrue($this->client->getResponse()->isRedirect());
        $this->assertStringContainsString('/logovanje', $this->client->getResponse()->headers->get('Location'));
    }

    public function testSubscribeDonorForm(): void
    {
        $this->loginAsUser();
        $this->client->request('GET', '/prijava-donator');

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSelectorExists('form[name="user_donor"]');
    }

    public function testSuccessPageAccessible(): void
    {
        $this->loginAsUser();
        $this->client->request('GET', '/uspesna-registracija-donora');

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }
}