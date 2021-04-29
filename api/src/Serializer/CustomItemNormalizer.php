<?php
// api/src/Serializer/CustomItemNormalizer.php

namespace App\Serializer;

use App\Entity\Products;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Doctrine\ORM\EntityManagerInterface;
use ApiPlatform\Core\Api\IriConverterInterface;
use App\Entity\Shipments;

final class CustomItemNormalizer implements NormalizerInterface, DenormalizerInterface
{
    private $normalizer;
    private $replaceIdsWithIRIs = [
        'ep' => 'entry_points',
        'ep_id' => 'entry_points',
        'entry_point' => 'entry_points',
        'manifest' => 'manifests',
        'manifest_id' => 'manifests',
        'overpack_id' => 'overpacks',
        'overpacks' => 'overpacks', // For Manifests POST
        'overpacks_details' => 'overpacks',
        'shipments' => 'shipments',
    ];
    private $decimalFields = [
        'fulfillment_fee',
        'packaging_fee',
        'packing_slip_fee',
        'shipping_cost',
        'cost',
        'value',
        'sold_for',
        // 'weight', Not a decimal for Overpacks
        'process_fee',
        'verify_fee',
    ];
    private $iriPrefix = '/v2/';
    private $tokenStorage;
    private $entityManager;
    private $iriConverter;
    private $currentUser;

    public function __construct(NormalizerInterface $normalizer, TokenStorageInterface $tokenStorage, EntityManagerInterface $entityManager, IriConverterInterface $iriConverter)
    {
        $this->tokenStorage = $tokenStorage;
        $this->entityManager = $entityManager;
        $this->iriConverter = $iriConverter;

        if (!$normalizer instanceof DenormalizerInterface) {
            throw new \InvalidArgumentException('The normalizer must implement the DenormalizerInterface');
        }

        $this->normalizer = $normalizer;
    }

