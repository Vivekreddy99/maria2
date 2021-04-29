<?php

namespace App\Tests\Functional;

require_once __DIR__.'/../../vendor/autoload.php';

use App\Exception\InvalidEntryException;
use Symfony\Component\HttpClient\Exception\ClientException;
use Doctrine\ORM\EntityManagerInterface;
use Hautelook\AliceBundle\PhpUnit\ReloadDatabaseTrait;
use App\Test\CustomApiTestCase;

class ManifestsResourceTest extends CustomApiTestCase
{
    use ReloadDatabaseTrait;

    public function testCreateManifestUnauthenticated()
    {
        $this->writeTestDescription("MANIFESTS");
        $this->writeTestDescription("1. Creating a Manifest as an unauthenticated user returns a 401 status code");

        $client = self::createClient();

        // POST to Manifests Unauthenticated.
        $client->request('POST', '/v2/manifests');
        $this->assertResponseStatusCodeSame(401, "Creating a Manifest as an unauthenticated user should generate a 401 Unauthorized response.");

    }

    public function testCreateDeleteManifest() :void
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
        $token = $testAccessToken->getToken();

        // Create EntryPoint.
        $ep_id = 'LAXD01';
        $testEntryPoint = $this->createEntryPoint($ep_id);

        // Create second EntryPoint.
        $ep_id2 = 'EWRD01';
        $testEntryPoint2 = $this->createEntryPoint($ep_id2);

        // Add Shipment.
        $order_number1 = 'RS0091037';
        $this->createShipment($client, $token, $ep_id, $order_number1, "92001");

        // Add 2nd Shipment.
        $order_number2 = 'RT0001022';
        $this->createShipment($client, $token, $ep_id2, $order_number2, "B3678");

        // Send POST request for an Overpack.
        $response = $client->request('POST', '/v2/overpacks', [
            'headers' => ['Content-type' => 'application/json', 'Authorization' => 'Bearer ' . $token],
            'json' => [
                'overpack' => [
                    'service' => 'BoxC Parcel',
                    'entry_point' => $ep_id,
                    'height' => 0,
                    'length' => 0,
                    'weight' => 0,
                    'width' => 0,
                ]
            ],
        ]);

        // Send 2nd POST request for an Overpack.
        $response = $client->request('POST', '/v2/overpacks', [
            'headers' => ['Content-type' => 'application/json', 'Authorization' => 'Bearer ' . $token],
            'json' => [
                'overpack' => [
                    'service' => 'BoxC Plus',
                    'entry_point' => $ep_id2,
                    'height' => 1,
                    'length' => 2,
                    'weight' => 3,
                    'width' => 4,
                ]
            ],
        ]);


        // Send PATCH to add shipment to Overpack.
        $response = $client->request('PATCH', '/v2/overpacks/1', [
            'headers' => ['Content-type' => 'application/merge-patch+json', 'Authorization' => 'Bearer ' . $token],
            'json' => [
                'overpack' => [
                    'shipments' => [
                        'add' => [1],
                        'remove' => null,
                    ],
                ],
            ],
        ]);

        // Send PATCH to add shipment to Overpack #2.
        $response = $client->request('PATCH', '/v2/overpacks/2', [
            'headers' => ['Content-type' => 'application/merge-patch+json', 'Authorization' => 'Bearer ' . $token],
            'json' => [
                'overpack' => [
                    'shipments' => [
                        'add' => [2],
                        'remove' => null,
                    ],
                ],
            ],
        ]);

        // Send POST request for Manifest.
        $this->writeTestDescription("2. POST request for Manifest containing an Overpack with a Shipment returns a 201 status code");
        $response = $client->request('POST', '/v2/manifests', [
            'headers' => ['Content-type' => 'application/json', 'Authorization' => 'Bearer ' . $token],
            'json' => [
                'manifest' => [
                    'overpacks' => [1],
                ],
            ],
        ]);
        $this->assertResponseStatusCodeSame(201, "Posting a Manifest should return a 201 Success - Created status code.");

        // Send 2nd POST request for Manifest.
        $response = $client->request('POST', '/v2/manifests', [
            'headers' => ['Content-type' => 'application/json', 'Authorization' => 'Bearer ' . $token],
            'json' => [
                'manifest' => [
                    'overpacks' => [2],
                ],
            ],
        ]);

