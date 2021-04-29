<?php
// src/App/Test/CustomApiTestCase.php

namespace App\Test;

use Symfony\Component\Dotenv\Dotenv;

require_once __DIR__.'/../../vendor/autoload.php';

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\AccessToken;
use App\Entity\Client;
use App\Entity\EntryPoints;
use App\Entity\Users;
use App\Entity\Warehouses;
use Doctrine\ORM\EntityManagerInterface;

class CustomApiTestCase extends ApiTestCase
{
    protected $separator_start = PHP_EOL;
    protected $separator_end = '';

    public function  __construct() 
    {
        parent::__construct();
  
        if (!class_exists(Dotenv::class)) {
            throw new \RuntimeException('APP_ENV environment variable is not defined. You need to define environment variables for configuration or add "symfony/dotenv" as a Composer dependency to load variables from a .env file.');
        }
        (new Dotenv())->load(__DIR__.'/../../test.env');
    }

    protected function createUser(string $email, string $password, string $language) :Users
    {
        $user = new Users();
        $user->setEmail($email);
        $encoded = self::$container->get('security.password_encoder')
            ->encodePassword($user, $password);
        $user->setPassword($encoded);
        $user->setFname('Tom');
        $user->setLname('Smith');
        $user->setLanguage($language);
        $user->setConName('TestConName');
        $user->setCountry('US');
        $user->setUsername($email);
        $em = self::$container->get(EntityManagerInterface::class);
        $em->persist($user);
        $em->flush();

        return $user;
    }

    /**
     * Username, password authentication at /login.
     * @param $client
     * @param $email
     * @param $password
     */
    protected function login($client, $email, $password)
    {
        // Send POST login request.
        $client->request('POST', '/login', [
            'headers' => ['Content-type' => 'application/json'],
            'json' => [
                'email' => $email,
                'password' => $password,
            ],
        ]);
        $this->assertResponseStatusCodeSame(204);
    }

    protected function createUserAndLogin($client, $email, $password, $language) :Users
    {
        $user = $this->createUser($email, $password, $language);

        $this->login($client, $email, $password);

        return $user;
    }

    /**
     * Populates the FOSOauthServerBundle Client table.
     * @param array $redirect_uris
     * @return Client
     */
    protected function createFosClient(array $redirect_uris)
    {
        $testClient = new Client();
        $testClient->setAllowedGrantTypes(['client_credentials']);
        $testClient->setRandomId('2s3bu28bu1ogkcoskg8kgk0ggcw840kckw4484gc8kw0g0o8g0');
        $testClient->setRedirectUris($redirect_uris);
        $testClient->setSecret('39xs7cvbut444w0og40kkg0kccow8sk848so88goc4owcc00kg');

        $em = self::$container->get(EntityManagerInterface::class);
        $em->persist($testClient);
        $em->flush();

        return $testClient;
    }

    protected function createAccessToken($testClient, $user)
    {
        $testAccessToken = new AccessToken();
        $testAccessToken->setClient($testClient);
        $expires = time() + 100000;
        $testAccessToken->setExpiresAt($expires);
        $testAccessToken->setScope('client_credentials');
        $testAccessToken->setUser($user);
        $testAccessToken->setToken('OWM0NWQ3ZDQ2OTkzMDI5NWY2ZDcxMDBmMWM2NDMzN2E0NjcwNDViZjhmODYyZDA3ZWMwNmE1ZWJiOGY0NDJhMA');

        $em = self::$container->get(EntityManagerInterface::class);
        $em->persist($testAccessToken);
        $em->flush();

        return $testAccessToken;
    }

    protected function createEntryPoint($id)
    {
        $ep = new EntryPoints();
        $ep->id = $id;
        $ep->city = 'COMPTON';
        $ep->country = 'US';
        $ep->delivery_address = 'BoxC\n921 ARTESIA BLVD\n,COMPTON, CA 90220\nUNITED STATES';
        $ep->address = $ep->delivery_address;
        $ep->name = 'BoxC';
        $ep->notes = 'Test entry point note';
        $ep->postal_code = '90220';
        $ep->province = 'CA';
        $ep->street1 = '921 ARTESIA BLVD';
        $ep->street2 = '';
        $ep->setActive(1);
        $ep->setLatitude(34.0522);
        $ep->setLongitude(118.2437);
        $ep->setTimezone('Pacific Standard Time');

        $em = self::$container->get(EntityManagerInterface::class);
        $em->persist($ep);
        $em->flush();

        return $ep;
    }

    protected function createWarehouse(string $wh_id, $ep)
    {
        $warehouse = new Warehouses();
        $warehouse->id = $wh_id;
        $warehouse->setAddress('Test address');
        $warehouse->base_fee = (float) $this->getTestNumberValue(1, 90, 2);
        $warehouse->city = 'Erlanger';
        $warehouse->country = 'US';
        $warehouse->language = 'English';
        $warehouse->language_code = 'en';
        $warehouse->name = 'US - Central 1';
        $warehouse->notes = 'Test notes.';
        $warehouse->per_unit_fee = (float) $this->getTestNumberValue(1, 9, 2);
        $warehouse->postal_code = '41018';
        $warehouse->storage_sm_price = (float) $this->getTestNumberValue(1, 5, 2);
        $warehouse->storage_md_price = (float) $this->getTestNumberValue(1, 9, 2);
        $warehouse->storage_lg_price = (float) $this->getTestNumberValue(1, 90, 2);
        $warehouse->storage_free_days = (int) $this->getTestNumberValue(1, 30, 0);
        // TODO: Doctrine returns 0 for character id's, like the one below;
        //   solved by adding default values in Test tables.
        // $warehouse->ep = $ep;

        $em = self::$container->get(EntityManagerInterface::class);
        $em->persist($warehouse);
        $em->flush();
    }

