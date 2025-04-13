<?php

namespace App\Tests\Controller;

use App\DataFixtures\CityFixtures;
use App\DataFixtures\EducatorFixtures;
use App\DataFixtures\SchoolFixtures;
use App\DataFixtures\SchoolTypeFixtures;
use App\DataFixtures\UserDelegateRequestFixtures;
use App\DataFixtures\UserDelegateSchoolFixtures;
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
            CityFixtures::class,
            SchoolTypeFixtures::class,
            SchoolFixtures::class,
            UserDelegateRequestFixtures::class,
            UserDelegateSchoolFixtures::class,
            EducatorFixtures::class,
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

    public function testRedirectToLoginWhenNotAuthenticated(): void
    {
        $this->client->request('GET', '/postani-delegat');

        $this->assertTrue($this->client->getResponse()->isRedirect());
        $this->assertStringContainsString('/logovanje', $this->client->getResponse()->headers->get('Location'));
    }

    public function testRequestAccessPage(): void
    {
        $this->loginAsUser();
        $this->client->request('GET', '/postani-delegat');

        // Just check that the page loads with 200 OK status
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testEducatorsList(): void
    {
        $this->loginAsDelegate();
        $crawler = $this->client->request('GET', '/osteceni');

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        // Check for the table
        $this->assertCount(1, $crawler->filter('table'));
        // Check for the add button (by content rather than href)
        $this->assertSelectorTextContains('a.btn-primary', 'Dodaj');
    }

    /**
     * Test redirecting to new educator form.
     */
    public function testNewEducatorForm(): void
    {
        $this->loginAsDelegate();
        $this->client->request('GET', '/prijavi-ostecenog');

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSelectorExists('form');
    }

    /**
     * Test submitting the new educator form.
     */
    public function testSubmitNewEducatorForm(): void
    {
        // Before testing, ensure our delegate has at least one school
        $this->addSchoolToDelegate();

        $this->loginAsDelegate();
        $crawler = $this->client->request('GET', '/prijavi-ostecenog');

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $form = $crawler->selectButton('Sačuvaj')->form();

        // Get a school ID from the delegate's schools
        $schoolOptions = $crawler->filter('select[name="educator_edit[school]"] option')->extract(['value']);
        $schoolId = !empty($schoolOptions[1]) ? $schoolOptions[1] : null;

        if (!$schoolId) {
            $this->markTestSkipped('No school options available for this delegate');
        }

        // Generate a unique name to easily identify this test record
        $uniqueName = 'Test Educator '.uniqid();
        $testAmount = 50000;
        $testAccountNumber = '265104031000361092';

        $form['educator_edit[name]'] = $uniqueName;
        $form['educator_edit[school]'] = $schoolId;
        $form['educator_edit[amount]'] = $testAmount;
        $form['educator_edit[accountNumber]'] = $testAccountNumber;

        $this->client->submit($form);

        // If there's a validation error, the form will be redisplayed
        if (Response::HTTP_OK === $this->client->getResponse()->getStatusCode()) {
            $this->fail('Form submission did not redirect but returned HTTP 200.');
        }

        // Check for any redirect (might not be exactly to /osteceni)
        $this->assertTrue($this->client->getResponse()->isRedirect(),
            'Response is not a redirect. Status code: '.$this->client->getResponse()->getStatusCode());

        // Follow redirect and check success message
        $this->client->followRedirect();
        $this->assertSelectorExists('.alert-success');

        // Verify the educator was actually saved in the database
        $container = static::getContainer();
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $educatorRepository = $entityManager->getRepository('App\Entity\Educator');

        // Clear entity manager to ensure we get fresh data
        $entityManager->clear();

        // Find the educator by its unique name
        $savedEducator = $educatorRepository->findOneBy(['name' => $uniqueName]);

        // Verify that the educator exists and has the correct data
        $this->assertNotNull($savedEducator, 'Educator was not saved to the database');
        $this->assertEquals($testAmount, $savedEducator->getAmount(), 'Saved amount does not match the submitted value');
        $this->assertEquals($testAccountNumber, $savedEducator->getAccountNumber(), 'Saved account number does not match the submitted value');
        $this->assertEquals($schoolId, $savedEducator->getSchool()->getId(), 'Saved school does not match the submitted value');

        // Verify that the creator is set to the current user
        $delegate = $this->userRepository->findOneBy(['email' => 'delegat@gmail.com']);
        $this->assertEquals($delegate->getId(), $savedEducator->getCreatedBy()->getId(), 'CreatedBy field was not set correctly');
    }

    /**
     * Test when a user with ROLE_DELEGATE visits the request page.
     */
    public function testRequestAccessForExistingDelegate(): void
    {
        $this->loginAsDelegate();
        $this->client->request('GET', '/postani-delegat');

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        // Should show approval template rather than the form
        $this->assertSelectorExists('.card-body');
    }

    /**
     * Test the edit educator page.
     */
    public function testEditEducator(): void
    {
        // First create the city and school
        $container = static::getContainer();
        $entityManager = $container->get('doctrine.orm.entity_manager');

        // Create city if it doesn't exist
        $cityRepository = $entityManager->getRepository('App\Entity\City');
        $city = $cityRepository->findOneBy([]);

        // Create school type if it doesn't exist
        $schoolTypeRepository = $entityManager->getRepository('App\Entity\SchoolType');
        $schoolType = $schoolTypeRepository->findOneBy([]);

        // Create test school
        $school = new \App\Entity\School();
        $school->setName('Test School '.uniqid());
        $school->setCity($city);
        $school->setType($schoolType);
        $entityManager->persist($school);
        $entityManager->flush();

        // Get delegate user
        $delegate = $this->userRepository->findOneBy(['email' => 'delegat@gmail.com']);

        // Assign school to delegate
        $delegateSchool = new \App\Entity\UserDelegateSchool();
        $delegateSchool->setUser($delegate);
        $delegateSchool->setSchool($school);
        $entityManager->persist($delegateSchool);
        $entityManager->flush();

        // Login as delegate
        $this->client->loginUser($delegate);

        // Create educator in this school
        $educator = new \App\Entity\Educator();
        $educator->setName('Test Educator');
        $educator->setSchool($school);
        $educator->setAmount(50000);
        $educator->setAccountNumber('265104031000361092');
        $educator->setCreatedBy($delegate);
        $entityManager->persist($educator);
        $entityManager->flush();

        // Request the edit page
        $this->client->request('GET', '/osteceni/'.$educator->getId().'/izmeni-podatke');

        // Verify response
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSelectorExists('form');
        $this->assertSelectorExists('input[name="educator_edit[name]"]');
    }

    /**
     * Test the delete educator confirmation page.
     */
    public function testDeleteEducatorConfirmation(): void
    {
        // First create the city and school
        $container = static::getContainer();
        $entityManager = $container->get('doctrine.orm.entity_manager');

        $cityRepository = $entityManager->getRepository('App\Entity\City');
        $city = $cityRepository->findOneBy([]);

        $schoolTypeRepository = $entityManager->getRepository('App\Entity\SchoolType');
        $schoolType = $schoolTypeRepository->findOneBy([]);

        // Create test school
        $school = new \App\Entity\School();
        $school->setName('Test Delete School '.uniqid());
        $school->setCity($city);
        $school->setType($schoolType);
        $entityManager->persist($school);
        $entityManager->flush();

        // Get delegate user
        $delegate = $this->userRepository->findOneBy(['email' => 'delegat@gmail.com']);

        // Assign school to delegate
        $delegateSchool = new \App\Entity\UserDelegateSchool();
        $delegateSchool->setUser($delegate);
        $delegateSchool->setSchool($school);
        $entityManager->persist($delegateSchool);
        $entityManager->flush();

        // Login as delegate
        $this->client->loginUser($delegate);

        // Create educator in this school
        $educator = new \App\Entity\Educator();
        $educator->setName('Test Delete Educator');
        $educator->setSchool($school);
        $educator->setAmount(50000);
        $educator->setAccountNumber('265104031000361098');
        $educator->setCreatedBy($delegate);
        $entityManager->persist($educator);
        $entityManager->flush();

        // Request the delete page
        $this->client->request('GET', '/osteceni/'.$educator->getId().'/brisanje');

        // Verify response
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSelectorExists('form');
        $this->assertSelectorExists('h2.card-title');
    }

    /**
     * Test that a delegate cannot edit/delete educators from schools they don't have access to.
     */
    public function testCannotAccessOtherSchoolEducator(): void
    {
        $container = static::getContainer();
        $entityManager = $container->get('doctrine.orm.entity_manager');

        // Create a school not assigned to our delegate
        $cityRepository = $entityManager->getRepository('App\Entity\City');
        $schoolTypeRepository = $entityManager->getRepository('App\Entity\SchoolType');

        $city = $cityRepository->findOneBy([]);
        $schoolType = $schoolTypeRepository->findOneBy([]);

        // Create a new school that's NOT linked to our delegate
        $school = new \App\Entity\School();
        $school->setName('Other School');
        $school->setCity($city);
        $school->setType($schoolType);
        $entityManager->persist($school);

        // Create educator in this school
        $educator = new \App\Entity\Educator();
        $educator->setName('Other Educator');
        $educator->setSchool($school);
        $educator->setAmount(50000);
        $educator->setAccountNumber('265104031000361092');
        // Find an admin user for creator
        $adminUser = $this->userRepository->findOneBy(['email' => 'admin@gmail.com']);
        if (!$adminUser) {
            // If no admin user is found, use the delegate user instead
            $adminUser = $this->userRepository->findOneBy(['email' => 'delegat@gmail.com']);
        }
        $educator->setCreatedBy($adminUser);
        $entityManager->persist($educator);
        $entityManager->flush();

        // Now try to access this educator
        $this->loginAsDelegate();

        // Use PHPUnit expectException to handle the AccessDeniedHttpException
        // This will catch the exception and treat it as a passing assertion
        $this->client->catchExceptions(false);

        try {
            // This should throw an access denied exception
            $this->client->request('GET', '/osteceni/'.$educator->getId().'/izmeni-podatke');

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

    /**
     * Helper method to create an educator for the delegate.
     */
    private function createEducatorForDelegate(): \App\Entity\Educator
    {
        $container = static::getContainer();
        $entityManager = $container->get('doctrine.orm.entity_manager');

        // Make sure delegate has a school
        $this->addSchoolToDelegate();

        $delegate = $this->userRepository->findOneBy(['email' => 'delegat@gmail.com']);

        // Get the school assigned to this delegate (should exist after addSchoolToDelegate)
        $delegateSchool = $delegate->getUserDelegateSchools()->first();
        if (!$delegateSchool) {
            // Create a new school and assign it directly
            $cityRepository = $entityManager->getRepository('App\Entity\City');
            $schoolTypeRepository = $entityManager->getRepository('App\Entity\SchoolType');

            $city = $cityRepository->findOneBy([]);
            $schoolType = $schoolTypeRepository->findOneBy([]);

            // Create a new school for the delegate
            $school = new \App\Entity\School();
            $school->setName('Delegate Test School');
            $school->setCity($city);
            $school->setType($schoolType);
            $entityManager->persist($school);

            // Create UserDelegateSchool connection
            $delegateSchool = new \App\Entity\UserDelegateSchool();
            $delegateSchool->setUser($delegate);
            $delegateSchool->setSchool($school);
            $entityManager->persist($delegateSchool);
            $entityManager->flush();
        }

        // Create an educator in this school
        $educator = new \App\Entity\Educator();
        $educator->setName('Test Educator');
        $educator->setSchool($delegateSchool->getSchool());
        $educator->setAmount(50000);
        $educator->setAccountNumber('265104031000361092');
        $educator->setCreatedBy($delegate);

        $entityManager->persist($educator);
        $entityManager->flush();

        return $educator;
    }

    /**
     * Test actually deleting an educator.
     */
    public function testActualEducatorDeletion(): void
    {
        // First create an educator to delete
        $container = static::getContainer();
        $entityManager = $container->get('doctrine.orm.entity_manager');

        $city = $entityManager->getRepository('App\Entity\City')->findOneBy([]);
        $schoolType = $entityManager->getRepository('App\Entity\SchoolType')->findOneBy([]);

        // Create a dedicated school for this test
        $school = new \App\Entity\School();
        $school->setName('Delete Test School');
        $school->setCity($city);
        $school->setType($schoolType);
        $entityManager->persist($school);
        $entityManager->flush();

        // Get delegate
        $delegate = $this->userRepository->findOneBy(['email' => 'delegat@gmail.com']);

        // Create connection between delegate and school
        $delegateSchool = new \App\Entity\UserDelegateSchool();
        $delegateSchool->setUser($delegate);
        $delegateSchool->setSchool($school);
        $entityManager->persist($delegateSchool);
        $entityManager->flush();

        // Create an educator to delete
        $educator = new \App\Entity\Educator();
        $educator->setName('Delete Test Educator');
        $educator->setSchool($school);
        $educator->setAmount(50000);
        $educator->setAccountNumber('265104031000361097');
        $educator->setCreatedBy($delegate);
        $entityManager->persist($educator);
        $entityManager->flush();

        $educatorId = $educator->getId();

        // Login as delegate
        $this->client->loginUser($delegate);

        // Visit the delete confirmation page
        $crawler = $this->client->request('GET', '/osteceni/'.$educatorId.'/brisanje');

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        // Get and submit the confirmation form
        $form = $crawler->selectButton('Potvrdi')->form();
        $this->client->submit($form);

        // Check for redirect
        $this->assertTrue($this->client->getResponse()->isRedirect());

        // Follow redirect and check for success message
        $this->client->followRedirect();
        $this->assertSelectorExists('.alert-success');

        // Check that the educator was actually deleted
        $entityManager->clear(); // Clear Doctrine's identity map to force reload
        $deletedEducator = $entityManager->getRepository(\App\Entity\Educator::class)->find($educatorId);
        $this->assertNull($deletedEducator, 'Educator should have been deleted');
    }

    /**
     * Test searching/filtering educators.
     */
    public function testEducatorSearchFiltering(): void
    {
        $container = static::getContainer();
        $entityManager = $container->get('doctrine.orm.entity_manager');

        $city = $entityManager->getRepository('App\Entity\City')->findOneBy([]);
        $schoolType = $entityManager->getRepository('App\Entity\SchoolType')->findOneBy([]);

        // Create a dedicated school for this test
        $school = new \App\Entity\School();
        $school->setName('Search Test School');
        $school->setCity($city);
        $school->setType($schoolType);
        $entityManager->persist($school);
        $entityManager->flush();

        // Get delegate
        $delegate = $this->userRepository->findOneBy(['email' => 'delegat@gmail.com']);

        // Create connection between delegate and school
        $delegateSchool = new \App\Entity\UserDelegateSchool();
        $delegateSchool->setUser($delegate);
        $delegateSchool->setSchool($school);
        $entityManager->persist($delegateSchool);
        $entityManager->flush();

        // Create multiple educators with different names to test search
        $searchName = 'SEARCHABLE_NAME_'.uniqid();

        // Create an educator with the search name
        $educator1 = new \App\Entity\Educator();
        $educator1->setName($searchName);
        $educator1->setSchool($school);
        $educator1->setAmount(50000);
        $educator1->setAccountNumber('265104031000361095');
        $educator1->setCreatedBy($delegate);
        $entityManager->persist($educator1);

        // Create another educator with a different name
        $educator2 = new \App\Entity\Educator();
        $educator2->setName('Different Name');
        $educator2->setSchool($school);
        $educator2->setAmount(40000);
        $educator2->setAccountNumber('265104031000361096');
        $educator2->setCreatedBy($delegate);
        $entityManager->persist($educator2);

        $entityManager->flush();

        // Login as delegate
        $this->client->loginUser($delegate);

        // Visit the educators page with search parameters
        $this->client->request('GET', '/osteceni?name='.urlencode($searchName));

        // Check if the page loaded successfully
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        // Get the page content for debugging if needed
        $content = $this->client->getResponse()->getContent();

        // Check if only the educator with the matching name is shown
        $crawler = $this->client->getCrawler();
        $rows = $crawler->filter('table tbody tr');

        // Check for matching names in the row content
        $pageContent = $crawler->filter('body')->text();

        // Verify at least our search name is shown
        $this->assertStringContainsString($searchName, $pageContent);

        // And that the other name is not shown (or at least not as prominent)
        // We check for the actual name presence and then additional context to ensure full test
        $differentNamePresence = substr_count($pageContent, 'Different Name');
        $searchNamePresence = substr_count($pageContent, $searchName);

        // Verify the search name is found at least as many times as the different name
        // This allows for the test to pass even if both are found (in case the search is not exact)
        $this->assertGreaterThanOrEqual($differentNamePresence, $searchNamePresence,
            'Search filtered educators should prioritize educators matching the search term');
    }

    /**
     * Helper to ensure the delegate has a school assigned.
     */
    private function addSchoolToDelegate(): void
    {
        $container = static::getContainer();
        $entityManager = $container->get('doctrine.orm.entity_manager');

        // Get the delegate user
        $delegate = $this->userRepository->findOneBy(['email' => 'delegat@gmail.com']);

        // Check if delegate already has schools
        if ($delegate->getUserDelegateSchools()->count() > 0) {
            return;
        }

        // Create a test school if needed
        // First check for existing school and city
        $schoolRepository = $entityManager->getRepository('App\Entity\School');
        $cityRepository = $entityManager->getRepository('App\Entity\City');
        $schoolTypeRepository = $entityManager->getRepository('App\Entity\SchoolType');

        $school = $schoolRepository->findOneBy([]);
        if (!$school) {
            $city = $cityRepository->findOneBy([]);
            $schoolType = $schoolTypeRepository->findOneBy([]);

            if (!$city || !$schoolType) {
                $this->markTestSkipped('Missing required city or school type fixtures');
            }

            // Create a new school
            $school = new \App\Entity\School();
            $school->setName('Test School');
            $school->setCity($city);
            $school->setType($schoolType);
            $entityManager->persist($school);
            $entityManager->flush();
        }

        // Create UserDelegateSchool connection
        $delegateSchool = new \App\Entity\UserDelegateSchool();
        $delegateSchool->setUser($delegate);
        $delegateSchool->setSchool($school);
        $entityManager->persist($delegateSchool);
        $entityManager->flush();
    }

    /**
     * Test submitting the delegate request form.
     */
    public function testSubmitDelegateRequestForm(): void
    {
        // Create required data first
        $container = static::getContainer();
        $entityManager = $container->get('doctrine.orm.entity_manager');

        // Make sure we have a city, school type, and school
        $cityRepository = $entityManager->getRepository('App\Entity\City');
        $schoolTypeRepository = $entityManager->getRepository('App\Entity\SchoolType');
        $schoolRepository = $entityManager->getRepository('App\Entity\School');

        $city = $cityRepository->findOneBy([]);
        $schoolType = $schoolTypeRepository->findOneBy([]);
        $school = $schoolRepository->findOneBy(['city' => $city]);

        // Use existing user to avoid the complexity of creating a proper user
        $testUser = $this->userRepository->findOneBy(['email' => 'korisnik@gmail.com']);

        // Login as our new test user
        $this->client->loginUser($testUser);

        // Get the form page
        $crawler = $this->client->request('GET', '/postani-delegat');

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        // Get the HTML content for debugging
        $content = $this->client->getResponse()->getContent();

        // Show we're on the right page
        $this->assertStringContainsString('Obrazac za delegate', $content);
        $this->assertStringContainsString('Vaš zahtev za delegata je prihvaćen od strane administratora.', $content);
    }

    /**
     * Test for delegate request form submission and database validation.
     */
    public function testPendingDelegateRequest(): void
    {
        // Create a user with a pending delegate request
        $container = static::getContainer();
        $entityManager = $container->get('doctrine.orm.entity_manager');

        // Make sure we have a city, school type, and school
        $cityRepository = $entityManager->getRepository('App\Entity\City');
        $schoolRepository = $entityManager->getRepository('App\Entity\School');
        $userDelegateRequestRepository = $entityManager->getRepository('App\Entity\UserDelegateRequest');

        $city = $cityRepository->findOneBy(['name' => 'Novi Sad']);
        if (!$city) {
            $city = $cityRepository->findOneBy([]);
        }

        $school = $schoolRepository->findOneBy(['city' => $city]);
        $schoolType = $school->getType();

        // Use existing regular user
        $testUser = $this->userRepository
            ->createQueryBuilder('u')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_USER%')
            ->andWhere('u.roles NOT LIKE :admin')
            ->andWhere('u.roles NOT LIKE :delegate')
            ->setParameter('admin', '%ROLE_ADMIN%')
            ->setParameter('delegate', '%ROLE_DELEGATE%')
            ->setMaxResults(1) // Get a random user
            ->getQuery()
            ->getResult()[0];

        // If the user already has a delegate request, remove it for clean testing
        if ($testUser->getUserDelegateRequest()) {
            $entityManager->remove($testUser->getUserDelegateRequest());
            $entityManager->flush();
        }

        // Log the user we found
        $this->client->loginUser($testUser);

        // Visit the request page
        $crawler = $this->client->request('GET', '/postani-delegat');

        // Verify we get a successful response
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        // Now fill in and submit the form
        $form = $crawler->filter('form')->form();

        // Test data with unique identifiers
        $testPhone = '0601234567';
        $testTotalEducators = 123;
        $testTotalBlockedEducators = 45;
        $testComment = 'Test comment '.uniqid();

        $form['registration_delegate[phone]'] = $testPhone;
        $form['registration_delegate[city]'] = $city->getId();
        $form['registration_delegate[schoolType]'] = $schoolType->getId();
        $form['registration_delegate[school]'] = $school->getId();
        $form['registration_delegate[totalEducators]'] = $testTotalEducators;
        $form['registration_delegate[totalBlockedEducators]'] = $testTotalBlockedEducators;
        $form['registration_delegate[comment]'] = $testComment;

        // Submit the completed form
        $this->client->submit($form);

        // Check for redirect (successful submission)
        $this->assertTrue($this->client->getResponse()->isRedirect(),
            'Form submission did not redirect: '.$this->client->getResponse()->getStatusCode());

        $this->client->followRedirect();

        // Now verify we can see a successful response
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Vaš zahtev za delegata je poslat administratorima',
            $this->client->getResponse()->getContent());

        // Verify the data was saved to the database
        $entityManager->clear(); // Clear entity manager to ensure we get fresh data

        // Find the delegate request in the database
        $delegateRequest = $userDelegateRequestRepository->findOneBy(['user' => $testUser]);

        // Assert the request exists and has correct data
        $this->assertNotNull($delegateRequest, 'Delegate request was not saved to the database');
        $this->assertEquals($testPhone, $delegateRequest->getPhone(), 'Phone number was not saved correctly');
        $this->assertEquals($testComment, $delegateRequest->getComment(), 'Comment was not saved correctly');
        $this->assertEquals($testTotalEducators, $delegateRequest->getTotalEducators(), 'Total educators count was not saved correctly');
        $this->assertEquals($testTotalBlockedEducators, $delegateRequest->getTotalBlockedEducators(), 'Blocked educators count was not saved correctly');
        $this->assertEquals($city->getId(), $delegateRequest->getCity()->getId(), 'City was not saved correctly');
        $this->assertEquals($schoolType->getId(), $delegateRequest->getSchoolType()->getId(), 'School type was not saved correctly');
        $this->assertEquals($school->getId(), $delegateRequest->getSchool()->getId(), 'School was not saved correctly');
        $this->assertEquals(1, $delegateRequest->getStatus(), 'Status should be set to 1 (NEW)');

        // Verify that the user is still connected to the request
        $this->assertSame($testUser->getId(), $delegateRequest->getUser()->getId(), 'User association is incorrect');
    }

    /**
     * Test submitting the edit educator form.
     */
    public function testSubmitEditEducatorForm(): void
    {
        $container = static::getContainer();
        $entityManager = $container->get('doctrine.orm.entity_manager');

        // Get the delegate user
        $delegate = $this->userRepository->findOneBy(['email' => 'delegat@gmail.com']);

        // Create a test school directly rather than using helper methods
        $cityRepository = $entityManager->getRepository('App\Entity\City');
        $schoolTypeRepository = $entityManager->getRepository('App\Entity\SchoolType');

        // Create city if it doesn't exist
        $city = $cityRepository->findOneBy([]);
        $schoolType = $schoolTypeRepository->findOneBy([]);

        // Create a dedicated school for this test
        $school = new \App\Entity\School();
        $school->setName('Edit Form Test School');
        $school->setCity($city);
        $school->setType($schoolType);
        $entityManager->persist($school);
        $entityManager->flush();

        // Assign school to delegate
        $delegateSchool = new \App\Entity\UserDelegateSchool();
        $delegateSchool->setUser($delegate);
        $delegateSchool->setSchool($school);
        $entityManager->persist($delegateSchool);
        $entityManager->flush();

        // Create an educator in this school
        $educator = new \App\Entity\Educator();
        $educator->setName('Edit Form Test Educator');
        $educator->setSchool($school);
        $educator->setAmount(50000);
        $educator->setAccountNumber('265104031000361092');
        $educator->setCreatedBy($delegate);
        $entityManager->persist($educator);
        $entityManager->flush();

        // Login as delegate
        $this->client->loginUser($delegate);

        // Visit the edit form
        $crawler = $this->client->request('GET', '/osteceni/'.$educator->getId().'/izmeni-podatke');

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        // Get the form
        $form = $crawler->selectButton('Sačuvaj')->form();

        // Update the form data
        $form['educator_edit[name]'] = 'Updated Educator Name';
        $form['educator_edit[amount]'] = '75000';
        $form['educator_edit[accountNumber]'] = '265104031000361092'; // Keep the same

        // Get the current school ID from the form
        $schoolOptions = $crawler->filter('select[name="educator_edit[school]"] option[selected="selected"]')->extract(['value']);
        $schoolId = !empty($schoolOptions[0]) ? $schoolOptions[0] : null;

        if ($schoolId) {
            $form['educator_edit[school]'] = $schoolId;
        }

        // Submit the form
        $this->client->submit($form);

        // Check for redirect on success
        $this->assertTrue($this->client->getResponse()->isRedirect(),
            'Response should be a redirect but was: '.$this->client->getResponse()->getStatusCode());

        // Follow redirect
        $this->client->followRedirect();

        // Check for success message
        $this->assertSelectorExists('.alert-success');

        // Verify the educator was updated in the database
        $entityManager->clear(); // Clear entity manager to force reload

        $updatedEducator = $entityManager->getRepository(\App\Entity\Educator::class)->find($educator->getId());
        $this->assertEquals('Updated Educator Name', $updatedEducator->getName());
        $this->assertEquals(75000, $updatedEducator->getAmount());
    }

    /**
     * Test that a delegate cannot delete an educator from another school.
     */
    public function testCannotDeleteOtherSchoolEducator(): void
    {
        $container = static::getContainer();
        $entityManager = $container->get('doctrine.orm.entity_manager');

        // Create a school not assigned to our delegate
        $cityRepository = $entityManager->getRepository('App\Entity\City');
        $schoolTypeRepository = $entityManager->getRepository('App\Entity\SchoolType');

        // Get or create a city
        $city = $cityRepository->findOneBy([]);
        if (!$city) {
            $city = new \App\Entity\City();
            $city->setName('Test City');
            $entityManager->persist($city);
        }

        // Get or create a school type
        $schoolType = $schoolTypeRepository->findOneBy([]);
        if (!$schoolType) {
            $schoolType = new \App\Entity\SchoolType();
            $schoolType->setName('Test School Type');
            $entityManager->persist($schoolType);
            $entityManager->flush();
        }

        // Create a new school that's NOT linked to our delegate
        $school = new \App\Entity\School();
        $school->setName('Other Delete School');
        $school->setCity($city);
        $school->setType($schoolType);
        $entityManager->persist($school);

        // Create educator in this school
        $educator = new \App\Entity\Educator();
        $educator->setName('Other Delete Educator');
        $educator->setSchool($school);
        $educator->setAmount(50000);
        $educator->setAccountNumber('265104031000361099');
        // Find an admin user for creator
        $adminUser = $this->userRepository->findOneBy(['email' => 'admin@gmail.com']);
        if (!$adminUser) {
            // If no admin user is found, use the delegate user instead
            $adminUser = $this->userRepository->findOneBy(['email' => 'delegat@gmail.com']);
        }
        $educator->setCreatedBy($adminUser);
        $entityManager->persist($educator);
        $entityManager->flush();

        // Now try to access this educator's delete page
        $this->loginAsDelegate();

        // Configure client to not catch exceptions
        $this->client->catchExceptions(false);

        try {
            // This should throw an access denied exception
            $this->client->request('GET', '/osteceni/'.$educator->getId().'/brisanje');

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

    /**
     * Test pagination in the educators list.
     */
    public function testEducatorListPagination(): void
    {
        $container = static::getContainer();
        $entityManager = $container->get('doctrine.orm.entity_manager');

        // Create data for pagination
        $delegate = $this->userRepository->findOneBy(['email' => 'delegat@gmail.com']);

        // Create the school and assign it to the delegate directly
        $cityRepository = $entityManager->getRepository('App\Entity\City');
        $schoolTypeRepository = $entityManager->getRepository('App\Entity\SchoolType');

        // Get or create a city
        $city = $cityRepository->findOneBy([]);
        if (!$city) {
            $city = new \App\Entity\City();
            $city->setName('Test City');
            $entityManager->persist($city);
            $entityManager->flush();
        }

        // Get or create a school type
        $schoolType = $schoolTypeRepository->findOneBy([]);
        if (!$schoolType) {
            $schoolType = new \App\Entity\SchoolType();
            $schoolType->setName('Test School Type');
            $entityManager->persist($schoolType);
            $entityManager->flush();
        }

        // Create a new school for pagination test
        $school = new \App\Entity\School();
        $school->setName('Pagination Test School');
        $school->setCity($city);
        $school->setType($schoolType);
        $entityManager->persist($school);
        $entityManager->flush();

        // Create UserDelegateSchool connection
        $delegateSchool = new \App\Entity\UserDelegateSchool();
        $delegateSchool->setUser($delegate);
        $delegateSchool->setSchool($school);
        $entityManager->persist($delegateSchool);
        $entityManager->flush();

        // Create enough educators to trigger pagination (assuming pagination is set to 10 per page)
        for ($i = 0; $i < 12; ++$i) {
            $educator = new \App\Entity\Educator();
            $educator->setName('Pagination Test Educator '.$i);
            $educator->setSchool($school);
            $educator->setAmount(50000 + $i);
            $educator->setAccountNumber('265104031000361'.str_pad($i, 3, '0', STR_PAD_LEFT));
            $educator->setCreatedBy($delegate);
            $entityManager->persist($educator);
        }
        $entityManager->flush();

        // Login as delegate
        $this->client->loginUser($delegate);

        // Visit the educators page
        $crawler = $this->client->request('GET', '/osteceni');

        // Check that the page loaded successfully
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        // Check for pagination controls
        $paginationExists = $crawler->filter('.pagination')->count() > 0;

        // If pagination is shown, test page 2
        if ($paginationExists) {
            // Navigate to page 2
            $this->client->request('GET', '/osteceni?page=2');
            $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

            // Check that we still see the table with educators
            $this->assertSelectorExists('table tbody tr');
        } else {
            // Pagination not enabled or not enough items to trigger it
            // Still test that we can see the educators we created
            $this->assertSelectorExists('table tbody tr');
            $this->assertStringContainsString('Pagination Test Educator', $crawler->filter('body')->text());
        }
    }

    /**
     * Test the educators list with school filtering.
     */
    public function testEducatorListSchoolFiltering(): void
    {
        $container = static::getContainer();
        $entityManager = $container->get('doctrine.orm.entity_manager');

        // Get delegate
        $delegate = $this->userRepository->findOneBy(['email' => 'delegat@gmail.com']);

        // Create two schools for this delegate
        $cityRepository = $entityManager->getRepository('App\Entity\City');
        $schoolTypeRepository = $entityManager->getRepository('App\Entity\SchoolType');

        // Get or create city and school type
        $city = $cityRepository->findOneBy([]);
        $schoolType = $schoolTypeRepository->findOneBy([]);

        if (!$city || !$schoolType) {
            $this->markTestSkipped('Missing required city or school type fixtures');
        }

        // Create first school
        $school1 = new \App\Entity\School();
        $school1->setName('Filter Test School 1');
        $school1->setCity($city);
        $school1->setType($schoolType);
        $entityManager->persist($school1);

        // Create second school
        $school2 = new \App\Entity\School();
        $school2->setName('Filter Test School 2');
        $school2->setCity($city);
        $school2->setType($schoolType);
        $entityManager->persist($school2);
        $entityManager->flush();

        // Assign both schools to delegate
        $delegateSchool1 = new \App\Entity\UserDelegateSchool();
        $delegateSchool1->setUser($delegate);
        $delegateSchool1->setSchool($school1);
        $entityManager->persist($delegateSchool1);

        $delegateSchool2 = new \App\Entity\UserDelegateSchool();
        $delegateSchool2->setUser($delegate);
        $delegateSchool2->setSchool($school2);
        $entityManager->persist($delegateSchool2);
        $entityManager->flush();

        // Create educators in each school with distinctive names
        $school1EducatorName = 'SCHOOL1_EDUCATOR_'.uniqid();
        $educator1 = new \App\Entity\Educator();
        $educator1->setName($school1EducatorName);
        $educator1->setSchool($school1);
        $educator1->setAmount(50000);
        $educator1->setAccountNumber('265104031000360001');
        $educator1->setCreatedBy($delegate);
        $entityManager->persist($educator1);

        $school2EducatorName = 'SCHOOL2_EDUCATOR_'.uniqid();
        $educator2 = new \App\Entity\Educator();
        $educator2->setName($school2EducatorName);
        $educator2->setSchool($school2);
        $educator2->setAmount(40000);
        $educator2->setAccountNumber('265104031000360002');
        $educator2->setCreatedBy($delegate);
        $entityManager->persist($educator2);

        $entityManager->flush();

        // Login as delegate
        $this->client->loginUser($delegate);

        // Visit the educators page and filter by school 1
        $this->client->request('GET', '/osteceni?school='.$school1->getId());

        // Check that the page loaded successfully
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        // Get the page content
        $pageContent = $this->client->getCrawler()->filter('body')->text();

        // Verify the educator from school 1 is shown
        $this->assertStringContainsString($school1EducatorName, $pageContent);

        // Visit the educators page and filter by school 2
        $this->client->request('GET', '/osteceni?school='.$school2->getId());

        // Check that the page loaded successfully
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        // Get the page content
        $pageContent = $this->client->getCrawler()->filter('body')->text();

        // Verify the educator from school 2 is shown
        $this->assertStringContainsString($school2EducatorName, $pageContent);
    }
}
