<?php
// api/src/Entity/warehousesDeliveries.php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM; 
use ApiPlatform\Core\Annotation\ApiResource;

/**
 * ... 
 *
 * @ApiResource
 */
class WarehousesDeliveries
{
    /**
     * @var int id
     *
     * @ORM\Column(type="int")
     */
    private $id;

    /**
     * @var string wh_id
     *
     * @ORM\Column(type="char")
     */
    public $wh_id;

    /**
     * @var string tracking_number
     *
     * @ORM\Column(type="varchar")
     */
    public $tracking_number;

    /**
     * @var string courier
     *
     * @ORM\Column(type="varchar")
     */
    public $courier;

    /**
     * @var string received_by
     *
     * @ORM\Column(type="varchar")
     */
    public $received_by;

    /**
     * @var string contents
     *
     * @ORM\Column(type="varchar")
     */
    public $contents;

    /**
     * @var string units
     *
     * @ORM\Column(type="varchar")
     */
    public $units;

    /**
     * @var string description
     *
     * @ORM\Column(type="varchar")
     */
    public $description;

    /**
     * @var \DateTimeInterface created
     *
     * @ORM\Column(type="timestamp")
     */
    public $created;

    public function getId(): ?int
    {
        return $this->id;
    }
}