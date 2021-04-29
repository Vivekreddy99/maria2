<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ReturnsController extends BaseEntityController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Sets Return status to Verified only if it currently has a Processed status.
     */
    public function verify($id)
    {
        // Check that the owner is the one making the request.
        if (!$this->isOwner('App\Entity\Returns', ['id' => $id])) {
            return $this->notFoundResponse;
        }

        // Check that the Return's status is Processed.
        if (isset($this->entity->status) && $this->entity->status == 'Processed') {
            // Update Return's status.
            $this->entity->status = 'Verifying';
            $this->em->flush();

            // Return Return object as Json response.
            $data = $this->entity->jsonSerialize();
            return new JsonResponse($data, 200);

        } else {
            // Send error since the Return's status was not Processed.
            return new JsonResponse(['message' => 'Only Returns with a Status of Processed may be verified'], 403);
        }

    }
}
