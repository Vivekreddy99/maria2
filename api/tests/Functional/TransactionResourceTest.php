<?php
// tests/Functional/TransactionResourceTest.php

namespace App\Tests\Functional;

require_once __DIR__.'/../../vendor/autoload.php';

use Doctrine\ORM\EntityManagerInterface;
use Hautelook\AliceBundle\PhpUnit\ReloadDatabaseTrait;
use App\Test\CustomApiTestCase;
use App\Entity\Transaction;
use App\Service\TransactionService;

class TransactionResourceTest extends CustomApiTestCase
{
    use ReloadDatabaseTrait;

    public function testPostTransaction(): void
    {

        $this->writeTestDescription("TRANSACTIONS");
        $client = self::createClient();
        $entity_manager = self::$container->get(EntityManagerInterface::class);
        $service = new TransactionService($entity_manager);

        // Create users.
        $user = $this->createUser('test_acct_for_deletion_1', 'foo', 'en');
        $user = $this->createUser('1486', 'foo', 'en');

        $this->writeTestDescription("1. Post Multiple Transactions");
        for($i = 0; $i < 1; $i++) {
            // Use Transaction service to post Transaction.
            $amount = $i * 100 + 20 * $i + $i + $i/100;
            $status_code = $service->postItem('CNY', $amount, 'test_acct_for_deletion_1', '1486', 'SBB Income');
        }

        $this->assertEquals(201, $status_code, "201 Response expected, but $status_code given.");
    }

}
