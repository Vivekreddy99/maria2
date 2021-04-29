<?php
# src/App/EventSubscriber/OrdersSubscriber.php

namespace App\EventSubscriber;

use App\Entity\Orders;
use App\Entity\Products;
use App\Entity\Shipments;
use App\Entity\Statements;
use ApiPlatform\Core\EventListener\EventPriorities;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class CollectionCountSubscriber
 * @package App\EventSubscriber
 *
 *  Gets count of items in a GET Collection for Orders and Shipments
 *    following the filtering process to be added to the JSON wrapper.
 */
class CollectionCountSubscriber implements EventSubscriberInterface
{
    /**
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => [
                ['getItemCount', EventPriorities::PRE_SERIALIZE],
            ],
        ];
    }

    // Get Order Count after filtering.
    public function getItemCount(ViewEvent $event)
    {
        // Limit to Orders Entity.
        $resource = $event->getRequest()->attributes->get('_api_resource_class');

        if (!in_array($resource, ['App\Entity\Orders', 'App\Entity\Products', 'App\Entity\Shipments', 'App\Entity\Statements'])) {
            return;
        }

        $result = $event->getControllerResult();

        if (isset($result) && method_exists($result, 'getTotalItems')) {
            $count = $result->getTotalItems();

            // Set total_shipments/total_orders for the first item.
            //   Totals are added to the wrapper in SerializationWrapperSubscriber.
            foreach($result as $item) {
                if (Orders::class == $resource) {
                    $item->total_orders = $count;
                } elseif (Products::class == $resource) {
                    $item->count = $count;
                } elseif (Shipments::class == $resource) {
                    $item->total_shipments = $count;
                } elseif (Statements::class == $resource) {
                    $item->count = $count;
                }
                break;
            }
        }
    }
}
