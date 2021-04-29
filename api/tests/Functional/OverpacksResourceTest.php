<?php

namespace App\Tests\Functional;

require_once __DIR__.'/../../vendor/autoload.php';

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\EntryPoints;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Users;
use App\Entity\Client;
use App\Entity\AccessToken;
use App\Entity\Overpacks;
use Hautelook\AliceBundle\PhpUnit\ReloadDatabaseTrait;
use App\Test\CustomApiTestCase;

class OverpacksResourceTest extends CustomApiTestCase
{
    use ReloadDatabaseTrait;

    public function testCreateOverpackUnauthenticated()
    {
        $this->writeTestDescription("OVERPACKS");
        $this->writeTestDescription("1. Creating an Overpack as an unauthenticated user returns a 401 status code");

        $client = self::createClient();

        // POST to Overpacks Unauthenticated.
        $client->request('POST', '/v2/overpacks');
        $this->assertResponseStatusCodeSame(401, "Creating an Overpack as an unauthenticated user should generate a 401 Unauthorized response.");

    }

    public function testCreateEditDeleteOverpack() :void
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

        $this->writeTestDescription("2. Creating an Overpack returns a 201 status code.");

        // Send POST request.
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

        //$this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(201, "Posting an Overpack should return a 201 Success - Created status code.");

        $this->writeTestDescription("3. Editing an Overpack returns a 200 status code and the response contains the edited content.");

        // Send PUT request.
        $response = $client->request('PUT', '/v2/overpacks/1', [
            'headers' => ['Content-type' => 'application/json', 'Authorization' => 'Bearer ' . $token],
            'json' => [
                'overpack' => [
                    'service' => 'BoxC Plus',
                    'entry_point' => $ep_id,
                    'height' => 10,
                    'length' => 20,
                    'weight' => 15,
                    'width' => 14,
                ]
            ],
        ]);
        $this->assertStringContainsString('"service":"BoxC Plus"', $response->getContent(), 'The response from the edited Overpack should contain the updated value service:BoxC Plus');
        $this->assertStringContainsString('"height":10', $response->getContent(), 'The response from the edited Overpack should contain the updated value height:10');
        $this->assertStringContainsString('"length":20', $response->getContent(), 'The response from the edited Overpack should contain the updated value length:20');
        $this->assertStringContainsString('"weight":15', $response->getContent(), 'The response from the edited OVerpack should contain the updated value weight:15');
        $this->assertStringContainsString('"width":14', $response->getContent(), 'The response from the edited Overpack should contain the updated value width:14');
        $this->assertResponseStatusCodeSame(200, "Editing an Overpack should return a 200 OK status code.");

        // Create test Shipments.
        $order_number1 = 'ON0012345';
        $tracking_number1 = '12345';
        $this->createShipment($client, $token, $ep_id, $order_number1, $tracking_number1);

        $order_number2 = 'ON0023456';
        $tracking_number2 = '23456';
        $this->createShipment($client, $token, $ep_id, $order_number2, $tracking_number2);

        $order_number3 = 'ON0034567';
        $tracking_number3 = '34567';
        $this->createShipment($client, $token, $ep_id, $order_number3, $tracking_number3);

        // Emulate tracking number being set by the system.
        for ($i = 1; $i < 4; $i++) {
            $ship = $em->find('App\Entity\Shipments', ['id' => $i]);
            $tn = ${'tracking_number' . $i};
            $ship->setTrackingNumber($tn);
            $em->persist($ship);
            $em->flush();
        }

        $this->writeTestDescription("4. Patching returns the Overpack listing with a 200-OK status code and added shipment tracking_number.");

        // Send PATCH to add shipments to Overpack.
        $response = $client->request('PATCH', '/v2/overpacks/1', [
            'headers' => ['Content-type' => 'application/merge-patch+json', 'Authorization' => 'Bearer ' . $token],
            'json' => [
                'overpack' => [
                    'shipments' => [
                        'add' => [1,2,3],
                        'remove' => null,
                    ],
                ],
            ],
        ]);
        $this->assertResponseStatusCodeSame(200, "Patching an Overpack should return a 200-OK status code.");
        $this->assertStringContainsString('"tracking_number":"' . $tracking_number2 . '"', $response->getContent(), "The response should contain the Shipments' tracking_number.");

        $this->writeTestDescription("5. Patching returns the Overpack listing with a 200-OK status code and no reference to the removed shipment tracking_number.");

        // Send PATCH to remove shipments from Overpack.
        $response = $client->request('PATCH', '/v2/overpacks/1', [
            'headers' => ['Content-type' => 'application/merge-patch+json', 'Authorization' => 'Bearer ' . $token],
            'json' => [
                'overpack' => [
                    'shipments' => [
                        'add' => null,
                        'remove' => [2],
                    ],
                ],
            ],
        ]);
        $this->assertResponseStatusCodeSame(200, "Patching an Overpack should return a 200-OK status code.");
        $this->assertStringNotContainsString('"tracking_number":"' . $tracking_number2 . '"', $response->getContent(), "The response should not contain the removed Shipments' tracking_number.");

        // Create a Manifest to test updating and deleting manifested Overpacks.
        $response = $client->request('POST', '/v2/manifests', [
            'headers' => ['Content-type' => 'application/json', 'Authorization' => 'Bearer ' . $testAccessToken->getToken()],
            'json' => [
                'manifest' => [
                    'carrier' => 'DHL',
                    'overpacks' => [1],
                    'entry_point' => $ep_id,
                    'tracking_number' => '92001',
                ],
            ],
        ]);

        $this->writeTestDescription("6. Updating a manifested Overpack returns a 403-Forbidden status code");

        // Send PUT request.
        $response = $client->request('PUT', '/v2/overpacks/1', [
            'headers' => ['Content-type' => 'application/json', 'Authorization' => 'Bearer ' . $token],
            'json' => [
                'overpack' => [
                    'service' => 'BoxC Plus',
                    'entry_point' => $ep_id,
                    'height' => 9,
                    'length' => 19,
                    'weight' => 14,
                    'width' => 13,
                ]
            ],
        ]);
        $this->assertResponseStatusCodeSame(403, "Deleting a manifested Overpack should return a 403-Forbidden status code.");

        // Send Delete request for manifested Overpack.
        $this->writeTestDescription("7. Deleting a manifested Overpack returns a 403-Forbidden status code");
        $response = $client->request('DELETE', '/v2/overpacks/1', [
            'headers' => ['Content-type' => 'application/json', 'Authorization' => 'Bearer ' . $token],
        ]);
        $this->assertResponseStatusCodeSame(403, "Deleting a manifested Overpack should return a 403-Forbidden status code.");


    }
}
