<?php
// src/App/Controller/ShopsController.php

namespace App\Controller;

use App\Entity\ProductsSkus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\LineItems;
use App\Entity\Products;
use App\Entity\InboundProducts;

class ShopsController extends BaseEntityController
{
    /**
     * Deletes Shop. Production shops with orders or products can't be deleted.
     *   Test shop orders, line_items, fulfillments, and skus will be deleted
     *   automatically. Test shop Products will be deleted here and
     *   ProductsWarehouses will be automatically removed with the Products.
     */
    public function delete($id)
    {
        // Check that the owner is the one making the request.
        if (!$this->isOwner('App\Entity\Shops', ['id' => $id])) {
            return $this->notFoundResponse;
        }

        $can_delete = true;
        if (isset($this->entity->test))
        {
            $em2 = $this->getDoctrine()->getManager();

            // Check for Orders. If it's a test Shop, remove the Products
            // associated with the Orders' LineItems.
            if (isset($this->entity->orders) && !empty($this->entity->orders) && \count($this->entity->orders) > 0) {
                $can_delete = $this->entity->test;

                if ($can_delete) {
                    foreach($this->entity->orders as $order) {
                        if (isset($order->line_item_list) && \count($order->line_item_list) > 0)
                        {
                            foreach($order->line_item_list as $line_item)
                            {
                                if ($line_item instanceof LineItems && isset($line_item->product) && $line_item->product instanceof Products)
                                {
                                    $pid = $line_item->product->getId();
                                    $obj = $em2->find('App\Entity\Products', ['id' => $pid]);

                                    // Should not occur for Products in test shops, but check
                                    // for related inbound_products to avoid MySQL FK constraint error.
                                    if (isset($line_item->product->inbound_products) && $line_item->product->inbound_products instanceof InboundProducts) {
                                        return new JsonResponse(['message' => "Product id $pid is part of an Inbound Shipment and cannot be deleted, please contact supprt@boxc.com about deleting your test shop."], 400);
                                    } else {
                                        $em2->remove($obj);
                                        $em2->flush();
                                    }
                                }
                            }
                        }
                    }
                }
            }

            // Check for ProductSkus. If it's a test Shop, remove the Products
            // associated with the Skus.
            if (isset($this->entity->products_skus) && !empty($this->entity->products_skus) && count($this->entity->products_skus) > 0) {
                $can_delete = $this->entity->test;

                if ($can_delete) {
                    foreach($this->entity->products_skus as $sku) {
                        if (isset($sku) && $sku instanceof ProductsSkus && isset($sku->product) && $sku->product instanceof Products) {
                            $obj = $em2->find('App\Entity\Products', ['id' => $sku->product->getId()]);
                            $em2->remove($obj);
                            $em2->flush();
                        }
                    }
                }
            }

            // Delete shop.
            if ($can_delete) {
                $this->em->remove($this->entity);
                $this->em->flush();
                return new Response(null, 204);
            } else {
                return new JsonResponse(['message' => 'Production shops with orders or products cannot be deleted.'], 403);
            }

        } else {
            $json_response = (object)['message' => 'Not found.'];
            return new JsonResponse($json_response, 404);
        }
    }
}
