<?php
// src/Controller/BaseEntityController.php

/**
 * This file should be used as a base class for Entities.
 *   It ensures that the current user, identified by Token
 *   is also the Entity owner.
 */

namespace App\Controller;

use App\Entity\Users;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

class BaseEntityController extends AbstractController
{
    protected $notFoundResponse;
    protected $em;
    protected $entity;

    public function __construct() {
        $this->notFoundResponse = new JsonResponse(['message' => 'Not found.'], 404);
    }

    /**
     * Verifies owner is current user and sets entity manager and entity instance.
     *
     * @param string $entity_name
     * @param array $id_arr
     * @return bool
     */
    public function isOwner($entity_name, $id_arr)
    {
        $retval = false;

        // Get owner.
        $this->em = $this->getDoctrine()->getManager();
        $this->entity = $this->em->find($entity_name, $id_arr);

        if ($this->entity instanceof $entity_name) {

            $owner = null;
            if (method_exists($this->entity, 'getUser')) {
                $owner = $this->entity->getUser()->getId();
            }
            elseif ($entity_name == 'App\Entity\Orders') // Use related Shop owner for Orders.
            {
                if (isset($this->entity->shop) && method_exists($this->entity->shop, 'getUser')) {
                    $owner = $this->entity->shop->getUser()->getId();
                }
            }
            elseif ($entity_name == 'App\Entity\ProductsSkus') // Check owners for composite keys.
            {
                $product_owner = null;
                if (isset($this->entity->product) && method_exists($this->entity->product, 'getUser')) {
                    $product_owner = $this->entity->product->getUser()->getId();
                }

                $shop_owner = null;
                if (isset($this->entity->shop) && method_exists($this->entity->shop, 'getUser')) {
                    $shop_owner = $this->entity->shop->getUser()->getId();
                }

                $owner = $product_owner == $shop_owner ? $product_owner : null;
            }

            // Get current user.
            $user = $this->get('security.token_storage')->getToken()->getUser();

            $id = ($user instanceof Users) ? $user->getId() : 0;

            if(!empty($id) && $id == $owner) {
                $retval = true;
            }
        }

        return $retval;
    }
}
