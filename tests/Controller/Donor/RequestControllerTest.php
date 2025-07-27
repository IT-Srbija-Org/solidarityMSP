<?php

namespace App\Tests\Controller\Donor;

use App\DataFixtures\UserFixtures;
use App\Entity\User;
use App\Repository\UserDonorRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;

class RequestControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private AbstractDatabaseTool $databaseTool;
    private ?EntityManagerInterface $entityManager;
    private ?UserRepository $userRepository;
    private ?UserDonorRepository $userDonorRepository;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();

        $this->databaseTool = $container->get(DatabaseToolCollection::class)->get();
        $this->loadFixtures();

        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->userRepository = $container->get(UserRepository::class);
        $this->userDonorRepository = $container->get(UserDonorRepository::class);
    }

    private function loadFixtures(): void
    {
        $this->databaseTool->loadFixtures([
            UserFixtures::class,
        ]);
    }

    private function getUser(string $email): ?User
    {
        return $this->userRepository->findOneBy(['email' => $email]);
    }

    private function getLoginUser(): ?UserInterface
    {
        return static::getContainer()->get('security.token_storage')->getToken()->getUser();
    }

    private function loginAsUser(string $email): void
    {
        $user = $this->userRepository->findOneBy(['email' => $email]);
        $this->client->loginUser($user);
    }

    private function removeUser(string $email): void
    {
        $user = $this->userRepository->findOneBy(['email' => $email]);
        if ($user) {
            $this->entityManager->remove($user);
            $this->entityManager->flush();
        }
    }

    public function testDonatePageAccess(): void
    {
        $this->client->request('GET', '/doniraj');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testDonateOnetimePageAccess(): void
    {
        $this->client->request('GET', '/jednokratna-donacija');
        $this->assertResponseRedirects('/registracija-donatora?action=donor_request_onetime', Response::HTTP_FOUND);
    }

    public function testSubscribePageAccess(): void
    {
        $this->client->request('GET', '/mesecna-donacija');
        $this->assertResponseRedirects('/registracija-donatora?action=donor_request_subscription', Response::HTTP_FOUND);
    }

    public function testSuccessPageRegister(): void
    {
        $this->client->request('GET', '/uspesna-registracija-donatora?action=donor_request_register');

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSelectorTextContains('h2', 'Potvrdi svoj email');
    }

    public function testSuccessPageSubscription(): void
    {
        $this->client->request('GET', '/uspesna-registracija-donatora?action=donor_request_subscription');

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSelectorTextContains('h2', 'Uspešno si se registrovo/la kao donator!');
        $this->assertSelectorTextContains('a.btn-primary', 'Podešavanje mesečne donacije');
    }

    public function testSuccessPageOnetime(): void
    {
        $this->client->request('GET', '/uspesna-registracija-donatora?action=donor_request_onetime');

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSelectorTextContains('h2', 'Uspešno si se registrovo/la kao donator!');
        $this->assertSelectorTextContains('a.btn-primary', 'Kreiraj instrukcije za uplatu');
    }

    public function testUnsubscribeWithoutToken(): void
    {
        $this->loginAsUser('korisnik@gmail.com');

        // Configure client to not catch exceptions
        $this->client->catchExceptions(false);

        try {
            // This should throw an access denied exception
            $this->client->request('GET', '/odjava-mesecnog-donatora');

            // If we get here (no exception), still check for HTTP 403
            $this->assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
        } catch (\Symfony\Component\Security\Core\Exception\AccessDeniedException $e) {
            // Expected exception, test passes
            $this->assertTrue(true, 'Expected AccessDeniedException was thrown');
        } catch (\Exception $e) {
            // Catch any other exceptions to ensure we reset client
            $this->fail('Unexpected exception thrown: '.get_class($e).' - '.$e->getMessage());
        } finally {
            // Reset to default behavior
            $this->client->catchExceptions(true);
        }
    }

    public function testUnsubscribeWithInvalidToken(): void
    {
        $this->loginAsUser('korisnik@gmail.com');

        // Configure client to not catch exceptions
        $this->client->catchExceptions(false);

        try {
            // This should throw an access denied exception
            $this->client->request('GET', '/odjava-mesecnog-donatora?_token=invalid');

            // If we get here (no exception), still check for HTTP 403
            $this->assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
        } catch (\Symfony\Component\Security\Core\Exception\AccessDeniedException $e) {
            // Expected exception, test passes
            $this->assertTrue(true, 'Expected AccessDeniedException was thrown');
        } catch (\Exception $e) {
            // Catch any other exceptions to ensure we reset client
            $this->fail('Unexpected exception thrown: '.get_class($e).' - '.$e->getMessage());
        } finally {
            // Reset to default behavior
            $this->client->catchExceptions(true);
        }
    }
}
