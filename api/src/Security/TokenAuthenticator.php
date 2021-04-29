<?php
// src/Security/TokenAuthenticator.php
namespace App\Security;

use App\Entity\Users;
use App\Entity\Client;
use App\Entity\AccessToken;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;

class TokenAuthenticator extends AbstractGuardAuthenticator
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Called on every request to decide if this authenticator should be
     * used for the request. Returning `false` will cause this authenticator
     * to be skipped.
     */
    public function supports(Request $request)
    {
        $retval = false;

        // return $request->headers->has('X-AUTH-TOKEN');
        if ($request->headers->has('Authorization')) {
            $value = $request->headers->get('Authorization');
            $retval = strpos($value, 'Bearer') !== FALSE;
        }

        return $retval;
    }

    /**
     * Called on every request. Return whatever credentials you want to
     * be passed to getUser() as $credentials.
     */
    public function getCredentials(Request $request)
    {
        $credentials = null;

        // return $request->headers->get('X-AUTH-TOKEN');
        if ($request->headers->has('Authorization')) {
            $value = $request->headers->get('Authorization');
            $credentials = preg_replace('/Bearer[ ]+/', '', $value);
        }

        return $credentials;
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        if (null === $credentials) {
            // The token header was empty, authentication fails with HTTP Status
            // Code 401 "Unauthorized"
            return null;
        }

        // if a User is returned, checkCredentials() is called
        // return $this->em->getRepository(Users::class)
        //    ->findOneBy(['apiToken' => $credentials]);

        // Find matching token from access_token table
        $token_obj = $this->em->getRepository(AccessToken::class)
            ->findOneBy(['token' => $credentials]);

        if (!($token_obj instanceof AccessToken)) {
            return null;
        }

        // Get User id and check that it is an integer.
        $user = $token_obj->getUser();

        // Check that the token has not expired.
        $expired = $token_obj->hasExpired();

        // Check that token scope applies.
        $client = $token_obj->getClient();
        $scope = $token_obj->getScope();

        if (!($user instanceof Users) || $expired || !($client instanceof Client)) {
            return null;
        }

        $scopes = $client->getAllowedGrantTypes();
        if (!\is_array($scopes) || !in_array($scope, $scopes)) {
            return null;
        }

        // Return validated User.
        return $user;
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        // Check credentials - e.g. make sure the password is valid.
        // In case of an API token, no credential check is needed.

        // Return `true` to cause authentication success
        return true;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        // on success, let the request continue
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        $data = [
             // you may want to customize or obfuscate the message first
            'message' => strtr($exception->getMessageKey(), $exception->getMessageData())

             // or to translate this message
             // $this->translator->trans($exception->getMessageKey(), $exception->getMessageData())
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Called when authentication is needed, but it's not sent
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        $data = [
             // you might translate this message
            'message' => 'Authentication Required'
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

    public function supportsRememberMe()
    {
        return false;
    }
}

