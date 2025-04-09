<?php

namespace App\Tests\Controller;

use App\DataFixtures\UserFixtures;
use App\Repository\UserRepository;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class DelegateControllerTest extends WebTestCase
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

    private function loginAsUser(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'korisnik@gmail.com']);
        $this->client->loginUser($user);
    }

    public function testRedirectToLoginWhenNotAuthenticated(): void
    {
        $this->client->request('GET', '/postani-delegat');

        $this->assertTrue($this->client->getResponse()->isRedirect());
        $this->assertStringContainsString('/logovanje', $this->client->getResponse()->headers->get('Location'));
    }

    public function testRequestAccessForm(): void
    {
        $this->loginAsUser();
        $this->client->request('GET', '/postani-delegat');

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSelectorExists('form[name="registration_delegate"]');
    }
}
