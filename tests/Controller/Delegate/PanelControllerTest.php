<?php

namespace App\Tests\Controller\Delegate;

use App\DataFixtures\CityFixtures;
use App\DataFixtures\DamagedEducatorFixtures;
use App\DataFixtures\DamagedEducatorPeriodFixtures;
use App\DataFixtures\SchoolFixtures;
use App\DataFixtures\SchoolTypeFixtures;
use App\DataFixtures\UserDelegateRequestFixtures;
use App\DataFixtures\UserDelegateSchoolFixtures;
use App\DataFixtures\UserFixtures;
use App\Entity\DamagedEducator;
use App\Repository\DamagedEducatorPeriodRepository;
use App\Repository\DamagedEducatorRepository;
use App\Repository\UserDelegateSchoolRepository;
use App\Repository\UserRepository;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class PanelControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private AbstractDatabaseTool $databaseTool;
    private ?UserRepository $userRepository;
    private ?DamagedEducatorPeriodRepository $damagedEducatorPeriodRepository;
    private ?DamagedEducatorRepository $damagedEducatorRepository;
    private ?UserDelegateSchoolRepository $userDelegateSchoolRepository;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();

        $this->databaseTool = $container->get(DatabaseToolCollection::class)->get();
        $this->loadFixtures();

        $this->userRepository = $container->get(UserRepository::class);
        $this->damagedEducatorRepository = $container->get(DamagedEducatorRepository::class);
        $this->damagedEducatorPeriodRepository = $container->get(DamagedEducatorPeriodRepository::class);
        $this->userDelegateSchoolRepository = $container->get(UserDelegateSchoolRepository::class);
    }

    private function loadFixtures(): void
    {
        $this->databaseTool->loadFixtures([
            UserFixtures::class,
            CityFixtures::class,
            SchoolTypeFixtures::class,
            SchoolFixtures::class,
            UserDelegateRequestFixtures::class,
            UserDelegateSchoolFixtures::class,
            DamagedEducatorPeriodFixtures::class,
            DamagedEducatorFixtures::class,
        ]);
    }

    private function loginAsUser(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'korisnik@gmail.com']);
        $this->client->loginUser($user);
    }

    private function loginAsDelegate(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'delegat@gmail.com']);
        if (!$user) {
            throw new \RuntimeException('Delegate user not found. Check UserFixtures for the correct email.');
        }

        $this->client->loginUser($user);
    }

    public function testDamagedEducatorPeriod(): void
    {
        $this->loginAsDelegate();
        $this->client->request('GET', '/delegat/odabir-perioda');

        // Just check that the page loads with 200 OK status
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testRedirectWithoutPeriodParameter(): void
    {
        $this->loginAsDelegate();
        $this->client->request('GET', '/delegat/osteceni');

        $this->assertTrue($this->client->getResponse()->isRedirect());
        $this->assertStringContainsString('/delegat/odabir-perioda', $this->client->getResponse()->headers->get('Location'));
    }

    public function testDamagedEducatorsList(): void
    {
        $this->loginAsDelegate();

        $period = $this->damagedEducatorPeriodRepository->findOneBy([]);
        $crawler = $this->client->request('GET', '/delegat/osteceni', ['period' => $period->getId()]);

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertCount(1, $crawler->filter('table'));
        $this->assertSelectorTextContains('a.btn-primary', 'Dodaj');
    }

    public function testPeriodNewDamagedEducatorForm(): void
    {
        $this->loginAsDelegate();

        $period = $this->damagedEducatorPeriodRepository->findOneBy([]);
        $this->client->request('GET', '/delegat/prijavi-ostecenog', ['period' => $period->getId()]);

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSelectorExists('form');
    }

    public function testNewDamagedEducatorForm(): void
    {
        $this->loginAsDelegate();

        $period = $this->damagedEducatorPeriodRepository->findOneBy([]);
        $user = $this->userRepository->findOneBy(['email' => 'delegat@gmail.com']);
        $userDelegateSchool = $this->userDelegateSchoolRepository->findOneBy(['user' => $user]);

        $crawler = $this->client->request('GET', '/delegat/prijavi-ostecenog?period='.$period->getId());
        $form = $crawler->filter('form[name="damaged_educator_edit"]')->form([
            'damaged_educator_edit[name]' => 'Milan Janjic',
            'damaged_educator_edit[school]' => $userDelegateSchool->getSchool()->getId(),
            'damaged_educator_edit[city]' => $userDelegateSchool->getSchool()->getCity()->getId(),
            'damaged_educator_edit[amount]' => 10000,
            'damaged_educator_edit[accountNumber]' => '265104031000361092',
        ]);

        $this->client->submit($form);

        $this->assertTrue($this->client->getResponse()->isRedirect());
        $this->client->followRedirect();

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testEditDamagedEducatorForm(): void
    {
        $this->loginAsDelegate();

        $period = $this->damagedEducatorPeriodRepository->findOneBy([]);
        $user = $this->userRepository->findOneBy(['email' => 'delegat@gmail.com']);
        $userDelegateSchool = $this->userDelegateSchoolRepository->findOneBy(['user' => $user]);
        $damagedEducator = $this->damagedEducatorRepository->findOneBy(['school' => $userDelegateSchool->getSchool(), 'period' => $period]);

        $crawler = $this->client->request('GET', '/delegat/osteceni/'.$damagedEducator->getId().'/izmeni-podatke');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $form = $crawler->filter('form[name="damaged_educator_edit"]')->form([
            'damaged_educator_edit[name]' => 'Milan Janjic',
            'damaged_educator_edit[school]' => $userDelegateSchool->getSchool()->getId(),
            'damaged_educator_edit[amount]' => 50000,
            'damaged_educator_edit[accountNumber]' => '265104031000361092',
        ]);

        $this->client->submit($form);

        $this->assertTrue($this->client->getResponse()->isRedirect());
        $this->client->followRedirect();

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $damagedEducator = $this->damagedEducatorRepository->findOneBy(['accountNumber' => '265104031000361092']);
        $this->assertEquals($damagedEducator->getAmount(), 50000);
    }

    public function testDeleteDamagedEducatorForm(): void
    {
        $this->loginAsDelegate();

        $period = $this->damagedEducatorPeriodRepository->findOneBy([]);
        $user = $this->userRepository->findOneBy(['email' => 'delegat@gmail.com']);
        $userDelegateSchool = $this->userDelegateSchoolRepository->findOneBy(['user' => $user]);
        $damagedEducator = $this->damagedEducatorRepository->findOneBy(['school' => $userDelegateSchool->getSchool(), 'period' => $period]);

        $crawler = $this->client->request('GET', '/delegat/osteceni/'.$damagedEducator->getId().'/brisanje');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $form = $crawler->filter('form[name="damaged_educator_delete"]')->form([
            'damaged_educator_delete[confirm]' => true,
            'damaged_educator_delete[comment]' => 'Test komentar',
        ]);

        $this->client->submit($form);

        $this->assertTrue($this->client->getResponse()->isRedirect());
        $this->client->followRedirect();

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $damagedEducator = $this->damagedEducatorRepository->find($damagedEducator->getId());
        $this->assertEquals(DamagedEducator::STATUS_DELETED, $damagedEducator->getStatus());
    }
}
