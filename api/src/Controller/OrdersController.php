<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class OrdersController extends BaseEntityController
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Deletes Order. Only orders that are Backordered, Exception, Holding, Processing, or Ready can be deleted.
     */
    public function delete($id)
    {
        // Check that the owner is the one making the request.
        if (!$this->isOwner('App\Entity\Orders', ['id' => $id])) {
            return $this->notFoundResponse;
        }

        $allowed_status = ['Backordered', 'Exception', 'Holding', 'Processing', 'Ready'];

        // Check that Order is one of: Backordered, Exception, Holding, Processing, or Ready
        if (isset($this->entity->status) && in_array($this->entity->status, $allowed_status)) {
            $this->em->remove($this->entity);
            $this->em->flush();
            return new Response(null, 204);
        } else {
            return new JsonResponse(['message' => "Only orders with status of Backordered, Exception, Holding, Processing, or Ready may be deleted."], 403);
        }
    }

    /**
     * Change status on multiple Orders.
     *
     * @param $id - Placeholder for 'status', not used.
     */
    public function patch($id, Request $request)
    {
        if ($id !== 'status') {
            return $this->notFoundResponse;
        }

        // Parse json request for id's and updated status.
        $json = $request->getContent();
        $arr = json_decode($json, true);
        $updated = false;

        if (isset($arr) && is_array($arr) && isset($arr['orders']))
        {
            $orders = [];
            foreach ($arr['orders'] as $key => $arr)
            {
                $id = isset($arr['id']) && is_numeric($arr['id']) ? $arr['id'] : null;
                if (!empty($id) && isset($arr['status']) && in_array($arr['status'], ['Processing', 'Holding'])) {

                    // Check that the owner is the one making the request.
                    if (!$this->isOwner('App\Entity\Orders', ['id' => $id])) {
                        $orders[]= ['id' => $id, 'status' => null];
                        continue;
                    }

                    // Update Order.
                    $this->entity->setStatus($arr['status']);
                    $this->em->flush();
                    $updated = true;
                    $orders[]= ['id' => $id, 'status' => $arr['status']];
                }
            }
        }

        if ($updated) {
            $content = (object) ['orders' => $orders];
            return new JsonResponse($content, 200);
        } else {
            return $this->notFoundResponse;
        }
    }
}
