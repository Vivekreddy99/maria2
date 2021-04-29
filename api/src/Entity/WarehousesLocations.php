<?php
// api/src/Entity/warehousesLocations.php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM; 
use ApiPlatform\Core\Annotation\ApiResource;

/**
 * ... 
 *
 * @ApiResource
 */
class WarehousesLocations
{
    /**
     * @var string wh_id
     *
     * @ORM\Column(type="char")
     */
    public $wh_id;

    /**
     * @var string location
     *
     * @ORM\Column(type="char")
     */
    public $location;

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
     * @var int wms_picked
     *
     * @ORM\Column(type="smallint")
     */
    public $wms_picked;

    /**
     * @var \DateTimeInterface created
     *
     * @ORM\Column(type="datetime")
     */
    public $created;

    /**
     * @var \DateTimeInterface updated
     *
     * @ORM\Column(type="datetime")
     */
    public $updated;

    public function getId(): ?int
    {
        return $this->id;
    }
}