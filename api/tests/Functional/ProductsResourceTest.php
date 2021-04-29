<?php

namespace App\Tests\Functional;

require_once __DIR__.'/../../vendor/autoload.php';

use Doctrine\ORM\EntityManagerInterface;
use Hautelook\AliceBundle\PhpUnit\ReloadDatabaseTrait;
use App\Test\CustomApiTestCase;

class ProductsResourceTest extends CustomApiTestCase
{
    use ReloadDatabaseTrait;

    public function testCreateProductUnauthenticated()
    {
        $this->writeTestDescription("PRODUCTS");
        $this->writeTestDescription("1. Creating a Product as an unauthenticated user returns a 401 status code");

        $client = self::createClient();

        // POST to Products Unauthenticated.
        $client->request('POST', '/v2/products');
        $this->assertResponseStatusCodeSame(401, "Creating a Product as an unauthenticated user should generate a 401 Unauthorized response.");

    }

    public function testCreateEditDeleteProduct() :void
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

        $this->writeTestDescription("2. Creating a Product returns a 201 status code.");

        // Send POST request.
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

        // Send PUT request.
        $this->writeTestDescription("3. Editing a Product returns a 200 status code.");
        $response = $client->request('PUT', '/v2/products/1', [
            'headers' => ['Content-type' => 'application/json', 'Authorization' => 'Bearer ' . $testAccessToken->getToken()],
            'json' => [
                'product' => [
                    'barcode' => '092155323452',
                    'coo' => 'CN',
                    'cost' => (float) $this->getTestNumberValue(1, 20, 2),
                    'value' => (float) $this->getTestNumberValue(1, 50, 2),
                    'name' => 'XXL Solid Shirt',
                    'description' => 'XXL Solid Shirt various colors',
                    'warehouses' => $warehouses,
                ],
            ],
        ]);
        $this->assertResponseStatusCodeSame(200, "Editing a Product should return a 200 OK status code.");

        // Send GET request.
        $this->writeTestDescription("4. GET request for newly created Product returns a 200 OK status code and returns edited description.");
        $response = $client->request('GET', '/v2/products/1', [
            'headers' => ['Content-type' => 'application/json', 'Authorization' => 'Bearer ' . $testAccessToken->getToken()],
        ]);
        $this->assertResponseStatusCodeSame(200, "Editing a Product should return a 200 OK status code.");
        $this->assertStringContainsString('"barcode":"092155323452"', $response->getContent(), 'The response from the edited Product should contain the updated barcode.');
        $this->assertStringContainsString('"description":"XXL Solid Shirt various colors"', $response->getContent(), 'The response from the edited Product should contain the updated description text.');

        // Send DELETE request.
        $this->writeTestDescription("5. Deleting a Product returns a 204 Success-No Content status code");
        $response = $client->request('DELETE', '/v2/products/1', [
            'headers' => ['Content-type' => 'application/json', 'Authorization' => 'Bearer ' . $testAccessToken->getToken()],
        ]);
        $this->assertResponseStatusCodeSame(204, "Deleting a Product should return a 204 Success-No Content status code.");

    }

}
