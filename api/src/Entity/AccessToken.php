<?php
// src/App/Entity/AccessToken.php

namespace App\Entity;

use FOS\OAuthServerBundle\Entity\AccessToken as BaseAccessToken;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Users;

/**
 * @ORM\Entity
 */
class AccessToken extends BaseAccessToken
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Client")
     * @ORM\JoinColumn(nullable=false)
     */
    protected $client;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Users")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $user;

    /**
     * @return int|null
     *
     * Returns user id if present or null.
     */
    public function getUserId() {
        if (isset($this->user) && $this->user instanceof Users) {
            return $this->user->getId();
        } else {
            return null;
        }

    }
}
