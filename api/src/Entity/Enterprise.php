<?php
// api/src/Entity/enterprise.php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM; 
use ApiPlatform\Core\Annotation\ApiResource;

/**
 * ... 
 *
 * @ApiResource
 */
class Enterprise
{
    /**
     * @var int id
     *
     * @ORM\Column(type="int")
     */
    private $id;

    /**
     * @var int user_id
     *
     * @ORM\Column(type="int")
     */
    public $user_id;

    /**
     * @var string|null customer_ref
     *
     * @ORM\Column(nullable=true)
     */
    public $customer_ref;

    /**
     * @var int accepted
     *
     * @ORM\Column(type="smallint")
     */
    public $accepted;

    /**
     * @var int rejected
     *
     * @ORM\Column(type="smallint")
     */
    public $rejected;

    /**
     * @var string version
     *
     * @ORM\Column(type="varchar")
     */
    public $version;

    /**
     * @var \DateTimeInterface created
     *
     * @ORM\Column(type="datetime")
     */
    public $created;

    public function getId(): ?int
    {
        return $this->id;
    }
}