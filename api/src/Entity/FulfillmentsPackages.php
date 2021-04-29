<?php
// api/src/Entity/fulfillmentsPackages.php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM; 
use ApiPlatform\Core\Annotation\ApiResource;

/**
 * ... 
 *
 * @ApiResource
 */
class FulfillmentsPackages
{
    /**
     * @var int fulfillment_id
     *
     * @ORM\Column(type="bigint")
     */
    public $fulfillment_id;

    /**
     * @var int|null package_no
     *
     * @ORM\Column(nullable=true)
     */
    public $package_no;

    /**
     * @var int product_id
     *
     * @ORM\Column(type="int")
     */
    public $product_id;

    /**
     * @var int|null packaging_id
     *
     * @ORM\Column(nullable=true)
     */
    public $packaging_id;

    /**
     * @var int quantity
     *
     * @ORM\Column(type="smallint")
     */
    public $quantity;

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