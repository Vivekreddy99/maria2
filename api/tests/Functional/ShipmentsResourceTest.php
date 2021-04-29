<?php

namespace App\Tests\Functional;

require_once __DIR__.'/../../vendor/autoload.php';

use App\Entity\AccessToken;
use Doctrine\ORM\EntityManagerInterface;
use Hautelook\AliceBundle\PhpUnit\ReloadDatabaseTrait;
use App\Test\CustomApiTestCase;

class ShipmentsResourceTest extends CustomApiTestCase
{
    use ReloadDatabaseTrait;

    public function testCreateShipmentUnauthenticated()
    {
        $this->writeTestDescription("SHIPMENTS");
        $this->writeTestDescription("1. Creating a Shipment as an unauthenticated user returns a 401 status code");

        $client = self::createClient();

        // POST to Shipments Unauthenticated.
        $client->request('POST', '/v2/shipments');
        $this->assertResponseStatusCodeSame(401, "Creating a Shipment as an unauthenticated user should generate a 401 Unauthorized response.");

        // POST to Shipments with bad Token.
        $this->createShipment($client, 'ABCD', 'EFGH', 'IJKL', "92001");
        $this->writeTestDescription("2. Creating a Shipment with a bad token returns a 401 status code");

        $this->assertResponseStatusCodeSame(401, "Creating a Shipment with a bad token should generate a 401 Unauthorized response.");

    }

    public function testCreateDeleteShipment() :void
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

        // Send POST request.
        $order_number1 = 'RS0091037';
        $package_height_default = 1;
        $package_width_default = 10;
        $package_length_default = 15;

        $this->writeTestDescription("3. Creating a Payload Shipment returns a 400 status code if package dimensions are missing.");
        $response = $this->createShipment($client, $token, $ep_id, $order_number1, "92001", 'Payload');
        $this->assertResponseStatusCodeSame(400, "Posting a Payload Shipment should return a 400 error code if package dimensions are missing.");

        $this->writeTestDescription("4. Creating an eCommerce Shipment returns a 201 status code and sets default package dimensions.");
        $response = $this->createShipment($client, $token, $ep_id, $order_number1, "92001", 'eCommerce');
        $this->assertResponseStatusCodeSame(201, "Posting a Shipment should return a 201 Success - Created status code.");
        $this->assertStringContainsString('"height":' . $package_height_default, $response->getContent(), 'An eCommerce shipment should have the default height if none is provided.');
        $this->assertStringContainsString('"width":' . $package_width_default, $response->getContent(), 'An eCommerce shipment should have the default width if none is provided.');
        $this->assertStringContainsString('"length":' . $package_length_default, $response->getContent(), 'An eCommerce shipment should have the default length if none is provided.');

        // Send DELETE request.
        $this->writeTestDescription("5. Deleting a Shipment returns a 200 status code and updates to canceled if not a test Shipment.");
        $response = $client->request('DELETE', '/v2/shipments/1', [
            'headers' => ['Content-type' => 'application/json', 'Authorization' => 'Bearer ' . $token],
        ]);
        $this->assertStringContainsString('This shipment will be canceled', $response->getContent(), 'The response from the deleted Shipment should contain the canceled text if not a test Shipment.');
        $this->assertResponseStatusCodeSame(200, "Deleting a Shipment should return a 200 OK status code and updates to canceled if not a test Shipment.");

        // Send GET request and confirm canceled flag is true.
        $this->writeTestDescription("6. GET request for newly created Shipment returns a 200 status code and returns canceled as true since it is not a test Shipment.");
        $response = $client->request('GET', '/v2/shipments/1', [
            'headers' => ['Content-type' => 'application/json', 'Authorization' => 'Bearer ' . $token],
        ]);
        $this->assertStringContainsString('"canceled":true', $response->getContent(), 'The response from the GET Shipment request should contain the canceled:true text since it is not a test Shipment.');
        $this->assertResponseStatusCodeSame(200, "A Shipment GET request should return a 200 OK status code.");

        // Send POST request for second Shipment.
        $order_number2 = 'RS0091034';
        $this->createShipment($client, $token, $ep_id, $order_number2, "92001");

        // Send GET collection request
        $this->writeTestDescription("7. GET request for all Shipments returns a 200 status code and returns the entry_point $ep_id.");
        $response = $this->sendRequest($client, $token, 'GET', '/v2/shipments', []);
        $this->assertStringContainsString('"entry_point":"' . $ep_id .'"', $response->getContent(), 'The response from the GET Shipment request should contain the entry_point ' . $ep_id . '.');
        $this->assertResponseStatusCodeSame(200, "A Shipment GET request should return a 200 OK status code.");

        // Send GET collection request with order_number filter
        $this->writeTestDescription("8. GET request for all Shipments with a filter on order_number returns a 200 status code and returns the searched on order_number and no others");
        $response = $this->sendRequest($client, $token, 'GET', '/v2/shipments', ['order_number' => $order_number2]);
        $this->assertStringContainsString('"order_number":"' . $order_number2 .'"', $response->getContent(), 'The response from the GET Shipment request with order_number filter should return the searched on order_number.');
        $this->assertStringNotContainsString('"order_number":"' . $order_number1 .'"', $response->getContent(), 'The response from the GET Shipment request with order_number filter should not return a different order_number.');
        $this->assertResponseStatusCodeSame(200, "A Shipment GET request should return a 200 OK status code.");
    }
}