    public function denormalize($data, $class, ?string $format = null, array $context = [])
    {
        $this->currentUser = $this->tokenStorage->getToken()->getUser()->getUserObject()->getId();

        // Flag that $class matches URI.
        $class_matches_uri = false;

        // Remove class wrapper.
        if (preg_match('/App\\\\Entity\\\\(.*)/', $class, $matches)) {
            $class_key = strtolower($matches[1]);
            // Fix for underscored request.
            if ($class_key == 'warehousesproducts') {
                $class_key = 'warehouses_products';
            } elseif ($class_key == 'lineitems') {
                $class_key = 'line_items';
            } elseif ($class_key == 'productsskus') {
                $class_key = 'skus';
            }

            if (substr($context['uri'], -strlen($class_key)) == $class_key) {
                $class_matches_uri = true;
            }

            if (substr($class_key, -1) == 's' && $class_key !== 'skus') {
                $class_key = substr($class_key, 0, -1);
            }

            if (isset($data[$class_key])) {
                $data = $data[$class_key];
            }
        }

        // Extra check that id cannot be set.
        // if (isset($context['collection_operation_name']) && $context['collection_operation_name'] == 'post' && isset($data['id'])) {
        if ($class_matches_uri && isset($data['id'])) {
            if ($class == 'App\Entity\WarehousesProducts') {
                $data['wh_id'] = $data['id'];
                $data['wh'] = '/v2/warehouses/' . $data['id'];
            }
            unset($data['id']);
        }

        $data_keys = is_array($data) && sizeof($data) > 0 ? array_keys($data) : $data = [];

        // Converts select relationship id's to IRI's.
        $match = empty($data) ? [] : array_intersect($data_keys, array_keys($this->replaceIdsWithIRIs));
        foreach ($match as $key) {
            // Convert Overpack id array to array of objects.
            if (in_array($key, ['overpacks', 'overpacks_details']) && is_array($data[$key])) {
                $prefix = $format == 'json-ld' ? '@' : '';
                foreach ($data[$key] as $ind => $val) {
                    if (is_int($val)) {
                        $data[$key][$ind] = [$prefix . 'id' => $val];
                        $data['overpacks_details'][$ind] = $data[$key][$ind];
                    }
                }

            } elseif (is_string($data[$key]) && strpos($data[$key], '/') === FALSE) {
                $data[$key] = $this->iriPrefix . $this->replaceIdsWithIRIs[$key] . '/' . $data[$key];
            }
        }

        // Converts Decimal fields to String as expected by API Platform.
        $match = empty($data) ? [] : array_intersect($data_keys, $this->decimalFields);
        foreach ($match as $key) {
            $data[$key] = strval($data[$key]);
        }

        // Entity specific modifications.
        if ($class == 'App\Entity\Fulfillments') {
            // Fix for IRI's'.
            if (isset($data['shipment_id']) && is_numeric($data['shipment_id'])) {
                $data['shipment_id'] = '/v2/shipments/' . $data['shipment_id'];
            }
            if (isset($data['order_id']) && is_numeric($data['order_id'])) {
                $data['order_id'] = '/v2/orders/' . $data['order_id'];
            }
        } elseif ($class == 'App\Entity\Shipments') {
            // Add updated data to Shipments, to trigger the setter.
            $data['updated'] = "now";
        } elseif ($class == 'App\Entity\Overpacks') {
            if (isset($context['item_operation_name']) && $context['item_operation_name'] == 'patch') {
                // Reformat "add" and "remove" shipment elements.
                if (isset($data['shipments'])) {
                    if (isset($data['shipments']['add']) && is_array($data['shipments']['add']) && !empty($data['shipments']['add'])) {
                        $data['new_shipments'] = [];
                        foreach ($data['shipments']['add'] as $id) {
                            if ($temp = $this->validShipment($id)) {
                                $data['new_shipments'][] = '/v2/shipments/' . $temp;
                            }
                        }
                    }
                    if (isset($data['shipments']['remove']) && is_array($data['shipments']['remove']) && !empty($data['shipments']['remove'])) {
                        $data['patch_shipments_remove'] = [];
                        foreach ($data['shipments']['remove'] as $id) {
                            if ($temp = $this->validShipment($id)) {
                                $data['patch_shipments_remove'][] = '/v2/shipments/' . $temp;
                            }
                        }
                    }
                    unset($data['shipments']);
                }
            }
        } elseif ($class == 'App\Entity\Orders') {
            // Don't allow status of Packing or Fulfilled to be overwritten.
            if (isset($context['object_to_populate'])) {
                $current_status = $context['object_to_populate']->status;
                if (in_array(strtolower($current_status), ['packing', 'fulfilled'])) {
                     $data['status'] = $current_status;
                }
                // Remove existing line_items if new line_items are being imported.
                if (isset($data['line_items'])) {
                    foreach($context['object_to_populate']->line_item_list as $ind => $line_item) {
                        $this->entityManager->remove($line_item);
                        $this->entityManager->flush();
                    }
                }
            }

            // Format line items.
            if (isset($data['line_items']) && is_array($data['line_items'])) {
                $data['attributes_line_items'] = [];
                $data['line_item_list'] = [];

                foreach ($data['line_items'] as $ind => $arr) {
                    if (isset($arr) && isset($arr['product_id']))
                    {
                        // Verify that Product Id is valid.
                        $prod = $this->entityManager->find('App\Entity\Products', ['id' => $arr['product_id']]);
                        if (!($prod instanceof Products) || $prod->getUser()->getId() !== $this->currentUser) {
                            continue;
                        } // TODO: Add Product not found error.

                        // Add line_items fields as attributes,
                        //   since the line_items[] is being used for
                        //   the IRIs of the related Products.
                        $data['attributes_line_items'][$arr['product_id']] = [];
                        $data['attributes_line_items'][$arr['product_id']]['current_user'] = $this->currentUser;

                        if (isset($arr['sku'])) {
                            $data['attributes_line_items'][$arr['product_id']]['sku'] = $arr['sku'];
                        }
                        if (isset($arr['quantity'])) {
                            $data['attributes_line_items'][$arr['product_id']]['quantity'] = $arr['quantity'];
                        }
                        if (isset($arr['sold_for'])) {
                            $data['attributes_line_items'][$arr['product_id']]['sold_for'] = $arr['sold_for'];
                        }

                        // Because of api platform limitations regarding creating embedded objects
                        //  via IRI's and because LineItems use composite keys, we are setting the
                        //  IRI to the Products IRI and getting the Order half of the composite
                        //  key dynamically.
                        $data['line_item_list'][] = '/v2/products/' . $arr['product_id'];

                    }
                }
                unset($data['line_items']);
            }

            // Generate created value from user input.
            // $created_temp = new \DateTimeImmutable();
            if (isset($data['created']) && is_string($data['created'])) {
                try {
                    $date = strtotime($data['created']);
                    if ($date === false) {
                        $data['created'] = date('Y-m-d H:i:s');
                    }
                } catch (\Exception $e) {
                    // Date formatting error, so use current timestamp.
                    $data['created'] = date('Y-m-d H:i:s');
                }
            }

            // Get shop id and shop order id.
            if (isset($data['shop']) && is_array($data['shop']) && isset($data['shop']['id']) && is_string($data['shop']['id'])) {
                if (isset($data['shop']['order_id'])) {
                    $data['shop_order_id'] = $data['shop']['order_id'];
                }
                $data['shop'] = '/v2/shops/' . $data['shop']['id'];
            }

        } elseif ($class == 'App\Entity\Products') {
            if (isset($context['object_to_populate'])) {
                $id = $context['object_to_populate']->getId();
            }

            // Format WarehousesProducts Items.
            if (isset($data['warehouses']) && is_array($data['warehouses'])) {
                $data['warehouses_products'] = [];
                foreach ($data['warehouses'] as $ind => $arr) {
                    if (isset($data['warehouses'][$ind]['id'])) {
                        $data['warehouses_products'][$ind] = [
                            'wh' => '/v2/warehouses/' . $data['warehouses'][$ind]['id'],
                        ];
                        unset($data['warehouses'][$ind]['id']);
                        foreach ($data['warehouses'][$ind] as $key => $val) {
                            $data['warehouses_products'][$ind][$key] = $val;
                        }
                        // Populate Product IRI if it exists,  i.e. PUT operation.
                        if (!empty($id) && is_numeric($id)) {
                            $data['warehouses_products'][$ind]['product'] = '/v2/products/' . $id;
                        }
                    }
                }
                unset($data['warehouses']);
            }

            // Format ProductsSkus items.
            if (isset($data['skus']) && is_array($data['skus'])) {
                $data['products_skus'] = [];
                foreach ($data['skus'] as $ind => $arr) {
                    if (isset($data['skus'][$ind]['shop_id'])) {
                        $data['products_skus'][$ind] = [
                            'shop' => '/v2/shops/' . $data['skus'][$ind]['shop_id'],
                        ];
                        unset($data['skus'][$ind]['shop_id']);
                        foreach ($data['skus'][$ind] as $key => $val) {
                            $data['products_skus'][$ind][$key] = $val;
                        }
                    }
                }
                unset($data['skus']);
            }

        } elseif ($class == 'App\Entity\ProductsSkus') {

            // Create Products IRI from context uri.
            if (preg_match('/products\/([0-9]+)\/skus/', $context['uri'], $matches)) {
                $data['product'] = '/v2/products/' . $matches[1];
            }

            // Create Shop IRI.
            if (isset($data['shop_id'])) {
                $data['shop'] = '/v2/shops/' . $data['shop_id'];
            }

        } elseif ($class == 'App\Entity\Inbound') {
            // Set IRI for warehouse.
            if (isset($data['warehouse']) && isset($data['warehouse']['id'])) {
                $data['wh'] = '/v2/warehouses/' . $data['warehouse']['id'];
            }

            // Set IRI for Product.
            $quantity = 0;
            if (isset($data['products']) && is_array($data['products'])) {
                $data['attributes_products'] = [];
                $data['inbound_products'] = [];
                foreach ($data['products'] as $ind => $product) {
                    if (isset($product) && isset($product['id']) && is_numeric($product['id'])) {
                        // Add inbound_products fields as attributes,
                        //   since the inbound_products[] is being used for
                        //   the IRIs of the related Products.
                        $data['attributes_products'][$product['id']] = [];
                        $data['attributes_products'][$product['id']]['current_user'] = $this->currentUser;
                        if (isset($product['quantity'])) {
                            $data['attributes_products'][$product['id']]['quantity'] = $product['quantity'];
                        }
                        if (isset($product['inbound_cost'])) {
                            $data['attributes_products'][$product['id']]['inbound_cost'] = $product['inbound_cost'];
                        }
                        /* Set by the system
                        if (isset($product['processed'])) {
                            $data['attributes_products'][$product['id']]['processed'] = $product['processed'];
                        } */

                        // Add product to InboundProducts array.
                        $data['inbound_products'][] = '/v2/products/' . $product['id'];
                    }
                }
                unset($data['products']);
            }
        }

        // Add current user.
        $uid = $this->currentUser;
        if (is_int($uid) && intval($uid) > 0) {
            $data['user'] = '/v2/users/' . $uid;
        }

        return $this->normalizer->denormalize($data, $class, $format, $context);
    }

