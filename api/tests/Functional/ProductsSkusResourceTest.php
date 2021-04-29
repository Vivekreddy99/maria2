<?php

namespace App\Tests\Functional;

require_once __DIR__.'/../../vendor/autoload.php';

use Doctrine\ORM\EntityManagerInterface;
use Hautelook\AliceBundle\PhpUnit\ReloadDatabaseTrait;
use App\Test\CustomApiTestCase;

class ProductsSkusResourceTest extends CustomApiTestCase
{
    use ReloadDatabaseTrait;

    public function testCreateEditDeleteSku() :void
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

        $this->writeTestDescription("PRODUCTS SKUS");
        $this->writeTestDescription("1. Creating an SKU returns a 201 status code.");

        // Send POST request for new Product.
        $warehouses = $this->getWarehousesTestData();
        $response = $client->request('POST', '/v2/products', [
            'headers' => ['Content-type' => 'application/json', 'Authorization' => 'Bearer ' . $testAccessToken->getToken()],
            'json' => [
                'product' => [
                    'coo' => 'CN',
                    'cost' => (float) $this->getTestNumberValue(1, 20, 2),
                    'value' => (float) $this->getTestNumberValue(1, 50, 2),
                    'name' => 'XL Striped Shirt',
                    'description' => 'XL Striped Shirt',
                    'warehouses' => $warehouses,
                ],
            ],
        ]);
        $this->assertResponseStatusCodeSame(201, "Posting a Product should return a 201 Success - Created status code.");

        // Send POST request for new Shop.
        $shop_id = 'b123_test';
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

        // Send POST request for new SKU.
        $sku = 'SKU123';
        $response = $client->request('POST', '/v2/products/1/skus', [
            'headers' => ['Content-type' => 'application/json', 'Authorization' => 'Bearer ' . $testAccessToken->getToken()],
            'json' => [
                'skus' => [
                    'active' => true,
                    'shop_id' => $shop_id,
                    'sku' => $sku,
                ],
            ],
        ]);
        $this->assertResponseStatusCodeSame(201, "Posting an SKU should return a 201 Success-Created status code.");

        // Send PUT request.
        $this->writeTestDescription("2. Editing an SKU returns a 200 status code.");
        $response = $client->request('PUT', '/v2/products/1/shop/' . $shop_id . '/sku/' . $sku, [
            'headers' => ['Content-type' => 'application/json', 'Authorization' => 'Bearer ' . $testAccessToken->getToken()],
            'json' => [
                'skus' => [
                    'active' => false,
                    'sku' => 'SKU1',
                ],
            ],
        ]);
        $this->assertResponseStatusCodeSame(200, "Editing an SKU should return a 200 OK status code.");

        // Send GET request.
        $this->writeTestDescription("3. GET request for newly created Product returns a 200 OK status code and returns edited SKU");
        $response = $client->request('GET', '/v2/products/1', [
            'headers' => ['Content-type' => 'application/json', 'Authorization' => 'Bearer ' . $testAccessToken->getToken()],
        ]);
        $this->assertResponseStatusCodeSame(200, "Editing a Product should return a 200 OK status code.");
        $this->assertStringContainsString('"skus":[{"active":false,"sku":"SKU1"}]', $response->getContent(), 'The response from the edited Product should contain the updated SKU.');

        // Send DELETE request.
        $this->writeTestDescription("4. Deleting an SKU returns a 204 Success-No Content status code");
        $response = $client->request('DELETE', '/v2/products/1/shop/' . $shop_id . '/sku/sku1', [
            'headers' => ['Content-type' => 'application/json', 'Authorization' => 'Bearer ' . $testAccessToken->getToken()],
        ]);
        $this->assertResponseStatusCodeSame(204, "Deleting an SKU should return a 204 Success-No Content status code.");

    }

}
