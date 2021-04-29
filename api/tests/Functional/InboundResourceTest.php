<?php

namespace App\Tests\Functional;

require_once __DIR__.'/../../vendor/autoload.php';

use Doctrine\ORM\EntityManagerInterface;
use Hautelook\AliceBundle\PhpUnit\ReloadDatabaseTrait;
use App\Test\CustomApiTestCase;

class InboundResourceTest extends CustomApiTestCase
{
    use ReloadDatabaseTrait;

    public function testCreateInboundUnauthenticated()
    {
        $this->writeTestDescription("INBOUND");
        $this->writeTestDescription("1. Creating an Inbound Shipment as an unauthenticated user returns a 401 status code");

        $client = self::createClient();

        // POST to Inbound Unauthenticated.
        $client->request('POST', '/v2/inbound');
        $this->assertResponseStatusCodeSame(401, "Creating an Inbound Shipment as an unauthenticated user should generate a 401 Unauthorized response.");

    }

    public function testCreateEditGetDeleteInbound() :void
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

        // Create EntryPoint.
        $ep_id = 'LAXD01';
        $testEntryPoint = $this->createEntryPoint($ep_id);

        $wh_id = 'WH0CVG01';
        $warehouse = $this->createWarehouse($wh_id, $testEntryPoint);

        // Send POST request for Product.
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

        $this->writeTestDescription("2. Creating an Inbound Shipment returns a 201 status code.");

        // Send POST request for Inbound.
        $response = $client->request('POST', '/v2/inbound', [
            'headers' => ['Content-type' => 'application/json', 'Authorization' => 'Bearer ' . $testAccessToken->getToken()],
            'json' => [
                'inbound' => [
                    'carrier' => 'ZTO',
                    'notes' => null,
                    'products' => [
                        [
                            'id' => 1,
                            'quantity' => 6,
                            'inbound_cost' => 43.75,
                             // 'processed' => 3, Set by the system.
                        ],
                    ],
                    'tracking_number' => '12345678901',
                    'warehouse' => [
                        'id' => $wh_id,
                    ]
                ],
            ],
        ]);
        $this->assertResponseStatusCodeSame(201, "Posting an Inbound Shipment should return a 201 Success-Created status code.");

        $this->writeTestDescription("3. Editing an Inbound Shipment returns a 200 status code.");

        // Send PUT request for Inbound.
        $response = $client->request('PUT', '/v2/inbound/1', [
            'headers' => ['Content-type' => 'application/json', 'Authorization' => 'Bearer ' . $testAccessToken->getToken()],
            'json' => [
                'inbound' => [
                    'carrier' => 'ZTO',
                    'notes' => 'Test note.',
                    'tracking_number' => '12345678901',
                    'warehouse' => [
                        'id' => $wh_id,
                    ]
                ],
            ],
        ]);
        $this->assertResponseStatusCodeSame(200, "Editing an Inbound Shipment should return a 200-OK status code.");

        // Send GET request.
        $this->writeTestDescription("4. GET request for newly created Inbound Shipment returns a 200 status code");
        $response = $client->request('GET', '/v2/inbound/1', [
            'headers' => ['Content-type' => 'application/json', 'Authorization' => 'Bearer ' . $testAccessToken->getToken()],
        ]);
        $this->assertResponseStatusCodeSame(200, "An Inbound Shipment GET request should return a 200 OK status code.");
        $this->assertStringContainsString('"notes":"Test note."', $response->getContent(), 'The response from the edited Inbound Shipment request should contain the updated notes field');

        // Send DELETE request.
        $this->writeTestDescription("5. Deleting an Inbound returns a 204 Success-No Content status code");
        $response = $client->request('DELETE', '/v2/inbound/1', [
            'headers' => ['Content-type' => 'application/json', 'Authorization' => 'Bearer ' . $testAccessToken->getToken()],
        ]);
        $this->assertResponseStatusCodeSame(204, "An Inbound Shipment DELETE request should return a 204 Success-No Content status code.");

    }

}
