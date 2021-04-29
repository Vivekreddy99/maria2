<?php

namespace App\Tests\Functional;

require_once __DIR__.'/../../vendor/autoload.php';

use Doctrine\ORM\EntityManagerInterface;
use Hautelook\AliceBundle\PhpUnit\ReloadDatabaseTrait;
use App\Test\CustomApiTestCase;

class OrdersResourceTest extends CustomApiTestCase
{
    use ReloadDatabaseTrait;

    public function testCreateOrderUnauthenticated()
    {
        $this->writeTestDescription("ORDERS");
        $this->writeTestDescription("1. Creating an Order as an unauthenticated user returns a 401 status code");

        $client = self::createClient();

        // POST to Orders Unauthenticated.
        $client->request('POST', '/v2/orders');
        $this->assertResponseStatusCodeSame(401, "Creating an Order as an unauthenticated user should generate a 401 Unauthorized response.");

    }

    public function testCreateEditDeleteOrder() :void
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

        $this->writeTestDescription("2. Creating an Order returns a 201 status code.");

        // Send POST request for Shop.
        $shop_id = 'a123_test';
        $active = true;
        $this->createShop($client, $token, $shop_id, $active);
        $this->assertResponseStatusCodeSame(201, "Posting a Shop should return a 201 Success - Created status code.");

        // Send POST for Product.
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

        // Send POST for 2nd Product.
        $response = $client->request('POST', '/v2/products', [
            'headers' => ['Content-type' => 'application/json', 'Authorization' => 'Bearer ' . $testAccessToken->getToken()],
            'json' => [
                'product' => [
                    'coo' => 'US',
                    'cost' => (float) $this->getTestNumberValue(1, 20, 2),
                    'value' => (float) $this->getTestNumberValue(1, 50, 2),
                    'name' => 'MD Striped Shirt',
                    'description' => 'Medium Striped Shirt',
                    'warehouses' => $warehouses,
                ],
            ],
        ]);

        // Send POST request for Order.
        $service = 'BoxC Parcel';
        $shop_order_id = 'REFID1';
        $shipping_name = 'c/o Head of Marketing';
        $status = 'Processing';
        $this->createOrder($client, $token, $shop_id, $service, $shop_order_id, $shipping_name, $status);
        $this->assertResponseStatusCodeSame(201, "Posting an Order should return a 201 Success-Created status code.");

        // Send GET request and confirm service is set.
        $this->writeTestDescription("3. GET request for newly created Order returns a 200 status code and the service field should be set.");
        $response = $this->sendRequest($client, $token, 'GET', '/v2/orders/1', []);
        $this->assertResponseStatusCodeSame(200, "An Order GET request should return a 200 OK status code.");
        $this->assertStringContainsString('"service":"' . $service . '"', $response->getContent(), 'The response should contain the service field.');

        // Send POST request for second Shop.
        $shop_id2 = 'b456_test';
        $active = true;
        $this->createShop($client, $token, $shop_id2, $active);
        $this->assertResponseStatusCodeSame(201, "Posting a Shop should return a 201 Success - Created status code.");

        // Send POST request for second Order.
        $shop_order_id2 = 'REF#2';
        $shipping_name2 = 'John Smith';
        $status2 = 'Holding';
        $this->createOrder($client, $token, $shop_id2, $service, $shop_order_id2, $shipping_name2, $status2);
        $this->assertResponseStatusCodeSame(201, "Posting an Order should return a 201 Success-Created status code.");

        // Send GET collection request and confirm shop_order_id is set.
        $this->writeTestDescription("4. GET collection request for Orders returns a 200 status code and the shop order ids.");
        $response = $this->sendRequest($client, $token, 'GET', '/v2/orders', []);
        $this->assertResponseStatusCodeSame(200, "An Order GET collection request should return a 200 OK status code.");
        $this->assertStringContainsString('"order_id":"' . $shop_order_id . '"', $response->getContent(), 'The response should contain the shop.order_id field ' . $shop_order_id . '.');
        $this->assertStringContainsString('"order_id":"' . $shop_order_id2 . '"', $response->getContent(), 'The response should contain the shop.order_id field ' . $shop_order_id2 . '.');

        // Send GET request and filter by shop order id.
        $this->writeTestDescription("5. GET collection request for Orders using a shop.order_id filter returns a 200 status code and only the Order with the requested shop.order_id.");
        $response = $this->sendRequest($client, $token, 'GET', '/v2/orders', ['shop.order_id' => $shop_order_id2]);
        $this->assertResponseStatusCodeSame(200, "An Order GET collection request should return a 200 OK status code.");
        $this->assertStringContainsString('"order_id":"' . $shop_order_id2 . '"', $response->getContent(), 'The response should contain the shop.order_id field ' . $shop_order_id2 . '.');
        $this->assertStringNotContainsString('"order_id":"' . $shop_order_id . '"', $response->getContent(), 'The response should not contain the shop.order_id field ' . $shop_order_id . '.');

