<?php
// api/src/DataProvider/TransactionCollectionDataProvider.php

namespace App\DataProvider;

use ApiPlatform\Core\DataProvider\ContextAwareCollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use ApiPlatform\Core\Exception\ResourceClassNotSupportedException;
use App\Entity\Transaction;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class TransactionCollectionDataProvider implements ContextAwareCollectionDataProviderInterface, RestrictedDataProviderInterface
{
    private $client;
    private $tokenStorageInterface;

    public function __construct(HttpClientInterface $client, TokenStorageInterface $tokenStorageInterface)
    {
        $this->client = $client;
        $this->tokenStorageInterface = $tokenStorageInterface;
    }

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return Transaction::class === $resourceClass;
    }

    public function getCollection(string $resourceClass, string $operationName = null, array $context = []): iterable
    {
        $retval = [];

        // Set account to the current user's name.
        $current_user = $this->tokenStorageInterface->getToken()->getUser();
        $account = isset($current_user) && method_exists($current_user, 'getUsername') ? $current_user->getUsername() : null;
        $currency = null;
        $next_page = null;
        $sort = null;
        $start = null;
        $end = null;
        $limit = 50; // Max 100.
        $page = 1;

        if (isset($context['filters'])) {
            if (isset($context['filters']['currency'])) {
                $currency = $context['filters']['currency'];
            }
            // Admin role bypass for any account.
            if (isset($context['filters']['account']) && isset($current_user) && method_exists($current_user, 'getRoles')) {
                $roles = $current_user->getRoles();
                foreach ($roles as $role) {
                    if ($role == "ROLE_ADMIN") {
                        // If current user is an admin, use the query string account instead.
                        $account = $context['filters']['account'];
                        break;
                    }
                }
            }
            if (isset($context['filters']['next_page'])) {
                $next_page = $context['filters']['next_page'];
            }
            if (isset($context['filters']['sort']) && in_array($context['filters']['sort'], ["asc", "desc"])) {
                $sort = $context['filters']['sort'];
            }
            if (isset($context['filters']['created.start'])) {
                $start = $context['filters']['created.start'];
            }
            if (isset($context['filters']['created.end'])) {
                $end = $context['filters']['created.end'];
            }
            if (isset($context['filters']['page'])) {
                $page_param = intval($context['filters']['page']);
                if ($page_param > 1) {
                    $page= $page_param;
                }
            }
            if (isset($context['filters']['limit'])) {
                $limit_param = intval($context['filters']['limit']);
                if ($limit_param > 50 && $limit_param < 101) {
                    $limit= $limit_param;
                }
                elseif ($limit_param < 50 || $limit_param > 100) {
                    return ['error' => 'Limit must be between 50 and 100.'];
                }
            }
        }

        if (empty($account) || empty($currency)) {
            return ['message' => 'Not found.'];
        }

        // Query Ledger API.
        try {
            $url = $_ENV['LEDGER_ROOT'] . '/v1/transactions?account=' . $account . '&currency=' . $currency;
            // Set sort.
            if (!empty($sort)) {
                $url .= '&sort=' . $sort;
            }
            // Set start and end dates, use defaults if one is not set.
            if (empty($start)) { // Set default start date to 1 year ago.
                $one_year_ago = strtotime("-1 year", time());
                $start = date("Y-m-d", $one_year_ago);
            }
            if (empty($end)) {
                $end = date("Y-m-d", time());
            }
            // Ensure start is less than end.
            if ($start < $end) {
                $url .= '&start_date=' . $start . '&end_date=' . $end;
            }
            else {
                return ['error' => 'created.start must be earlier than created.end'];
            }
            // Set limit.
            if (!empty($limit)) {
                $url .= '&per_page=' . $limit;
            }

            // Initialize return []
            $retval = [
                'account' => $account,
                'currency' => $currency,
                'created.start' => $start,
                'created.end' => $end,
                'transactions' => [],
                'page' => $page,
                'count' => 0,
                'limit' => $limit
            ];

            // Paginate as needed.
            $page_ct = 1;
            $page_tcount = 0;
            do {
                $tcount = 0;
                $next_page = false;
                $response = $this->client->request('GET', $url);
                $statusCode = $response->getStatusCode();

                $arr = $response->toArray();
                if ($statusCode == 200 && is_array($arr) && isset($arr['transactions']) && is_array($arr['transactions'])) {
                    $tcount = count($arr['transactions']);

                    // Remove next_page parameter.
                    $ind = strpos($url, "&next_page");
                    if ($ind !== false) {
                        $url = substr($url, 0, $ind);
                    }
                    // Add next_page parameter if present.
                    if (isset($arr['next_page']) && !empty($arr['next_page'])) {
                        $url .= '&next_page=' . $arr['next_page'];
                        $next_page = true;
                    }
                    // Reached page.
                    if ($page_ct == $page) {
                        // Prepare transactioins.
                        $transactions = [];
                        foreach ($arr['transactions'] as $transaction) {
                            // Remove hash_id.
                            unset($transaction['hash_id']);
                            // Remove account and currency.
                            unset($transaction['account']);
                            unset($transaction['currency']);
                            // Ensure balance is 2 decimal places.
                            if (isset($transaction['balance'])) {
                                $transaction['balance'] = number_format($transaction['balance'], 2, '.', '');
                            }
                            // Reformat date.
                            if (isset($transaction['datetime'])) {
                                $transaction['created'] = date("Y-m-d", strtotime($transaction['datetime']));
                                unset($transaction['datetime']);
                            }
                            $transactions[] = $transaction;
                        }
                        $retval['transactions'] = $transactions;
                        $retval['count'] = $tcount;
                    }
                }
            } while($page_ct++ && $next_page);

        } catch (Exception $ex) {
            if ($_ENV['APP_ENV'] == 'dev') {
                return ['message' => $ex->__toString()];
            }
            else {
                return ['message' => 'Request failed.'];
            }
        }
        
        $page_ct--;
        $retval['pages'] = $page_ct;

        return $retval;
    }
}
