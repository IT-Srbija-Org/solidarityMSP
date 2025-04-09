<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SecurityControllerTest extends WebTestCase
{
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->catchExceptions(true);
    }

    /**
     * Test that the login page loads correctly with all form elements.
     */
    public function testLoginPageLoads(): void
    {
        $this->client->request('GET', '/logovanje');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
        $this->assertSelectorExists('input[name="email"]');
        $this->assertSelectorExists('button[type="submit"]');
    }

    /**
     * Test the logout route throws an exception (as expected)
     * This is a valid behavior since the logout route is handled by Symfony's security system.
     */
    public function testLogoutThrowsLogicException(): void
    {
        // The logout route should throw a LogicException
        $this->expectException(\LogicException::class);

        // We need to make a kernel that doesn't catch exceptions
        $kernel = self::bootKernel();
        $container = $kernel->getContainer();

        // Get the controller
        $controller = $container->get('App\Controller\SecurityController');

        // Call the logout method directly
        $controller->logout();
    }
}
