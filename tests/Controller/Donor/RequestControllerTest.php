<?php

namespace App\Tests\Controller\Donor;

use App\DataFixtures\CityFixtures;
use App\DataFixtures\DamagedEducatorFixtures;
use App\DataFixtures\DamagedEducatorPeriodFixtures;
use App\DataFixtures\SchoolFixtures;
use App\DataFixtures\SchoolTypeFixtures;
use App\DataFixtures\TransactionFixtures;
use App\DataFixtures\UserDelegateRequestFixtures;
use App\DataFixtures\UserDelegateSchoolFixtures;
use App\DataFixtures\UserDonorFixtures;
use App\DataFixtures\UserFixtures;
use App\Entity\User;
use App\Entity\UserDonor;
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
            CityFixtures::class,
            DamagedEducatorPeriodFixtures::class,
            SchoolTypeFixtures::class,
            SchoolFixtures::class,
            UserDelegateRequestFixtures::class,
            UserDelegateSchoolFixtures::class,
            UserDonorFixtures::class,
            DamagedEducatorFixtures::class,
            TransactionFixtures::class,
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

    public function testNewUserSubscribeAndRegistrationAndVerification(): void
    {
        $email = 'korisnik@gmail.com';
        $this->removeUser($email);

        $crawler = $this->client->request('GET', '/registracija-donatora?action=donor_request_subscription');

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSelectorExists('form[name="user_donor_register"]');

        // Populate form
        $form = $crawler->filter('form[name="user_donor_register"]')->form([
            'user_donor_register[firstName]' => 'Marko',
            'user_donor_register[lastName]' => 'Markovic',
            'user_donor_register[email]' => $email,
        ]);

        $this->client->submit($form);

        // Check are register verification email sent
        $this->assertEmailCount(1);
        $mailerMessage = $this->getMailerMessage();
        $this->assertEmailSubjectContains($mailerMessage, 'Link za potvrdu email adrese');

        // Check are user registered
        $user = $this->getUser($email);
        $this->assertEquals('Marko', $user->getFirstName());
        $this->assertEquals('Markovic', $user->getLastName());
        $this->assertFalse($user->isEmailVerified());

        // Extract verified link
        $crawler = new Crawler($mailerMessage->getHtmlBody());
        $verifiedLink = $crawler->filter('#link')->attr('href');

        // Click on verified link from email
        $this->client->request('GET', $verifiedLink);

        // Check are donor success email send
        $this->assertEmailCount(1);
        $mailerMessage = $this->getMailerMessage();
        $this->assertEmailSubjectContains($mailerMessage, 'Potvrda registracije donatora na Mrežu solidarnosti');

        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();

        // Check are user now login and verified
        $user = $this->getLoginUser();
        $this->assertNotNull($user);
        $this->assertTrue($user->isEmailVerified());

        // Check are user redirected to success page
        $this->assertStringContainsString('/uspesna-registracija-donatora?action=donor_request_subscription', $this->client->getRequest()->getUri());

        // Get action link from page
        $crawler = new Crawler($this->client->getResponse()->getContent());
        $link = $crawler->filter('#link')->attr('href');
        $this->assertEquals('/mesecna-donacija', $link);

        // Click on action link
        $crawler = $this->client->request('GET', $link);
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        // Populate form
        $form = $crawler->filter('form[name="user_donor_subscription"]')->form([
            'user_donor_subscription[amount]' => 10000,
            'user_donor_subscription[schoolType]' => UserDonor::SCHOOL_TYPE_ALL,
        ]);

        // Submit form
        $this->client->submit($form);

        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();

        // Check are user donor saved
        $userDonor = $this->userDonorRepository->findOneBy(['user' => $user]);
        $this->assertEquals(10000, $userDonor->getAmount());
        $this->assertEquals(UserDonor::SCHOOL_TYPE_ALL, $userDonor->getSchoolType());

        // Check are user redirected to right page
        $this->assertStringContainsString('/instrukcije-za-uplatu', $this->client->getRequest()->getUri());

        // Try to unsubscribe
        $this->client->request('GET', '/mesecna-donacija');
        $crawler = new Crawler($this->client->getResponse()->getContent());

        // Unsubscribe
        $unsubscribeLink = $crawler->filter('.test-link1')->attr('href');
        $this->client->request('GET', $unsubscribeLink);
        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();

        $userDonor = $this->userDonorRepository->findOneBy(['user' => $user]);
        $this->assertNull($userDonor);
    }

    public function testNewUserOnetimeAndRegistrationAndVerification(): void
    {
        $email = 'korisnik@gmail.com';
        $this->removeUser($email);

        $crawler = $this->client->request('GET', '/registracija-donatora?action=donor_request_onetime');

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSelectorExists('form[name="user_donor_register"]');

        // Populate form
        $form = $crawler->filter('form[name="user_donor_register"]')->form([
            'user_donor_register[firstName]' => 'Marko',
            'user_donor_register[lastName]' => 'Markovic',
            'user_donor_register[email]' => $email,
        ]);

        $this->client->submit($form);

        // Check are register verification email sent
        $this->assertEmailCount(1);
        $mailerMessage = $this->getMailerMessage();
        $this->assertEmailSubjectContains($mailerMessage, 'Link za potvrdu email adrese');

        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();

        // Check are user redirected to success page
        $this->assertStringContainsString('/uspesna-registracija-donatora?action=donor_request_register', $this->client->getRequest()->getUri());

        // Check are user registered
        $user = $this->getUser($email);
        $this->assertEquals('Marko', $user->getFirstName());
        $this->assertEquals('Markovic', $user->getLastName());
        $this->assertFalse($user->isEmailVerified());

        // Extract verified link
        $crawler = new Crawler($mailerMessage->getHtmlBody());
        $verifiedLink = $crawler->filter('#link')->attr('href');

        // Click on verified link from email
        $this->client->request('GET', $verifiedLink);

        // Check are donor success email send
        $this->assertEmailCount(1);
        $mailerMessage = $this->getMailerMessage();
        $this->assertEmailSubjectContains($mailerMessage, 'Potvrda registracije donatora na Mrežu solidarnosti');

        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();

        // Check are user now login and verified
        $user = $this->getLoginUser();
        $this->assertNotNull($user);
        $this->assertTrue($user->isEmailVerified());

        // Check are user redirected to success page
        $this->assertStringContainsString('/uspesna-registracija-donatora?action=donor_request_onetime', $this->client->getRequest()->getUri());

        // Get action link from page
        $crawler = new Crawler($this->client->getResponse()->getContent());
        $link = $crawler->filter('#link')->attr('href');
        $this->assertEquals('/kreiraj-instrukcije-za-uplatu', $link);

        // Click on action link
        $crawler = $this->client->request('GET', $link);
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        // Populate form
        $form = $crawler->filter('form[name="transaction_create"]')->form([
            'transaction_create[amount]' => 10000,
            'transaction_create[schoolType]' => UserDonor::SCHOOL_TYPE_ALL,
        ]);

        // Submit form
        $this->client->submit($form);

        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();

        // Check are user redirected to right page
        $this->assertStringContainsString('/instrukcije-za-uplatu', $this->client->getRequest()->getUri());

        $userDonor = $this->userDonorRepository->findOneBy(['user' => $user]);
        $this->assertNull($userDonor);
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
        $this->assertSelectorTextContains('h2', 'Uspešno si se registrovao/la kao donator!');
        $this->assertSelectorTextContains('a.btn-primary', 'Podešavanje mesečne donacije');
    }

    public function testSuccessPageOnetime(): void
    {
        $this->client->request('GET', '/uspesna-registracija-donatora?action=donor_request_onetime');

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSelectorTextContains('h2', 'Uspešno si se registrovao/la kao donator!');
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