        // Send GET request and filter by shop id.
        $this->writeTestDescription("6. GET collection request for Orders using a shop.id filter returns a 200 status code and only the Order with the requested shop.id.");
        $response = $this->sendRequest($client, $token, 'GET', '/v2/orders', ['shop.id' => $shop_id2]);
        $this->assertResponseStatusCodeSame(200, "An Order GET collection request should return a 200 OK status code.");
        $this->assertStringContainsString('"id":"' . $shop_id2 . '"', $response->getContent(), 'The response should contain the shop.id field ' . $shop_id2 . '.');
        $this->assertStringNotContainsString('"id":"' . $shop_id . '"', $response->getContent(), 'The response should not contain the shop.id field ' . $shop_id . '.');

        // Send GET request and filter by shipping name.
        $this->writeTestDescription("7. GET collection request for Orders using a shipping_address.name filter returns a 200 status code and only the Order with the requested shipping_address.name.");
        $response = $this->sendRequest($client, $token, 'GET', '/v2/orders', ['shipping_address.name' => $shipping_name2]);
        $this->assertResponseStatusCodeSame(200, "An Order GET collection request should return a 200 OK status code.");
        $this->assertStringContainsString('"name":"' . $shipping_name2 . '"', $response->getContent(), 'The response should contain the shipping_address.name field ' . $shipping_name2 . '.');
        $this->assertStringNotContainsString('"name":"' . $shipping_name . '"', $response->getContent(), 'The response should not contain the shipping_address.name field ' . $shipping_name . '.');

        // Send GET request and filter by status.
        $this->writeTestDescription("8. GET collection request for Orders using a status filter returns a 200 status code and only the Order with the requested status.");
        $response = $this->sendRequest($client, $token, 'GET', '/v2/orders', ['status' => strtolower($status2)]);
        $this->assertResponseStatusCodeSame(200, "An Order GET collection request should return a 200 OK status code.");
        $this->assertStringContainsString('"status":"' . $status2 . '"', $response->getContent(), 'The response should contain status:' . $status2);
        $this->assertStringNotContainsString('"status":"' . $status . '"', $response->getContent(), 'The response should not contain status:' . $status);

        // Send PATCH request to swap the status of the two Orders.
        $response = $client->request('PATCH', '/v2/orders/status', [
            'headers' => ['Content-type' => 'application/json', 'Authorization' => 'Bearer ' . $token],
            'json' => [
                'orders' => [
                    [
                        'id' => 1,
                        'status' => $status2,
                    ],
                    [
                        'id' => 2,
                        'status' => $status,
                    ],
                ],
            ],
        ]);
        $this->assertResponseStatusCodeSame(200, "An Order PATCH request should return a 200 OK status code.");
        $this->assertStringContainsString('"status":"' . $status . '"', $response->getContent(), 'The response should contain status:' . $status);
        $this->assertStringContainsString('"status":"' . $status2 . '"', $response->getContent(), 'The response should contain status:' . $status2);

        // Send DELETE request.
        $this->writeTestDescription("9. Deleting an Order returns a 204 Success-No Content status code");
        $response = $this->sendRequest($client, $token, 'DELETE', '/v2/orders/1', []);
        $this->assertResponseStatusCodeSame(204, "Deleting an Order should return a 204 Success-No Content status code.");

        // Change Order status to Packing.
        $em = self::$container->get(EntityManagerInterface::class);
        $order = $em->find('App\Entity\Orders', ['id' => 2]);
        $order->status = 'Packing';
        $em->persist($order);
        $em->flush();

        // Send DELETE request.
        $this->writeTestDescription("10. Deleting an Order with a Packing status returns a 403 Forbidden status code");
        $response = $this->sendRequest($client, $token, 'DELETE', '/v2/orders/2', []);
        $this->assertResponseStatusCodeSame(403, "Deleting an Order with a Packing status should return a 403 Forbidden status code.");

        // Send PUT request for Order with status=Packing.
        $this->writeTestDescription("11. Updating an Order with Packing status returns a 400 error");
        $response = $this->updateOrder(2, $client, $token, $shop_id2, $service, $shop_order_id2, 'Test name');
        $this->assertResponseStatusCodeSame(400, "Updating an Order should return a 400 error status code");

