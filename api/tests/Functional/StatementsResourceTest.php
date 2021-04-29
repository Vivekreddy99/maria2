<?php

namespace App\Tests\Functional;

require_once __DIR__.'/../../vendor/autoload.php';

use Doctrine\ORM\EntityManagerInterface;
use Hautelook\AliceBundle\PhpUnit\ReloadDatabaseTrait;
use App\Test\CustomApiTestCase;
use App\Entity\Statements;

class StatementsResourceTest extends CustomApiTestCase
{
    use ReloadDatabaseTrait;

    public function testGetStatements(): void
    {
        $this->writeTestDescription("STATEMENTS");

        // Create HTTP Client.
        $client = self::createClient();

        // Create user.
        $user = $this->createUser('test@example.com', 'foo', 'en');

        // Create Client Entity for Token authentication.
        $testClient = $this->createFosClient(['http://localhost:9000/coop/oauth/handle']);

        // Create AccessToken.
        $testAccessToken = $this->createAccessToken($testClient, $user);
        $token = $testAccessToken->getToken();

        // Get Entity Manager.
        $em = self::$container->get(EntityManagerInterface::class);

        // Create N test Statements.
        $created = [];
        $total_billed = [];
        for ($i = 1; $i < 15; $i++) {
            $month = (($i % 12) + 1);
            if ($month < 10) {
                $month = '0' . $month;
            }

            $day = (($i % 28) + 1);
            if ($day < 10) {
                $day = '0' . $day;
            }

            $created[$i] = \date_create('2020-' . $month . '-' . $day);
            $total_billed[$i] = (float)\bcdiv($i * (2371 + $i), 100, 2);

            $data = [
                'created' => $created[$i],
                'total_billed' => $total_billed[$i]
            ];
            // $this->writeTestDescription("Created: " . date_format($created[$i], 'Y-m-d'));

            $this->createStatement($em, $data, $user);
        }

        // Send GET request and confirm the total field is set.
        $this->writeTestDescription("1. GET request for a Statement returns a 200 status code and the total field should be set.");
        $response = $this->sendRequest($client, $token, 'GET', '/v2/statements/1', []);
        $this->assertResponseStatusCodeSame(200, "A Statements GET request should return a 200 OK status code.");
        $this->assertStringContainsString('"total":' . $total_billed[1], $response->getContent(), 'The response should contain the total field.');

        // Send GET collection request and confirm the count of Statements is correct.
        $this->writeTestDescription("2. GET collection request for Statement returns a 200 status code and the count " . '(' . ($i - 1) . ').');
        $response = $this->sendRequest($client, $token, 'GET', '/v2/statements', []);
        $this->assertResponseStatusCodeSame(200, "A Statement GET collection request should return a 200 OK status code.");
        $this->assertStringContainsString('"count":' . ($i - 1), $response->getContent(), 'The response should contain the count of statements (' . ($i - 1) . ').');

        // Send GET request and filter by created date.
        $start = '2020-02-02';
        $end = '2020-03-04';
        $later = '2020-04-04';
        $this->writeTestDescription("3. GET collection request for Statements using created.start and created.end filters returns a 200 status code and not a Statement with a later created date.");
        $response = $this->sendRequest($client, $token, 'GET', '/v2/statements', ['created.start' => $start, 'created.end' => $end]);
        $this->assertResponseStatusCodeSame(200, "A Statements GET collection request should return a 200 OK status code.");
        $this->assertStringContainsString('"created":"' . $start . '"', $response->getContent(), 'The response should contain the created.start field ' . $start . '.');
        $this->assertStringNotContainsString('"created":"' . $later . '"', $response->getContent(), 'The response should not contain a statement with a later date such as ' . $later . '.');

        // Send GET request and sort desc.
        $this->writeTestDescription("4. GET collection request for Statements in descending order returns a 200 status code and begins with the created date of the Statement with the highest id.");
        $response = $this->sendRequest($client, $token, 'GET', '/v2/statements', ['sort' => 'desc']);
        $this->assertResponseStatusCodeSame(200, "A Statements GET collection request should return a 200 OK status code.");
        $last_created = date_format($created[($i - 1)], 'Y-m-d');
        $this->assertStringContainsString('[{"created":"' . $last_created . '"', $response->getContent(), 'The response should begin with the created date of the Statement with the highest id ' . $last_created . '.');

        /* / Send GET collection request and confirm pagination.
        $limit = 5;
        $page_num = 2;
        $first_created = date_format($created[1], 'Y-m-d');
        $this->writeTestDescription("5. A page $page_num GET collection request for Statements returns a 200 status code and a Statement from page $page_num and not the first Statement.");
        $response = $this->sendRequest($client, $token, 'GET', '/v2/statements', ['limit' => $limit, 'page' => $page_num]);
        $this->assertResponseStatusCodeSame(200, "A Statement GET collection request should return a 200 OK status code.");
        $this->assertStringNotContainsString('[{"created":"' . $first_created . '"', $response->getContent(), 'The response should not begin with the created date of the first Statement since it is not page 1.');
        $this->assertStringContainsString('"id":' . ($page_num * $limit - 1), $response->getContent(), 'The response should contain the id of Statement on the selected page (' . $page_num . ').');
        */
    }

    protected function createStatement($em, $data, $user)
    {
        $statement = new Statements();
        $statement->setUser($user);
        $statement->created = $data['created'];
        $statement->total_billed = $data['total_billed'];

        $em->persist($statement);
        $em->flush();
    }
}
