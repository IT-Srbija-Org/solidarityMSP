<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\User\UserInterface;

class RegistrationControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private ?EntityManagerInterface $entityManager;
    private ?UserRepository $userRepository;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();

        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->userRepository = $container->get(UserRepository::class);
    }

    private function getLoginUser(): ?UserInterface
    {
        return static::getContainer()->get('security.token_storage')->getToken()->getUser();
    }

    private function removeUser(string $email): void
    {
        $user = $this->userRepository->findOneBy(['email' => $email]);
        if ($user) {
            $this->entityManager->remove($user);
            $this->entityManager->flush();
        }
    }

    public function testResendVerificationWithoutEmail(): void
    {
        $this->client->request('GET', '/ponovna-verifikacija-email');
        $this->client->followRedirect();

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.alert.alert-error', 'Email nije prosleđen.');
    }

    public function testResendVerificationForNonExistentUser(): void
    {
        $this->client->request('GET', '/ponovna-verifikacija-email', ['email' => 'nonexistent@example.com']);
        if ($this->client->getResponse()->isRedirection()) {
            $this->client->followRedirect();
            $this->assertResponseIsSuccessful();
            $this->assertSelectorTextContains('.alert.alert-error', 'Korisnik sa ovom email adresom ne postoji.');
        } else {
            $status = $this->client->getResponse()->getStatusCode();
            $this->fail('Expected redirect for non-existent user, got status: '.$status);
        }
    }

    public function testResendVerificationForVerifiedUser(): void
    {
        $email = 'verified@example.com';
        $this->removeUser($email);

        $userClass = $this->entityManager->getClassMetadata(User::class)->getName();
        $user = new $userClass();
        $user->setFirstName('Verified');
        $user->setLastName('User');
        $user->setEmail($email);
        $user->setIsEmailVerified(true);
        $user->setIsActive(true);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->client->request('GET', '/ponovna-verifikacija-email', ['email' => $email]);
        $this->client->followRedirect();

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.alert.alert-success', 'Vaš nalog je već potvrđen. Možete se prijaviti.');
    }

    public function testResendVerificationTooFrequently(): void
    {
        $email = 'unverified@example.com';
        $this->removeUser($email);

        $userClass = $this->entityManager->getClassMetadata(User::class)->getName();
        $user = new $userClass();
        $user->setFirstName('Unverified');
        $user->setLastName('User');
        $user->setEmail($email);
        $user->setIsEmailVerified(false);
        $user->setIsActive(true);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // First request
        $this->client->request('GET', '/ponovna-verifikacija-email', ['email' => $email]);
        $this->client->followRedirect();

        // Second request immediately after
        $this->client->request('GET', '/ponovna-verifikacija-email', ['email' => $email]);
        $this->client->followRedirect();

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.alert.alert-error', 'Molimo sačekajte još');
    }
}