    protected function writeTestDescription(string $string)
    {
        print $this->separator_start . $string . $this->separator_end;
    }

    protected function getAddressTestData($type, $name)
    {
        $address = [
            'name' => 'My Test Company',
            'street1' => '311 SAINT NICHOLAS AVE',
            'street2' => '2D',
            'city' => 'RIDGEWOOD',
            'province' => 'NY',
            'postal_code' => '11385',
            'country' => 'US',
        ];

        if ($type == 'Shipping') {
            $address['name'] = $name;
            $address['phone'] = '555-444-3333';
            $address['email'] = 'test@example.com';
        }

        return $address;

    }

    protected function getConsigneeTestData()
    {
        return [
            'name' => 'Google Inc.',
            'phone' => null,
            'email' => null,
            'street1' => '1600 AMPHITHEATRE PKWY',
            'street2' => null,
            'city' => 'MOUNTAIN VIEW',
            'province' => 'CA',
            'postal_code' => '94043-1351',
            'country' => 'US',
        ];
    }

    protected function getConsignorTestData()
    {
        return [
            'name' => 'Generic Company, LLC',
            'phone' => '555-123-4567',
            'street1' => '1 WORLD WAY',
            'street2' => null,
            'city' => 'SHENZHEN',
            'province' => 'GDG',
            'postal_code' => '518000',
            'country' => 'CN',
        ];
    }

    protected function getLineItemsTestData(array $product_ids = [0 => 1])
    {
        $line_items = [];

        for($i = 1; $i <= count($product_ids); $i++) {
            $line_items[] = $this->getLineItemTestData($product_ids[($i - 1)]);
        }

        return $line_items;
    }

    protected function getLineItemTestData($product_id = 1)
    {
        return [
            'coo' => 'US',
            'currency' => 'USD',
            'description' => 'Phone case',
            'dg_code' => null,
            'hts_code' => null,
            'origin_description' => null,
            'product_id' => $product_id,
            'quantity' => (int) $this->getTestNumberValue(1, 100, 0),
            'sku' => 'ABCD',
            'tax' => (float) $this->getTestNumberValue(1, 100, 0),
            'value' => (float) $this->getTestNumberValue(1, 20, 2),
            'weight' => (float) $this->getTestNumberValue(1, 200, 3),
        ];
    }

    protected function getPackagesTestData()
    {
        $packages = [];

        $rand = rand(1,4);
        for($i = 0; $i < $rand; $rand++) {
            $packages[] = $this->getPackageTestData();
            break;
        }

        return $packages;
    }

    protected function getPackageTestData()
    {
        return [
            'barcode' => \bin2hex(random_bytes(5)),
            'weight' => (float) $this->getTestNumberValue(1, 200, 3),
        ];
    }

    protected function getReferencesTestData()
    {
        return [
            "SKU #10292301",
            "Comment line 2.",
            "Reference #3."
        ];
    }

    protected function getWarehousesTestData()
    {
        $warehouses = [];

        $warehouses[] = $this->getWarehouseTestData('en');
        $warehouses[] = $this->getWarehouseTestData('zh');

        return $warehouses;
    }

    protected function getWarehouseTestData($language)
    {
        return [
            'language_code' => $language,
            'description' => $language == 'en' ? 'Shirt' : 'æˆ‘',
        ];
    }

    protected function getTestNumberValue($min, $max, $decimal_places)
    {
        $factor = \pow(10, $decimal_places);
        $rand = \rand($min * $factor, $max * $factor);
        return \bcdiv($rand, $factor, $decimal_places);
    }

    /**
     * Shorthand method for sending test request.
     *
     * @param $client
     * @param string $token
     * @param string $method
     * @param string $url
     * @param array $query_string_parts
     * @return mixed $response
     */
    protected function sendRequest($client, $token, $method, $url, array $query_string_parts)
    {
        $query_string = '';
        foreach($query_string_parts as $key => $val){
            $query_string .= $key . '=' . urlencode($val) . '&';
        }
        $url = empty($query_string) ? $url : $url . '?' . $query_string;
        $response = $client->request($method, $url, [
            'headers' => ['Content-type' => 'application/json', 'Authorization' => 'Bearer ' . $token],
        ]);

        return $response;
    }

    protected function createShipment($http_client, $token, $ep_id, $order_number, $tracking_number, $shipment_class = 'eCommerce')
    {
        $consignee = $this->getConsigneeTestData();
        $consignor = $this->getConsignorTestData();
        $line_items = $this->getLineItemsTestData();
        $packages = $this->getPackagesTestData();
        $references = $this->getReferencesTestData();
        $return_address = $this->getAddressTestData('Return', 'Test Return Name');
        $shipping_address = $this->getAddressTestData('Shipping', 'Test Shipping Name');

        $response = $http_client->request('POST', '/v2/shipments', [
            'headers' => ['Content-type' => 'application/json', 'Authorization' => 'Bearer ' . $token],
            'json' => [
                'shipment' => [
                    'class' => $shipment_class,
                    'comments' => (object) ['comments' => 'Test comments'],
                    'consignee' => $consignee,
                    'consignor' => $consignor,
                    'cost' => 3.58,
                    'entry_point' => $ep_id,
                    'line_items' => $line_items,
                    'order_number' => $order_number,
                    'overpack_id' => 1,
                    'packages' => $packages,
                    'references' => $references,
                    'return_address' => $return_address,
                    'service' => 'BoxC Parcel',
                    'shipping_address' => $shipping_address,
                    'signature_confirmation' => false,
                    'terms' => 'DDU',
                    'test' => false,
                    'tracking_number' => $tracking_number,
                ],
            ],
        ]);

        return $response;
    }

    public static function getKernelClass() {
       return 'App\Kernel';
    }
}
