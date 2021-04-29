<?php
# src/App/EventSubscriber/SerializationWrapperSubscriber.php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

class SerializationWrapperSubscriber implements EventSubscriberInterface
{

    protected $mapping = [
        'entry_points',
        'estimate',
        'fulfillments',
        'inbound',
        'labels',
        'manifests',
        'orders',
        'overpacks',
        'products',
        'shipments',
        'shops',
        'statements',
        'warehouses',
    ];

    public static function getSubscribedEvents() {

        // return the subscribed events, their methods and priorities
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    /**
     * @param ResponseEvent $event
     *
     * Add Entity wrapper to Collection response.
     */
    public function onKernelResponse(ResponseEvent $event)
    {
        $request = $event->getRequest();

        // Don't implement for Unit Tests (i.e. host=example.com)
        if (is_array($_ENV) && isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] == 'test') {
            return;
        }

        $path = $request->getPathInfo();

        // Get wrapper entity from request.
        $entity = null;
        if (!empty($path)) {
            $entities = $this->mapping;
            foreach ($entities as $key) {
                // Should always end with entity name.
                if (substr($path, -strlen('/' . $key)) == '/' . $key) {
                    $entity = $key;
                    break;
                }
            }
        }

        $response = $event->getResponse();
        if (is_object($response))
        {
            $content = $response->getContent();

            if (empty($entity)) {
                // Send the current response content as is, if no entity is defined.
                print $content;
                exit();
            } else {

                // Check if content is a Json array, and not an empty array [].
                $is_json_array = $content && \strlen($content) > 2 && \substr($content, 0, 1) == '[' && substr($content, -1) == ']';
                if ($is_json_array)
                {
                    if ($key == 'estimate') {
                        // Strip enclosing [] since it's always singular
                        $content = substr($content, 1, -1);
                    } elseif ($key == 'orders') {
                        // Calculate and add total orders and total pages.
                        $request = Request::createFromGlobals();
                        $qs = $request->getQueryString();

                        // Get number per page.
                        $limit = 50; // Default limit.
                        \parse_str($qs, $params);
                        if (isset($params['limit']) && intval($params['limit']) > 0 && intval($params['limit']) < 101) {
                            $limit = intval($params['limit']);
                        }

                        // Get total orders.
                        $arr = \json_decode($content, TRUE);

                        $pager_info = $this->getPagerInfo($arr, 'total_orders', $limit);
                        $total_orders = $pager_info['total_orders'];
                        $total_pages = $pager_info['total_pages'];

                        // Remove total_orders attribute from items.
                        $content = preg_replace('/,[ ]?"total_orders":[ ]?[0-9]+/', '', $content);

                        $content .= ', "total_pages":' . $total_pages . ', "total_orders":' . $total_orders;

                    } elseif ($key == 'products') {
                        // Calculate and add total orders and total pages.
                        $request = Request::createFromGlobals();
                        $qs = $request->getQueryString();

                        // Get number per page.
                        $limit = 50; // Default limit.
                        \parse_str($qs, $params);
                        if (isset($params['limit']) && intval($params['limit']) > 0 && intval($params['limit']) < 101) {
                            $limit = intval($params['limit']);
                        }

                        // Get current page.
                        $current_page = 1;
                        if (isset($params['page']) && intval($params['page']) > 0 && intval($params['page']) < 101) {
                            $current_page = intval($params['page']);
                        }

                        // Get product count.
                        $arr = \json_decode($content, TRUE);

                        $pager_info = $this->getPagerInfo($arr, 'count', $limit);
                        $total_count = $pager_info['count'];
                        $total_pages = $pager_info['total_pages'];

                        // Remove count attribute from items.
                        $content = preg_replace('/,[ ]?"count":[ ]?[0-9]+/', '', $content);

                        $content .= ', "count": ' . $total_count . ', "page":' . $current_page . ', "pages":' . $total_pages;

                    } elseif ($key == 'shipments') {

                        // Calculate and add total shipments and total pages.
                        $request = Request::createFromGlobals();
                        $qs = $request->getQueryString();

                        // Get number per page.
                        $limit = 50; // Default limit.
                        \parse_str($qs, $params);
                        if (isset($params['limit']) && intval($params['limit']) > 0 && intval($params['limit']) < 101) {
                            $limit = intval($params['limit']);
                        }

                        // Get total shipments.
                        $arr = \json_decode($content, TRUE);

                        $pager_info = $this->getPagerInfo($arr, 'total_shipments', $limit);
                        $total_shipments = $pager_info['total_shipments'];
                        $total_pages = $pager_info['total_pages'];

                        // Remove total_shipments attribute from items.
                        $content = preg_replace('/,[ ]?"total_shipments":[ ]?[0-9]+/', '', $content);

                        $content .= ', "total_pages":' . $total_pages . ', "total_shipments":' . $total_shipments;

                    } elseif ($key == 'statements') {
                        // Calculate and add count and total pages.
                        $request = Request::createFromGlobals();
                        $qs = $request->getQueryString();

                        // Get number per page.
                        $limit = 50; // Default limit.
                        \parse_str($qs, $params);
                        if (isset($params['limit']) && intval($params['limit']) > 0 && intval($params['limit']) < 101) {
                            $limit = intval($params['limit']);
                        }

                        // Get current page.
                        $current_page = 1;
                        if (isset($params['page']) && intval($params['page']) > 0 && intval($params['page']) < 101) {
                            $current_page = intval($params['page']);
                        }

                        // Get statements count.
                        $arr = \json_decode($content, TRUE);

                        $pager_info = $this->getPagerInfo($arr, 'count', $limit);
                        $total_count = $pager_info['count'];
                        $total_pages = $pager_info['total_pages'];

                        // Remove count attribute from items.
                        $content = preg_replace('/,[ ]?"count":[ ]?[0-9]+/', '', $content);

                        $content .= ', "count": ' . $total_count . ', "page":' . $current_page . ', "pages":' . $total_pages;
                    } // End Statements.

                    // Add entity wrapper.
                    $content = '{"' . $entity . '": ' . $content . '}';
                    $response->setContent($content);

                } // End of Is JSON Array block.
            } // End of Has Response block.
        } // End of Is Entity block.
    }

    /**
     * Get number of pages, total items post filtering.
     *
     * @param array $arr - Entity items.
     * @param string $key - Field name, like total_orders, or total_shipments.
     * @param int $limit - Number of items per page.
     * @return array $info - Total pages and Total items (referenced by $key).
     */
    protected function getPagerInfo($arr, $key, $limit)
    {
        $info = ['total_pages' => 0, $key => 0];

        if (is_int($limit) && $limit > 0 && is_array($arr) && isset($arr[0]) && isset($arr[0][$key])) {
            $total_items = intval($arr[0][$key]);

            // Calculate total pages.
            $total_pages = \ceil($total_items/$limit);

            $info = ['total_pages' => $total_pages, $key => $total_items];
        }

        return $info;
    }
}
