<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\Users;

class UserController extends AbstractController
{
    /**
     * Retrieves Account information for current user.
     */
    public function account()
    {
        // Get current user.
        $user = $this->get('security.token_storage')->getToken()->getUser();

        $id = ($user instanceof Users) ? $user->getId() : 0;
        if(empty($id)) {
            return new JsonResponse(['message' => 'Not found.'], 404);
        }

        // Create JSON response
        // {"account":{"balance":99698.94,"credit":0,"email":"test@gmail.com","first_name":"John","id":5514,"last_name":"Doe","max_requests":40,"requests":6}}
        $data = ['account' => [
            'balance' => $user->getBalance(),
            'credit' => $user->getCreditLimit(),
            'email' => $user->getEmail(),
            'first_name' => $user->getFname(),
            'id' => $id,
            'last_name' => $user->getLname(),
            'max_requests' => $user->getMaxRequests(),
            'requests' => $user->getRequests(),
        ]];

        return new JsonResponse($data, 200);

    }

}
