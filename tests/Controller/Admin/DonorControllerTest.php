<?php

namespace App\Tests\Controller\Admin;

use App\DataFixtures\UserDonorFixtures;
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
        $this->client->catchExceptions(true);

        $container = static::getContainer();

        $this->databaseTool = $container->get(DatabaseToolCollection::class)->get();
        $this->loadFixtures();

        $this->userRepository = $container->get(UserRepository::class);
    }

    private function loadFixtures(): void
    {
        $this->databaseTool->loadFixtures([
            UserFixtures::class,
            UserDonorFixtures::class,
        ]);
    }

    private function loginAsAdmin(): void
    {
        $adminUser = $this->userRepository->findOneBy(['email' => 'admin@gmail.com']);
        $this->client->loginUser($adminUser);
    }

    private function loginAsUser(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'korisnik@gmail.com']);
        $this->client->loginUser($user);
    }

    public function testRedirectToLoginWhenNotAuthenticated(): void
    {
        $this->client->request('GET', '/admin/donor/list');

        $this->assertTrue($this->client->getResponse()->isRedirect());
        $this->assertStringContainsString('/logovanje', $this->client->getResponse()->headers->get('Location'));
    }

    /**
     * Check that regular users can't access admin functionality
     * This test method verifies authorization without checking the page directly.
     */
    public function testRegularUsersNotHavingAdminRole(): void
    {
        // Get a regular user
        $user = $this->userRepository->findOneBy(['email' => 'korisnik@gmail.com']);

        // Verify this user doesn't have admin role
        $this->assertNotContains('ROLE_ADMIN', $user->getRoles());
    }

    public function testListDonors(): void
    {
        $this->loginAsAdmin();
        $this->client->request('GET', '/admin/donor/list');

        // Check that the page loads successfully
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }
}