        // Send GET request and confirm canceled flag is true.
        $this->writeTestDescription("3. GET request for newly created Manifest returns a 200 status code");
        $response = $this->sendRequest($client, $token, 'GET', '/v2/manifests/1', []);
        $this->assertResponseStatusCodeSame(200, "A Manifest GET request should return a 200 OK status code.");

        // Send GET collection request.
        $this->writeTestDescription("4. GET collection request for Manifests returns a 200 status code and response containing entry_points.");
        $response = $this->sendRequest($client, $token, 'GET', '/v2/manifests', []);
        $this->assertResponseStatusCodeSame(200, "A Manifest GET collection request should return a 200 OK status code.");
        $this->assertStringContainsString($ep_id, $response->getContent(), 'The response should contain entry_point ' . $ep_id);
        $this->assertStringContainsString($ep_id2, $response->getContent(), 'The response should contain entry_point ' . $ep_id2);

        // Send GET collection request with entry_point filter.
        $this->writeTestDescription("5. GET collection request with an entry_point filter for Manifests returns a 200 status code and response containing searched for entry_point, but not different entry_point");
        $response = $this->sendRequest($client, $token, 'GET', '/v2/manifests', ['entry_point' => $ep_id]);
        $this->assertResponseStatusCodeSame(200, "A Manifest GET collection request should return a 200 OK status code.");
        $this->assertStringContainsString($ep_id, $response->getContent(), 'The response should contain entry_point:' . $ep_id);
        $this->assertStringNotContainsString($ep_id2, $response->getContent(), 'The response should not contain entry_point:' . $ep_id2);

        // Send 3rd POST request for Manifest with Overpacks having different entry_points.
        $this->writeTestDescription("6. POST request for Manifest containing two Overpacks with different EntryPoints returns a 400 error code");
        $response = $client->request('POST', '/v2/manifests', [
            'headers' => ['Content-type' => 'application/json', 'Authorization' => 'Bearer ' . $token],
            'json' => [
                'manifest' => [
                    'overpacks' => [1, 2],
                ],
            ],
        ]);
        $this->assertResponseStatusCodeSame(400, "A Manifest POST request containing two Overpacks with different EntryPoints should return a 400 error code.");

    }

    public function testAddingOverpackWithNoShipment() {
        // Create HTTP Client.
        $client = self::createClient();

        // Create user.
        $user = $this->createUser('test@example.com', 'foo', 'en');

        // Create Client Entity for Token authentication.
        $testClient = $this->createFosClient(['http://localhost:9000/coop/oauth/handle']);

        // Create AccessToken.
        $testAccessToken = $this->createAccessToken($testClient, $user);
        $token = $testAccessToken->getToken();

        // Create EntryPoint.
        $ep_id = 'LAXD01';
        $testEntryPoint = $this->createEntryPoint($ep_id);

        // Send POST request for an Overpack.
        $response = $client->request('POST', '/v2/overpacks', [
            'headers' => ['Content-type' => 'application/json', 'Authorization' => 'Bearer ' . $token],
            'json' => [
                'overpack' => [
                    'service' => 'BoxC Plus',
                    'entry_point' => $ep_id,
                    'height' => 1,
                    'length' => 2,
                    'weight' => 3,
                    'width' => 4,
                ]
            ],
        ]);

        // Send POST request for Manifest.
        $this->writeTestDescription("7. Creating a Manifest that contains an Overpack with no Shipments throws an Exception");
        $this->expectException(ClientException::class);
        try {
            $response = $client->request('POST', '/v2/manifests', [
                'headers' => ['Content-type' => 'application/json', 'Authorization' => 'Bearer ' . $token],
                'json' => [
                    'manifest' => [
                        'overpacks' => [1],
                    ],
                ],
            ]);
            $this->assertStringNotContainsString('no Shipment', $response->getContent(), 'The response text should indicate when the attached Overpack has no Shipment.');

        } catch (ClientException $ex) {
            $this->assertStringNotContainsString('no Shipment', $response->getContent(), 'The response text should indicate when the attached Overpack has no Shipment.');
        }
    }
}
