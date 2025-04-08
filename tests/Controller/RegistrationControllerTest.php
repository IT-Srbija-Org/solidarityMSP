<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RegistrationControllerTest extends WebTestCase
{
    public function testRegistrationFormIsDisplayed(): void
    {
        $client = static::createClient();
        $client->request('GET', '/registracija');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form[name="registration"]');
        $this->assertSelectorExists('input[name="registration[firstName]"]');
        $this->assertSelectorExists('input[name="registration[lastName]"]');
        $this->assertSelectorExists('input[name="registration[email]"]');
    }
}
