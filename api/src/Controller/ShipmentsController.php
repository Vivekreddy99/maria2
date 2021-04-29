<?php
// src/Controller/ShipmentsController.php

namespace App\Controller;

use App\Entity\Fulfillments;
use App\Entity\Shipments;
use Symfony\Component\HttpFoundation\Response;
use function intval;

class ShipmentsController extends BaseEntityController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function delete($id)
    {
        // Check that the owner is the one making the Delete request.
        if (!$this->isOwner('App\Entity\Shipments', $id)) {
            return $this->notFoundResponse;
        }

        $test = $this->entity->test;

        // TODO: Add check for Labels.
        $hasLabel = false;

        // Check for related fulfillments, i.e. processed.
        // TODO: determine if checking events is a better approach.
        $processed = false;
        $em2 = $this->getDoctrine()->getManager();
        $fulfillment = $em2->getRepository(Fulfillments::class)->findOneBy(['shipment' => $id]);
        if ($fulfillment instanceof Fulfillments) {
            $processed = true;
        }

        $overpacked = $this->entity->getOverpackId() !== null;

        // Only test shipments and shipments without labels can be deleted.
        if (intval($test) > 0 && !$hasLabel) {
            $this->em->flush();
            return new Response(null, 204);
        } // Other shipments will be canceled if they're not processed or overpacked.
        elseif (!$overpacked && !$processed) {
            $this->entity->canceled = 1;
            $this->em->persist($this->entity);
            $this->em->flush();

            if ($hasLabel) {
                $content = 'This shipment will be canceled instead because it has a label.';
            } else {
                $content = 'This shipment will be canceled.';
            }
            return new Response($content, 200);

        } else {
            $content = 'Only test shipments and shipments without labels can be deleted.';
            return new Response($content, 400);
        }
    }
}