        // Change Order status to Ready.
        $em = self::$container->get(EntityManagerInterface::class);
        $order = $em->find('App\Entity\Orders', ['id' => 2]);
        $order->status = 'Ready';
        $em->persist($order);
        $em->flush();

        // Send PUT request for Order with status=Ready.
        $this->writeTestDescription("12. Updating an Order with Ready status returns a 200-OK response code and contains the new line_item but not the previous line_item.");
        $response = $this->updateOrder(2, $client, $token, $shop_id2, $service, $shop_order_id2, 'Test name');
        $this->assertResponseStatusCodeSame(200, "Updating an Order should return a 200-OK response code");
        $this->assertStringContainsString('"product_id":2', $response->getContent(), 'The response should contain product_id:2');
        $this->assertStringNotContainsString('"product_id":1', $response->getContent(), 'The response should not contain status:product_id:1');

        // Test Posting an Order to an inactive Shop
        $this->writeTestDescription("13. Posting an Order to an inactive Shop returns a 400 error code");
        $shop2 = $em->find('App\Entity\Shops', ['id' => $shop_id2]);
        $shop2->active = false;
        $em->persist($shop2);
        $em->flush();

        // Send POST request for third Order.
        $shop_order_id3 = 'REFERENCE#3';
        $shipping_name3 = 'John Doe';
        $status2 = 'Holding';
        $this->createOrder($client, $token, $shop_id2, $service, $shop_order_id3, $shipping_name3, $status2);
        $this->assertResponseStatusCodeSame(400, "Posting an Order to an inactive Shop should return a 400 error code.");

    }

    protected function createOrder($client, $token, $shop_id, $service, $shop_order_id, $shipping_name, $status = 'Holding')
    {
        $consignee = $this->getConsigneeTestData();
        $consignor = $this->getConsignorTestData();
        $line_items = $this->getLineItemsTestData([1]);
        $return_address = $this->getAddressTestData('Return', 'Test Return Address Name');
        $shipping_address = $this->getAddressTestData('Shipping', $shipping_name);

        $response = $client->request('POST', '/v2/orders', [
            'headers' => ['Content-type' => 'application/json', 'Authorization' => 'Bearer ' . $token],
            'json' => [
                'order' => [
                    'consignee' => $consignee,
                    'consignor' => $consignor,
                    'created' => '2020-10-05 15:43:03',
                    'gift_message' => 'Thanks for the laughs. -Sansa',
                    'line_items' => $line_items,
                    'packing_slip' => false,
                    'partial_fulfillment' => true,
                    'priority' => 5,
                    'return_address' => $return_address,
                    'service' => $service,
                    'shipping_address' => $shipping_address,
                    'shop' => [
                        'id' => $shop_id,
                        'order_id' => $shop_order_id,
                    ],
                    'status' => $status,
                    'test' => false,
                    'total_products' => 3,
                    'total_quantity' => 11,
                ],
            ],
        ]);

        return $response;
    }

    protected function updateOrder($id, $client, $token, $shop_id, $service, $shop_order_id, $shipping_name, $status = 'Holding')
    {
        $consignee = $this->getConsigneeTestData();
        $consignor = $this->getConsignorTestData();
        $line_items = $this->getLineItemsTestData([2]);
        $return_address = $this->getAddressTestData('Return', 'Test Return Address Name');
        $shipping_address = $this->getAddressTestData('Shipping', $shipping_name);

        $response = $client->request('PUT', '/v2/orders/' . $id, [
            'headers' => ['Content-type' => 'application/json', 'Authorization' => 'Bearer ' . $token],
            'json' => [
                'order' => [
                    'consignee' => $consignee,
                    'consignor' => $consignor,
                    'created' => '2020-10-05 15:43:03',
                    'gift_message' => 'Thanks for the laughs. -Sansa',
                    'line_items' => $line_items,
                    'packing_slip' => true,
                    'partial_fulfillment' => true,
                    'priority' => 3,
                    'return_address' => $return_address,
                    'service' => $service,
                    'shipping_address' => $shipping_address,
                    'shop' => [
                        'id' => $shop_id,
                        'order_id' => $shop_order_id,
                    ],
                    'status' => $status,
                    'test' => false,
                    'total_products' => 3,
                    'total_quantity' => 11,
                ],
            ],
        ]);

        return $response;
    }

    protected function createShop($client, $token, $shop_id, $active = true)
    {
        $response = $client->request('POST', '/v2/shops', [
            'headers' => ['Content-type' => 'application/json', 'Authorization' => 'Bearer ' . $token],
            'json' => [
                'shop' => [
                    'active' => $active,
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
    }

}
