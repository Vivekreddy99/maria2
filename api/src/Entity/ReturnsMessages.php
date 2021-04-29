<?php
// api/src/Entity/returnsMessages.php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM; 
use ApiPlatform\Core\Annotation\ApiResource;

/**
 * ... 
 *
 * @ApiResource
 */
class ReturnsMessages
{
    /**
     * @var int id
     *
     * @ORM\Column(type="bigint")
     */
    private $id;

    /**
     * @var int return_id
     *
     * @ORM\Column(type="bigint")
     */
    public $return_id;

    /**
     * @var string author
     *
     * @ORM\Column(type="char")
     */
    public $author;

    /**
     * @var string body
     *
     * @ORM\Column(type="varchar")
     */
    public $body;

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