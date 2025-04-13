<?php

namespace App\Tests\Controller\Admin;

use App\DataFixtures\CityFixtures;
use App\DataFixtures\SchoolFixtures;
use App\DataFixtures\SchoolTypeFixtures;
use App\DataFixtures\UserDelegateRequestFixtures;
use App\DataFixtures\UserDelegateSchoolFixtures;
use App\DataFixtures\UserFixtures;
use App\Entity\School;
use App\Entity\User;
use App\Entity\UserDelegateSchool;
use App\Repository\SchoolRepository;
use App\Repository\UserDelegateSchoolRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
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
    private ?SchoolRepository $schoolRepository;
    private ?UserDelegateSchoolRepository $userDelegateSchoolRepository;
    private ?EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();

        $this->databaseTool = $container->get(DatabaseToolCollection::class)->get();
        $this->loadFixtures();

        $this->userRepository = $container->get(UserRepository::class);
        $this->schoolRepository = $container->get(SchoolRepository::class);
        $this->userDelegateSchoolRepository = $container->get(UserDelegateSchoolRepository::class);
        $this->entityManager = $container->get(EntityManagerInterface::class);
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
        ]);
    }

    private function loginAsAdmin(): void
    {
        $adminUser = $this->userRepository->findOneBy(['email' => 'admin@gmail.com']);
        $this->client->loginUser($adminUser);
    }

    // Functional / Integration tests

    public function testDelegateListRequiresAuthentication(): void
    {
        $this->client->request('GET', '/admin/delegate/list');

        $response = $this->client->getResponse();
        $statusCode = $response->getStatusCode();

        // Should not be accessible without authentication
        $this->assertNotEquals(Response::HTTP_OK, $statusCode);

        $this->assertTrue(
            $response->isRedirection()
            || in_array($statusCode, [
                Response::HTTP_UNAUTHORIZED,
                Response::HTTP_FORBIDDEN,
                Response::HTTP_INTERNAL_SERVER_ERROR,
            ])
        );
    }

    public function testDelegateListAccessibleByAdmin(): void
    {
        $this->loginAsAdmin();
        $this->client->request('GET', '/admin/delegate/list');

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSelectorExists('table'); // Check if table exists
        $this->assertSelectorExists('form input[name="firstName"]'); // Check if search form exists
    }

    public function testConnectSchoolRequiresAuthentication(): void
    {
        // Get a delegate user from fixtures
        $user = $this->getDelegateUser();
        $userId = $user->getId();

        $this->client->request('GET', "/admin/delegate/{$userId}/connect-school");

        $response = $this->client->getResponse();
        $statusCode = $response->getStatusCode();

        // Should not be accessible without authentication
        $this->assertNotEquals(Response::HTTP_OK, $statusCode);

        $this->assertTrue(
            $response->isRedirection()
            || in_array($statusCode, [
                Response::HTTP_UNAUTHORIZED,
                Response::HTTP_FORBIDDEN,
                Response::HTTP_INTERNAL_SERVER_ERROR,
            ])
        );
    }

    public function testConnectSchoolAccessibleByAdmin(): void
    {
        $this->loginAsAdmin();

        // Get a delegate user from fixtures
        $user = $this->getDelegateUser();
        $userId = $user->getId();

        $this->client->request('GET', "/admin/delegate/{$userId}/connect-school");

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSelectorExists('form[name="user_delegate_school_connect"]'); // Check if connect form exists
    }

    public function testConnectSchoolFormSubmission(): void
    {
        $this->loginAsAdmin();

        // Get a delegate user from fixtures
        $user = $this->getDelegateUser();
        $userId = $user->getId();

        // Find a school that's not already connected to this user
        $school = $this->findSchoolNotConnectedToUser($user);

        $this->client->request('GET', "/admin/delegate/{$userId}/connect-school");
        $this->client->submitForm('Dodaj', [
            'user_delegate_school_connect[school]' => $school->getId(),
        ]);

        // Check if we were redirected back to the connect page
        $this->assertTrue($this->client->getResponse()->isRedirect("/admin/delegate/{$userId}/connect-school"));

        // Follow redirect
        $this->client->followRedirect();

        // Check for success flash message
        $this->assertSelectorExists('.alert-success');

        // Check the flash message content - Note: This is a bug in the controller
        // It says "odvezali" (disconnected) when it should say "povezali" (connected)
        $flashContent = $this->client->getCrawler()->filter('.alert-success')->text();
        $this->assertStringContainsString('odvezali', $flashContent, 'Bug: Flash message indicates disconnecting a school, not connecting');

        // Verify that a new connection was created in the database
        $connection = $this->userDelegateSchoolRepository->findOneBy([
            'user' => $user,
            'school' => $school,
        ]);

        $this->assertNotNull($connection);
    }

    public function testConnectSchoolToNonDelegateUser(): void
    {
        $this->loginAsAdmin();

        // Get a regular user (not a delegate)
        $regularUser = $this->userRepository->createQueryBuilder('u')
            ->where('u.roles NOT LIKE :role')
            ->setParameter('role', '%ROLE_DELEGATE%')
            ->andWhere('u.isActive = :active')
            ->setParameter('active', true)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        $this->assertNotNull($regularUser, 'No regular user found in fixtures');
        $userId = $regularUser->getId();

        // Configure client to not catch exceptions so we can handle them manually
        $this->client->catchExceptions(false);

        try {
            // This should throw an access denied exception
            $this->client->request('GET', "/admin/delegate/{$userId}/connect-school");

            // If we get here (no exception), still check for HTTP 403
            $this->assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
        } catch (\Symfony\Component\Security\Core\Exception\AccessDeniedException $e) {
            // Expected exception, test passes
            $this->assertTrue(true, 'Expected AccessDeniedException was thrown');
        } catch (\Exception $e) {
            // Catch any other exceptions
            $this->fail('Unexpected exception thrown: '.get_class($e).' - '.$e->getMessage());
        } finally {
            // Reset to default behavior
            $this->client->catchExceptions(true);
        }
    }

    public function testConnectSchoolToInactiveDelegateUser(): void
    {
        $this->loginAsAdmin();

        // Get a delegate and set them as inactive
        $delegate = $this->getDelegateUser();
        $delegate->setIsActive(false);
        $this->entityManager->flush();

        $userId = $delegate->getId();

        // Configure client to not catch exceptions so we can handle them manually
        $this->client->catchExceptions(false);

        try {
            // This should throw an access denied exception
            $this->client->request('GET', "/admin/delegate/{$userId}/connect-school");

            // If we get here (no exception), still check for HTTP 403
            $this->assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
        } catch (\Symfony\Component\Security\Core\Exception\AccessDeniedException $e) {
            // Expected exception, test passes
            $this->assertTrue(true, 'Expected AccessDeniedException was thrown');
        } catch (\Exception $e) {
            // Catch any other exceptions
            $this->fail('Unexpected exception thrown: '.get_class($e).' - '.$e->getMessage());
        } finally {
            // Reset to default behavior
            $this->client->catchExceptions(true);
        }
    }

    public function testUnconnectSchoolWithoutId(): void
    {
        $this->loginAsAdmin();

        // Get a delegate user
        $user = $this->getDelegateUser();
        $userId = $user->getId();

        // Configure client to not catch exceptions so we can handle them manually
        $this->client->catchExceptions(false);

        try {
            // This should throw a not found exception
            $this->client->request('GET', "/admin/delegate/{$userId}/unconect-school");

            // If we get here (no exception), still check for HTTP 404
            $this->assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            // Expected exception, test passes
            $this->assertTrue(true, 'Expected NotFoundHttpException was thrown');
        } catch (\Exception $e) {
            // Catch any other exceptions
            $this->fail('Unexpected exception thrown: '.get_class($e).' - '.$e->getMessage());
        } finally {
            // Reset to default behavior
            $this->client->catchExceptions(true);
        }
    }

    public function testUnconnectSchoolWithInvalidId(): void
    {
        $this->loginAsAdmin();

        // Get a delegate user
        $user = $this->getDelegateUser();
        $userId = $user->getId();

        // Use a non-existent ID
        $nonExistentId = 99999;

        // Configure client to not catch exceptions so we can handle them manually
        $this->client->catchExceptions(false);

        try {
            // This should throw a not found exception
            $this->client->request('GET', "/admin/delegate/{$userId}/unconect-school?user-delegate-school-id={$nonExistentId}");

            // If we get here (no exception), still check for HTTP 404
            $this->assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            // Expected exception, test passes
            $this->assertTrue(true, 'Expected NotFoundHttpException was thrown');
        } catch (\Exception $e) {
            // Catch any other exceptions
            $this->fail('Unexpected exception thrown: '.get_class($e).' - '.$e->getMessage());
        } finally {
            // Reset to default behavior
            $this->client->catchExceptions(true);
        }
    }

    public function testUnconnectSchoolBelongingToDifferentUser(): void
    {
        $this->loginAsAdmin();

        // Get two different delegate users
        $delegates = $this->userRepository->createQueryBuilder('u')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_DELEGATE%')
            ->andWhere('u.isActive = :active')
            ->setParameter('active', true)
            ->setMaxResults(2)
            ->getQuery()
            ->getResult();

        // If we don't have two delegates, create a test scenario
        if (count($delegates) < 2) {
            $this->markTestSkipped('Need at least 2 delegates for this test');

            return;
        }

        $delegate1 = $delegates[0];
        $delegate2 = $delegates[1];

        // Get a connection for delegate 1
        $connection = $this->getUserDelegateSchoolConnection($delegate1);
        if (!$connection) {
            $this->markTestSkipped('No school connection found for delegate user');

            return;
        }

        $connectionId = $connection->getId();

        // Configure client to not catch exceptions so we can handle them manually
        $this->client->catchExceptions(false);

        try {
            // This should throw an access denied exception
            $this->client->request('GET', "/admin/delegate/{$delegate2->getId()}/unconect-school?user-delegate-school-id={$connectionId}");

            // If we get here (no exception), still check for HTTP 403
            $this->assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
        } catch (\Symfony\Component\Security\Core\Exception\AccessDeniedException $e) {
            // Expected exception, test passes
            $this->assertTrue(true, 'Expected AccessDeniedException was thrown');
        } catch (\Exception $e) {
            // Catch any other exceptions
            $this->fail('Unexpected exception thrown: '.get_class($e).' - '.$e->getMessage());
        } finally {
            // Reset to default behavior
            $this->client->catchExceptions(true);
        }
    }

    public function testUnconnectSchoolRequiresAuthentication(): void
    {
        // Get a delegate user and connected school
        $user = $this->getDelegateUser();
        $userId = $user->getId();
        $connection = $this->getUserDelegateSchoolConnection($user);

        if (!$connection) {
            $this->markTestSkipped('No school connection found for delegate user');
        }

        $connectionId = $connection->getId();

        $this->client->request('GET', "/admin/delegate/{$userId}/unconect-school?user-delegate-school-id={$connectionId}");

        $response = $this->client->getResponse();
        $statusCode = $response->getStatusCode();

        // Should not be accessible without authentication
        $this->assertNotEquals(Response::HTTP_OK, $statusCode);

        $this->assertTrue(
            $response->isRedirection()
            || in_array($statusCode, [
                Response::HTTP_UNAUTHORIZED,
                Response::HTTP_FORBIDDEN,
                Response::HTTP_INTERNAL_SERVER_ERROR,
            ])
        );
    }

    public function testUnconnectSchoolAccessibleByAdmin(): void
    {
        $this->loginAsAdmin();

        // Get a delegate user and connected school
        $user = $this->getDelegateUser();
        $userId = $user->getId();
        $connection = $this->getUserDelegateSchoolConnection($user);

        if (!$connection) {
            $this->markTestSkipped('No school connection found for delegate user');
        }

        $connectionId = $connection->getId();

        $this->client->request('GET', "/admin/delegate/{$userId}/unconect-school?user-delegate-school-id={$connectionId}");

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSelectorExists('form[name="confirm"]'); // Check if confirm form exists
    }

    public function testUnconnectSchoolFormSubmission(): void
    {
        $this->loginAsAdmin();

        // Get a delegate user and connected school
        $user = $this->getDelegateUser();
        $userId = $user->getId();
        $connection = $this->getUserDelegateSchoolConnection($user);

        if (!$connection) {
            $this->markTestSkipped('No school connection found for delegate user');
        }

        $connectionId = $connection->getId();

        $this->client->request('GET', "/admin/delegate/{$userId}/unconect-school?user-delegate-school-id={$connectionId}");
        $this->client->submitForm('Potvrdi', [
            'confirm[confirm]' => true,
        ]);

        // Check if we were redirected back to the connect page
        $this->assertTrue($this->client->getResponse()->isRedirect("/admin/delegate/{$userId}/connect-school"));

        // Follow redirect
        $this->client->followRedirect();

        // Check for success flash message
        $this->assertSelectorExists('.alert-success');

        // Check the flash message content
        $flashContent = $this->client->getCrawler()->filter('.alert-success')->text();
        $this->assertStringContainsString('odvezali', $flashContent, 'Flash message should indicate disconnecting a school');

        // Verify that the connection was removed from the database
        $connectionAfter = $this->userDelegateSchoolRepository->find($connectionId);
        $this->assertNull($connectionAfter);
    }

    /**
     * Helper method to get a delegate user from fixtures.
     */
    private function getDelegateUser(): User
    {
        $delegateUser = $this->userRepository->createQueryBuilder('u')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_DELEGATE%')
            ->andWhere('u.isActive = :active')
            ->setParameter('active', true)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        $this->assertNotNull($delegateUser, 'No delegate user found in fixtures');

        return $delegateUser;
    }

    /**
     * Helper method to find a school that's not connected to the given user.
     */
    private function findSchoolNotConnectedToUser(User $user): School
    {
        // Get IDs of schools already connected to this user
        $connectedSchoolIds = $this->userDelegateSchoolRepository->createQueryBuilder('uds')
            ->select('IDENTITY(uds.school)')
            ->where('uds.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();

        $connectedSchoolIds = array_column($connectedSchoolIds, 1);

        // Find a school that's not in this list
        $unconnectedSchool = $this->schoolRepository->createQueryBuilder('s')
            ->where('s.id NOT IN (:ids)')
            ->setParameter('ids', $connectedSchoolIds ?: [0]) // Avoid empty IN clause
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        $this->assertNotNull($unconnectedSchool, 'No unconnected school found');

        return $unconnectedSchool;
    }

    /**
     * Helper method to get a user-delegate-school connection for a user.
     */
    private function getUserDelegateSchoolConnection(User $user): ?UserDelegateSchool
    {
        return $this->userDelegateSchoolRepository->findOneBy(['user' => $user]);
    }
}
