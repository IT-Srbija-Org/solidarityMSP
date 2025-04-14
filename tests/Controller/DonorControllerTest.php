<?php

namespace App\Tests\Controller;

use App\DataFixtures\UserFixtures;
use App\Entity\User;
use App\Repository\UserDonorRepository;
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
    private ?UserDonorRepository $userDonorRepository;

    private ?User $user;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();

        $this->databaseTool = $container->get(DatabaseToolCollection::class)->get();
        $this->loadFixtures();

        $this->userRepository = $container->get(UserRepository::class);
        $this->userDonorRepository = $container->get(UserDonorRepository::class);
    }

    private function loadFixtures(): void
    {
        $this->databaseTool->loadFixtures([
            UserFixtures::class,
        ]);
    }

    private function loginAsUser(): void
    {
        $this->user = $this->userRepository->findOneBy(['email' => 'korisnik@gmail.com']);
        $this->client->loginUser($this->user);
    }

    public function testRedirectToLoginWhenNotAuthenticated(): void
    {
        $this->client->request('GET', '/postani-donator');

        $this->assertTrue($this->client->getResponse()->isRedirect());
        $this->assertStringContainsString('/logovanje', $this->client->getResponse()->headers->get('Location'));
    }

    public function testSubscribeAndUnsubscribeDonorForm(): void
    {
        $this->loginAsUser();
        $crawler = $this->client->request('GET', '/postani-donator');

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSelectorExists('form[name="user_donor"]');

        // Subscribe
        $form = $crawler->selectButton('Sačuvaj')->form([
            'user_donor[isMonthly]' => 0,
            'user_donor[amount]' => 5000,
            'user_donor[comment]' => 'Test donation comment',
        ]);

        $this->client->submit($form);

        // Check email
        $this->assertEmailCount(1);
        $mailerMessage = $this->getMailerMessage();
        $this->assertEmailSubjectContains($mailerMessage, 'Potvrda registracije donora na Mrežu solidarnosti');

        // Check redirect
        $this->client->followRedirect();
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('/uspesna-registracija-donatora', $this->client->getRequest()->getUri());

        // Check are donor registered
        $userDonor = $this->userDonorRepository->findOneBy(['user' => $this->user]);
        $this->assertFalse($userDonor->isMonthly());
        $this->assertEquals(5000, $userDonor->getAmount());
        $this->assertEquals('Test donation comment', $userDonor->getComment());

        // Check success message
        $crawler = $this->client->request('GET', '/postani-donator');
        $this->assertSelectorTextContains('.alert-success', 'Već ste se prijavili na listu donatora. U slučaju da želite da promenite podatke unesite nove u formu ispod.');
        $this->assertSelectorTextContains('.alert-error', 'Ako želite da se odjavite sa liste donatora kliknite na sledeći link');

        // Unsubscribe
        $unsubscribeLink = $crawler->filter('.alert-error a')->attr('href');
        $this->client->request('GET', $unsubscribeLink);
        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();

        $userDonor = $this->userDonorRepository->findOneBy(['user' => $this->user]);
        $this->assertNull($userDonor);
    }

    public function testSuccessMessageRoute(): void
    {
        $this->loginAsUser();
        $this->client->request('GET', '/uspesna-registracija-donatora');

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSelectorTextContains('h2', 'Uspešno ste se registrovali kao donator!');
    }

    public function testUnsubscribeWithoutToken(): void
    {
        $this->loginAsUser();

        // Configure client to not catch exceptions
        $this->client->catchExceptions(false);

        try {
            // This should throw an access denied exception
            $this->client->request('GET', '/odjava-donatora');

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
        $this->loginAsUser();

        // Configure client to not catch exceptions
        $this->client->catchExceptions(false);

        try {
            // This should throw an access denied exception
            $this->client->request('GET', '/odjava-donatora?_token=invalid');

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