    public function supportsDenormalization($data, $type, ?string $format = null)
    {
        return $this->normalizer->supportsDenormalization($data, $type, $format);
    }

    public function normalize($object, ?string $format = null, array $context = [])
    {
        $class = $context['resource_class'];
        $class = strtolower(str_replace('App\\Entity\\', '', $class));

        // Entity specific GET response manipulation.
        switch ($class) {
            case 'estimate':
                // For Estimate, populate with query string parameters.
                $temp = explode('?', $context['request_uri']);
                if (isset($temp) && is_array($temp) && isset($temp[1])) {

                    $items = explode('&', $temp[1]);

                    foreach ($items as $item) {
                        if (empty($item) || strpos($item, '=') === FALSE) {
                            continue;
                        }

                        list($param, $value) = array_map('trim', explode('=', $item));

                        $param_method = str_replace('_', '', ucwords($param, '_'));

                        if (isset($object->$param) && method_exists($object, $method = 'set' . $param_method)) {
                            // Turn DG Codes into array
                            if ($param == 'dg_codes') {
                                $value = str_replace(['[', ']'], '', $value);
                                $value = array_map('trim', explode(',', $value));
                            }
                            call_user_func_array([$object, $method], [$value]);
                        }
                    }
                    // Set Services.
                    if (method_exists($object, 'setServicesArr')) {
                        $object->setServicesArr();
                    }
                }
                $result = $this->normalizer->normalize($object, $format, $context);
                break;
            case 'inbound':
                $result = $this->normalizer->normalize($object, $format, $context);
                // Sort product attributes alphabetically.
                if (isset($result['products'])) {
                    foreach ($result['products'] as $key => $product) {
                        ksort($result['products'][$key]);
                    }
                }
                // Change wh to warehouse.
                if (isset($result['wh'])) {
                    $result['warehouse'] = $result['wh'];
                    unset($result['wh']);
                }
                break;
            case 'orders':
                $result = $this->normalizer->normalize($object, $format, $context);
                if (is_array($result) && isset($result['shop_id']) && is_array($result['shop_id'])) {
                    $shop_id = isset($result['shop_id']['id']) ? $result['shop_id']['id'] : null;
                    $shop_name = isset($result['shop_id']['name']) ? $result['shop_id']['name'] : null;
                    $shop_order_id = null;
                    if (isset($result['shop_order_id'])) {
                        $shop_order_id = $result['shop_order_id'];
                        unset($result['shop_order_id']);
                    }
                    $shop_type = isset($result['shop_id']['type']) ? $result['shop_id']['type'] : null;

                    $result['shop'] = [
                        'id' => $shop_id,
                        'name' => $shop_name,
                        'order_id' => $shop_order_id,
                        'type' => $shop_type,
                    ];

                    unset($result['shop_id']);
                    \ksort($result);

                }

                break;
            case 'products':
                // Remove skus, warehouses from Collection GET.
                $result = $this->normalizer->normalize($object, $format, $context);
                if (isset($context['collection_operation_name'])) {
                    if (isset($result['skus'])) {
                        unset($result['skus']);
                    }
                    if (isset($result['warehouses'])) {
                        unset($result['warehouses']);
                    }
                } else {
                    if (isset($result['count'])) {
                        unset($result['count']);
                    }
                }
                break;
            case 'returns':
                $result = $this->normalizer->normalize($object, $format, $context);
                $new_images = [];
                if (isset($result['images']) && is_array($result['images'])) {
                    foreach ($result['images'] as $img) {
                        if (isset($img['name'])) {
                            $new_images[] = $img['name'];
                        }
                    }
                    $result['images'] = $new_images;
                }
                break;
            case 'shipments':
                $result = $this->normalizer->normalize($object, $format, $context);

                // Fix array within array issue for line_items output.
                if (isset($result['line_items'])) {
                    $fixed_line_items = [];
                    foreach ($result['line_items'] as $key => $arr) {
                        // Add nested line_items to fixed_line_items array.
                        if (!empty($result['line_items'][$key]) && isset($arr[0]) && !empty($arr[0])) {
                            $fixed_line_items[] = $arr[0];
                        }
                    }
                    $result['line_items'] = $fixed_line_items;
                }
                break;
            case 'shops':
                $result = $this->normalizer->normalize($object, $format, $context);

                // Group fields into settings object.
                $result['settings'] = [];

                if (isset($result['default_service'])) {
                    $result['settings']['default_service'] = $result['default_service'];
                    unset($result['default_service']);
                }
                if (isset($result['delay_processing'])) {
                    $result['settings']['delay_processing'] = $result['delay_processing'];
                    unset($result['delay_processing']);
                }
                if (isset($result['default_partial'])) {
                    $result['settings']['default_partial'] = $result['default_partial'];
                    unset($result['default_partial']);
                }

                break;
            case 'statements':
                $result = $this->normalizer->normalize($object, $format, $context);
                // Remove count variable, used by collections only.
                if (!isset($context['collection_operation_name'])) {
                    if (isset($result['count'])) {
                        unset($result['count']);
                    }
                }
                break;
            default:
                // Add wrapper class to GET output.
                $result = $this->normalizer->normalize($object, $format, $context);
        }

        // Set singular or plural for item or collection.
        $singular = $context['operation_type'] == 'item' || $context['collection_operation_name'] == 'post';
        if ($singular && !isset($context['api_attribute'])) {

            if (strlen($class) > 0 && substr($class, -1) == 's') {
                $class = substr($class, 0, -1);
            }

            if ($class == 'productssku') {
                $class = 'sku';
            }

            $object = new \stdClass();
            $object->$class = $result;
            if ($class == 'estimate') {
                print json_encode($object);
                exit;
            }

            $result = $object;
        }

        return $result;
    }

    public function supportsNormalization($data, ?string $format = null)
    {
        return $this->normalizer->supportsNormalization($data, $format);
    }

    /**
     * Returns Shipment id if valid, 0 if not valid.
     *
     * @param mixed $id Shipment ID or Tracking number.
     * @return int
     */
    public function validShipment($id)
    {
        $retval = 0;

        // Check id as tracking_number.
        $temp = $this->entityManager->getRepository(Shipments::class)->findOneBy(['tracking_number' => $id, 'user' => $this->currentUser]);
        if ($temp instanceof Shipments) {
            $retval = $temp->getId();
        } // Check id as id.
        elseif (is_numeric($id)) {
            $temp = $this->entityManager->getRepository(Shipments::class)->findOneBy(['id' => $id, 'user' => $this->currentUser]);
            if ($temp instanceof Shipments) {
                $retval = $id;
            }
        }

        return $retval;
    }
}
