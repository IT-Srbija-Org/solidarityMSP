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

    public function testNonAuthenticatedAccess(): void
    {
        $this->client->request('GET', '/postani-donator');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testNewUserSubscribeAndRegistrationAndVerification(): void
    {
        $email = 'korisnik@gmail.com';
        $this->removeUser($email);

        $crawler = $this->client->request('GET', '/postani-donator');

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSelectorExists('form[name="user_donor"]');

        // Subscribe
        $form = $crawler->filter('form[name="user_donor"]')->form([
            'user_donor[firstName]' => 'Marko',
            'user_donor[lastName]' => 'Markovic',
            'user_donor[email]' => $email,
            'user_donor[isMonthly]' => 1,
            'user_donor[amount]' => 10000,
            'user_donor[comment]' => 'Test donation comment',
        ]);

        $this->client->submit($form);

        // Check are register verification email sent
        $this->assertEmailCount(1);
        $mailerMessage = $this->getMailerMessage();
        $this->assertEmailSubjectContains($mailerMessage, 'Link za potvrdu email adrese');

        // Check redirect
        $this->client->followRedirect();
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('/uspesna-registracija-donatora', $this->client->getRequest()->getUri());

        // Check are user registered
        $user = $this->getUser($email);
        $this->assertEquals('Marko', $user->getFirstName());
        $this->assertEquals('Markovic', $user->getLastName());
        $this->assertFalse($user->isEmailVerified());

        // Check are donor data saved
        $userDonor = $this->userDonorRepository->findOneBy(['user' => $user]);
        $this->assertTrue($userDonor->isMonthly());
        $this->assertEquals(10000, $userDonor->getAmount());
        $this->assertEquals('Test donation comment', $userDonor->getComment());

        // Extract verified link
        $crawler = new Crawler($mailerMessage->getHtmlBody());
        $verifiedLink = $crawler->filter('#link')->attr('href');

        // Click on verified link from email
        $this->client->request('GET', $verifiedLink);

        // Check are donor success email send
        $this->assertEmailCount(1);
        $mailerMessage = $this->getMailerMessage();
        $this->assertEmailSubjectContains($mailerMessage, 'Potvrda registracije donora na Mrežu solidarnosti');

        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();

        // Check are user now login and verified
        $user = $this->getLoginUser();
        $this->assertNotNull($user);
        $this->assertTrue($user->isEmailVerified());

        // Check success message
        $crawler = $this->client->request('GET', '/postani-donator');

        // Unsubscribe
        $unsubscribeLink = $crawler->filter('.test-link1')->attr('href');
        $this->client->request('GET', $unsubscribeLink);
        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();

        $userDonor = $this->userDonorRepository->findOneBy(['user' => $user]);
        $this->assertNull($userDonor);
    }

    public function testSuccessMessageRoute(): void
    {
        $this->loginAsUser('korisnik@gmail.com');
        $this->client->request('GET', '/uspesna-registracija-donatora');

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSelectorTextContains('h2', 'Uspešno ste se registrovali kao donator!');
    }

    public function testNotAuthenticatedSuccessMessageRoute(): void
    {
        $this->client->request('GET', '/uspesna-registracija-donatora');

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSelectorTextContains('h2', 'Potvrdite svoj email kako bi donacija bila uspešna');
    }

    public function testUnsubscribeWithoutToken(): void
    {
        $this->loginAsUser('korisnik@gmail.com');

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
        $this->loginAsUser('korisnik@gmail.com');

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
