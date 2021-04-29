<?php
// api/src/Entity/stocktakes.php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM; 
use ApiPlatform\Core\Annotation\ApiResource;

/**
 * ... 
 *
 * @ApiResource
 */
class Stocktakes
{
    /**
     * @var int id
     *
     * @ORM\Column(type="bigint")
     */
    private $id;

    /**
     * @var string wh_id
     *
     * @ORM\Column(type="char")
     */
    public $wh_id;

    /**
     * @var int product_id
     *
     * @ORM\Column(type="int")
     */
    public $product_id;

    /**
     * @var int quantity
     *
     * @ORM\Column(type="smallint")
     */
    public $quantity;

    /**
     * @var int code
     *
     * @ORM\Column(type="tinyint")
     */
    public $code;

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