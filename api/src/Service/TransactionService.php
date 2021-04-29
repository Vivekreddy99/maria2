<?php
// src/Service/TransactionService.php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpClient\HttpClient;
use App\Entity\Users;

class TransactionService
{
    private $client;
    private $em;

    public function __construct($entityManager)
    {
        $client = HttpClient::create();
        $this->client = $client;
        $this->em = $entityManager;
    }

    public function postItem($currency, $amount, $from, $to, $category)
    {
        // Check that from and to are valid users.
        $users = [$from, $to];
        foreach ($users as $temp) {
            $user = $this->em->getRepository(Users::class)->findOneBy(['email' => $temp]);
            if (!$user || !$user->getId())  {
                return 400;
            }
        }

        $url = $_ENV['LEDGER_ROOT'] . '/v1/transactions';

        $response = $this->client->request('POST', $url, [
            'headers' => ['Content-type' => 'application/json'],
            'json' => [
                'transaction' => [
                    'currency' => $currency,
                    'amount' => $amount,
                    'category' => $category,
                    'from' => $from,
                    'to' => $to,
                    'metadata' => ['from' => $from, 'to' => $to]
                ],
            ],
        ]);

        if ($response && method_exists($response, 'getStatusCode')) {
            return $response->getStatusCode();
        }
        else {
            return 500;
        }

    }

}
