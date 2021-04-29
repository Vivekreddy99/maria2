<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\Products;

class ProductsController extends BaseEntityController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Deletes Products and all references, if they aren't in use.
     *
     * @param int $id Product id
     * @return Response Error message or 200 Ok response.
     *
     * TODO: Revisit to make sure all references are accounted for.
     */
    public function delete($id)
    {
        // Check that the owner is the one making the request.
        if (!$this->isOwner('App\Entity\Products', ['id' => $id])) {
            return $this->notFoundResponse;
        }

        // Get SKU's, Inbound, and Warhouses.
        $skus = $this->entity->products_skus;
        $inbound_products = $this->entity->inbound_products;
        $warehouses_products = $this->entity->warehouses_products;

        $name = $this->entity->getName();
        $name = isset($name) ? $name . ' (id:' . $id . ')' : $id;
        $related_order_ids = [$name => []];

        // Check if any SKU's are part of Orders.
        $can_delete_skus = true;
        foreach ($skus as $obj) {
            if (is_object($obj) && isset($obj->sku)) {
                $order_ids = $this->isSkuUsedInOrder($obj, $obj->sku);
                if (!empty($order_ids)) {
                    $related_order_ids[$name] = array_merge($related_order_ids[$name], $order_ids);
                    $can_delete_skus = false;
                }
            }
        }

        // Check if any inbound shipment contain this product.
        $related_inbound_ids = [$name => []];
        $can_delete_inbound = true;
        if ($can_delete_skus) {
            foreach ($inbound_products as $obj) {
                $related_inbound_ids[$name][] = $obj->getId();
                $can_delete_inbound = false;
            }
        }

        // Check if any warehouses stock this product.
        $related_warehouse_ids = [$name => []];
        $can_delete_warehouse = true;
        if ($can_delete_skus && $can_delete_inbound) {
            foreach ($warehouses_products as $obj) {
                if (\property_exists($obj, 'wh')) {
                    $related_warehouse_ids[$name][] = $obj->wh->getId();
                    $can_delete_warehouse = false;
                }
            }
        }

        // Delete Product and related items.
        if ($can_delete_skus && $can_delete_inbound && $can_delete_warehouse) {
            // Deletes product and related Skus.
            $this->em->remove($this->entity);
            $this->em->flush();
            return new Response(null, 204);
        } else {
            $in_use = '';
            if (!$can_delete_skus) {
                $in_use .= $this->getInUseMessage($related_order_ids, 'Product', 'Order id');
            }
            if (!$can_delete_inbound) {
                $in_use .= $this->getInUseMessage($related_inbound_ids, 'Product', 'Inbound shipment id');
            }
            if (!$can_delete_warehouse) {
                $in_use .= $this->getInUseMessage($related_warehouse_ids, 'Product', 'Warehouse id', 'is inventoried in');
            }
            return new JsonResponse(['message' => "Products that are in use cannot be deleted." . $in_use], 403);
        }
    }

    // Deletes SKUs that aren't in use by an Order.
    public function skuDelete($id, $shop_id, $sku, Request $request)
    {
        // Check that the owner is the one making the request.
        if (!$this->isOwner('App\Entity\ProductsSkus', ['product' => $id, 'shop' => $shop_id])) {
            return $this->notFoundResponse;
        }

        if (isset($this->entity->sku) && \strtolower($sku) == \strtolower($this->entity->sku)) {

            // SKUs referenced by an order can't be deleted.
            $order_ids = $this->isSkuUsedInOrder($this->entity, $sku);

            if (empty($order_ids)) {
                $this->em->remove($this->entity);
                $this->em->flush();
                return new Response(null, 204);
            } else {
                $order_str = $this->getInUseMessage([$sku => $order_ids], 'Sku', 'Order id');
                return new JsonResponse(['message' => "Skus referenced by an order cannot be deleted." . $order_str], 403);
            }

        } else {
            return $this->notFoundResponse;
        }
    }

    public function skuPut($id, $shop_id, $sku, Request $request)
    {
        // Check that the owner is the one making the request.
        if (!$this->isOwner('App\Entity\ProductsSkus', ['product' => $id, 'shop' => $shop_id])) {
            return $this->notFoundResponse;
        }

        if (isset($this->entity->sku) && $this->entity->sku == $sku) {
            // Default values.
            $newSku = $this->entity->sku;
            $newActive = isset($this->entity->active) ? $this->entity->active : 1;

            // Parse json request for new values.
            $json = $request->getContent();
            $arr = json_decode($json, true);
            if (isset($arr) && is_array($arr) && isset($arr['skus'])) {
                if (isset($arr['skus']['sku']) && !empty($arr['skus']['sku'])) {
                    $newSku = $arr['skus']['sku'];
                }
                if (isset($arr['skus']['active']) && is_bool($arr['skus']['active'])) {
                    $newActive = $arr['skus']['active'] ? 1 : 0;
                }
            }

            // Update found entity with new data.
            $this->entity->setSku($newSku);
            $this->entity->setActive($newActive);
            $this->entity->setShopId($shop_id);
            $this->em->flush();

            // All affected orders' line items will have their SKU changed.
            $orders = $this->entity->shop->orders;
            foreach ($orders as $order) {
                $line_item = $this->em->find('App\Entity\LineItems', ['order' => $order->getId(), 'product' => $id]);
                if (is_object($line_item) && isset($line_item->sku) && $line_item->sku == $sku) {
                    $line_item->sku = $newSku;
                    $this->em->flush();
                }
            }

            // Send response. Lookup and return parent Product.
            // $product = $this->em->find('App\Entity\Products', $id);
            $product = $this->entity->product;
            if ($product instanceof Products) {
                $response = new JsonResponse($product->jsonSerialize(), 200);
            } else {
                return new Response('Product not found.', 404);
            }
        } else {
            return $this->notFoundResponse;
        }

        return $response;
    }

    /**
     * Checks if SKU is in use in an Order.
     * @param object $data ProductsSkus object.
     * @param string $sku Sku string to compare with related ProductsSku.
     *
     * @return array Returns Order Ids if used.
     */
    public function isSkuUsedInOrder($data, $sku)
    {
        $order_ids = [];

        $line_items = $data->product->line_items;
        foreach ($line_items as $line_item)
        {
            if (is_object($line_item) && isset($line_item->sku) && \strtolower($line_item->sku) == \strtolower($sku)) {
                $order_ids[] = $line_item->order->getId();
            }
        }

        return $order_ids;
    }

    /**
     * Gets string of "in use" items ("SKU $sku is in is used in Orders $ids").
     *
     * @param $arr
     *   List of items.
     * @param $type
     *   The name of the entity that is in use.
     * @param $parent
     *   The name of the using entity.
     * @param $is_used_in_str
     *   An overrideable phrase
     *
     * @return string $str Formatted string.
     */
    protected function getInUseMessage($arr, $type, $parent, $is_used_in_str = 'is used in')
    {
        $str = '';
        foreach ($arr as $key => $values) {
            $s = '';
            $list = $values;
            if (is_array($values)) {
                $s = count($values) > 1 ? 's' : '';
                $list = implode(', ' , $values);
            }
            $str .= ' ' . $type . ': ' . $key . ' ' . $is_used_in_str . ' ' . $parent . $s . ': ' . $list;
        }

        return $str;
    }
}
