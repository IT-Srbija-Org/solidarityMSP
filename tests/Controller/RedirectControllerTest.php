<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class RedirectControllerTest extends WebTestCase
{
    // Testing one URL is sufficient since all routes share the same controller method
    // and have identical behavior (301 redirect to homepage)
    public function testLegacyUrlRedirectsToHome(): void
    {
        $urls = [
            '/hvalaDonatoru',
            '/hvalaDelegatu',
            '/hvalaZaOstecenog',
            '/obrazacDonatori',
            '/obrazacDelegati',
            '/profileDelegat',
            '/obrazacOsteceni',
        ];

        $client = static::createClient();
        foreach ($urls as $url) {
            $client->request('GET', $url);
            $this->assertResponseRedirects('/', Response::HTTP_MOVED_PERMANENTLY);
        }
    }
}
