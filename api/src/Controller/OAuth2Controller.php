<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class OAuth2Controller extends AbstractController
{
    /**
     * Handles user authorization request.
     */
    public function authorize()
    {
        $data = [];
        return new JsonResponse($data, 200);
    }

    /**
     * Returns authorization token.
     */
    public function getAccessToken(Request $request)
    {
        // Parse json request for id's and updated status.
        $json = $request->getContent();
        $arr = \json_decode($json, true);
        if (
            !isset($arr) ||
            !is_array($arr) ||
            !isset($arr['application_id']) ||
            !isset($arr['application_secret']) ||
            !isset($arr['nonce'])
        ) {
            return new JsonResponse(['error' => 'Invalid request.'], 400);
        }


        $data = [];
        if (isset($arr) && is_array($arr) && isset($arr['application_id'])) {
            return new JsonResponse(['error' => 'Invalid request.'], 400);
        }




        return new JsonResponse($data, 200);
    }

    /**
     * Deletes access token.
     *
     * @param string $token
     * @return Response - 200, Not found, or Error message
     */
    public function deleteAccessToken($token)
    {
        // Get current user.
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $uid = is_object($user) ? $user->getId() : 0;

        if(empty($uid)) {
            return new JsonResponse(['message' => 'Not found.'], 404);
        }

        $em = $this->getDoctrine()->getManager();
        $token = $em->find('App\Entity\Tokens', ['id' => $token]);

        if (is_object($token) && isset($token->user) && $uid == $token->user->getId()) {
            $em->remove($token);
            $em->flush();
            return new Response('', 200);
        }

        return new JsonResponse(['Error 1015' => 'Invalid access token.'], 403);
    }
}
