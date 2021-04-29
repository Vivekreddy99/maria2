<?php

namespace App\Entity\Helper;

use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Helper class for Shipments
 */
class Package
{
    /**
     * @var string The barcode for this package. Set by the system.
     *
     * @Groups({"shipments:read", "shipments:write"})
     */
    public $barcode;

    /**
     * @var float The greater of actual or volumetric weight in kg. Set by the system.
     *
     * @Groups({"shipments:read", "shipments:write"})
     */
    public $chargeable_weight;

    /**
     * @var float|null The height of this packge in cm. Default is 1 for eCommerce. Required for Payload.
     *
     * @Groups({"shipments:read", "shipments:write"})
     */
    public $height;

    /**
     * @var float|null The length of this packge in cm. Default is 15 for eCommerce. Required for Payload.
     *
     * @Groups({"shipments:read", "shipments:write"})
     */
    public $length;

    /**
     * @var \DateTime The date and time this package was processed. Default is null. Set by the system.
     *
     * @Groups({"shipments:read", "shipments:write"})
     */
    public $processed = null;

    /**
     * @var float|null The actual weight of the package in kg.
     *
     * @Groups({"shipments:read", "shipments:write"})
     */
    public $weight;

    /**
     * @var float The width of this packge in cm. Default is 10 for eCommerce. Required for Payload.
     *
     * @Groups({"shipments:read", "shipments:write"})
     */
    public $width;

    /**
     * @param string|null $route
     *
     * Based on route and comparison of volumetric weight vs. weight determines
     *   the greater value and assigns it to the chargeable_weight parameter.
     */
    public function calculateChargeableWeight($route = null) {
        // TODO: handle updating of chargeable weight dependent on route.
        if (empty($this->chargeable_weight)) {
            $this->chargeable_weight = $this->weight;
        }
    }

    public function setHeight(?float $height)
    {
        $this->height = $height;
    }

    public function setLength(?float $length)
    {
        $this->length = $length;
    }

    public function setWidth(?float $width)
    {
        $this->width = $width;
    }

}
