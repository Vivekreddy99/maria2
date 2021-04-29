<?php

namespace App\Tests\Functional;

require_once __DIR__.'/../../vendor/autoload.php';

use Doctrine\ORM\EntityManagerInterface;
use Hautelook\AliceBundle\PhpUnit\ReloadDatabaseTrait;
use App\Test\CustomApiTestCase;

class ShopsResourceTest extends CustomApiTestCase
{
    use ReloadDatabaseTrait;

    public function testCreateShopUnauthenticated()
    {
        $this->writeTestDescription("SHOPS");
        $this->writeTestDescription("1. Creating a Shop as an unauthenticated user returns a 401 status code");

        $client = self::createClient();

        // POST to Shops Unauthenticated.
        $client->request('POST', '/v2/shops');
        $this->assertResponseStatusCodeSame(401, "Creating a Shop as an unauthenticated user should generate a 401 Unauthorized response.");

    }

    public function testCreateEditDeleteShop() :void
    {
        // Create HTTP Client.
        $client = self::createClient();

        $em = self::$container->get(EntityManagerInterface::class);

        // Create user.
        $user = $this->createUser('test@example.com', 'foo', 'en');

        // Create Client Entity for Token authentication.
        $testClient = $this->createFosClient(['http://localhost:9000/coop/oauth/handle']);

        // Create AccessToken.
        $testAccessToken = $this->createAccessToken($testClient, $user);

        $this->writeTestDescription("2. Creating a Shop returns a 201 status code.");

        // Send POST request.
        $shop_id = 'a123_test';
        $response = $client->request('POST', '/v2/shops', [
            'headers' => ['Content-type' => 'application/json', 'Authorization' => 'Bearer ' . $testAccessToken->getToken()],
            'json' => [
                'shop' => [
                    'active' => false,
                    'id' => $shop_id,
                    'name' => 'One Test Shop',
                    'settings' => [
                        'delay_processing' => 0,
                        'partial_fulfillment' => false,
                    ],
                    'test' => true,
                ],
            ],
        ]);
        $this->assertResponseStatusCodeSame(201, "Posting a Shop should return a 201 Success - Created status code.");

        // Send PUT request.
        $this->writeTestDescription("3. Editing a Shop returns a 200 status code.");
        $response = $client->request('PUT', '/v2/shops/' . $shop_id, [
            'headers' => ['Content-type' => 'application/json', 'Authorization' => 'Bearer ' . $testAccessToken->getToken()],
            'json' => [
                'shop' => [
                    'active' => true,
                    'name' => 'Two Test Shop',
                    'settings' => [
                        'delay_processing' => 24,
                        'partial_fulfillment' => false,
                    ],
                    'test' => true,
                ],
            ],
        ]);
        $this->assertResponseStatusCodeSame(200, "Editing a Shop should return a 200 OK status code.");

        // Send GET request.
        $this->writeTestDescription("4. GET request for newly created Shop returns a 200 OK status code and returns edited name.");
        $response = $client->request('GET', '/v2/shops/' . $shop_id, [
            'headers' => ['Content-type' => 'application/json', 'Authorization' => 'Bearer ' . $testAccessToken->getToken()],
        ]);
        $this->assertResponseStatusCodeSame(200, "Editing a Shop should return a 200 OK status code.");
        $this->assertStringContainsString('"name":"Two Test Shop"', $response->getContent(), 'The response from the edited Shop should contain the updated name.');
        $this->assertStringContainsString('"delay_processing":24', $response->getContent(), 'The response from the edited Shop should contain the updated delay_processing value.');

        // Send DELETE request.
        $this->writeTestDescription("5. Deleting a Shop returns a 204 Success-No Content status code.");
        $response = $client->request('DELETE', '/v2/shops/' . $shop_id, [
            'headers' => ['Content-type' => 'application/json', 'Authorization' => 'Bearer ' . $testAccessToken->getToken()],
        ]);
        $this->assertResponseStatusCodeSame(204, "Deleting a Shop should return a 204 Success-No Content status code.");

    }

}
