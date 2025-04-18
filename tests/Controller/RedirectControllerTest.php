<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class RedirectControllerTest extends WebTestCase
{
    public function testLegacyUrlRedirectsToHome(): void
    {
        $urls = [
            '/hvalaDonatoru',
            '/hvalaDelegatu',
            '/hvalaZaOstecenog',
        ];

        $client = static::createClient();
        foreach ($urls as $url) {
            $client->request('GET', $url);
            $this->assertResponseRedirects('/', Response::HTTP_MOVED_PERMANENTLY);
        }
    }

    public function testLegacyUrlRedirectsToDonor(): void
    {
        $urls = [
            '/obrazacDonatori',
        ];

        $client = static::createClient();
        foreach ($urls as $url) {
            $client->request('GET', $url);
            $this->assertResponseRedirects('/profil-donora', Response::HTTP_MOVED_PERMANENTLY);
        }
    }

    public function testLegacyUrlRedirectsToDelegate(): void
    {
        $urls = [
            '/obrazacDelegati',
            '/profileDelegat',
            '/obrazacOsteceni',
        ];

        $client = static::createClient();
        foreach ($urls as $url) {
            $client->request('GET', $url);
            $this->assertResponseRedirects('/postani-delegat', Response::HTTP_MOVED_PERMANENTLY);
        }
    }
}
