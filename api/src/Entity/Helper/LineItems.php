<?php

namespace App\Entity\Helper;

use App\Entity\Shipments;
use ArrayAccess;
use App\Entity\Helper\LineItem;
use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\SerializedName;
use ApiPlatform\Core\Annotation\ApiProperty;

/**
 * LineItem Helper class
 */

class LineItems implements \Iterator, ArrayAccess
{
    /**
     * @var string The country of origin or where the product was manufactured in ISO 3166-1 alpha-2 format.
     *
     * @Assert\Choice(callback="getCountryIsoCodes", message="The country of origin or where the product was manufactured must be in the ISO 3166-1 alpha-2 format."),
     * @Groups({"shipments:read", "shipments:write"})
     */
    public $coo;

    /**
     * @var string The 3-letter currency code. Default: "USD".
     *
     * @Assert\Length(max=3, maxMessage="Currency abbreviation has too many characters.")
     * @Groups({"shipments:read", "shipments:write"})
     */
    public $currency;

    /**
     * @var string A concise description of the line item in English. Max length: 64.
     *
     * @Assert\NotBlank
     * @Assert\Type("string")
     * @Assert\Length(max=64)
     * @Groups({"shipments:read", "shipments:write"})
     */
    public $description;

    /**
     * @var string|null A concise description of the line item in the entry point country's language. Max length: 64.
     *
     * @Groups({"shipments:read", "shipments:write"})
     */
    public $origin_description;

    /**
     * @var int The number of units of this line item. Max: 999.
     *
     * @Assert\Type("integer")
     * @Assert\Positive
     * @Assert\Length(max=3, maxMessage="Quantity must be under 1000")
     * @Groups({"shipments:read", "shipments:write"})
     */
    public $quantity;

    /**
     * @var float The tax for this line item. Default: 0.
     *
     * @Groups({"shipments:read", "shipments:write"})
     */
    public $tax;

    /**
     * @var float The total declared value of this line item..
     *
     * @Assert\NotBlank
     * @Assert\Type("float")
     * @Assert\Positive
     * @Assert\Length(max=12, maxMessage="Value is too high.")
     * @Groups({"shipments:read", "shipments:write"})
     */
    public $value;

    /**
     * @var float The weight for this line item. Default: 0.
     *
     * @Assert\NotBlank
     * @Assert\Type("float")
     * @Assert\Positive
     * @Assert\Length(max=12, maxMessage="Weight is too high.")
     * @Groups({"shipments:read", "shipments:write"})
     */
    public $weight;

    /**
     * @var array Testing
     *
     */
    private $container = [];

    private $index = 0;

    public function getCoo() {
        return $this->coo;
    }

    public function setCoo($coo) {
        $this->coo = $coo;
    }

    public function getTax() {
        return $this->tax;
    }

    public function setTax($tax) {
        $this->tax = $tax;
    }

    public function getContainer() {
        return $this->container;
    }

    public function addLineItem(LineItem $line_item) {
        $this->container[] = $line_item;
    }

    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->container[] = $value;
        } else {
            $this->container[$offset] = $value;
        }
    }

    public function offsetExists($offset)
    {
        return isset($this->container[$offset]);
    }

    public function offsetUnset($offset)
    {
        unset($this->container[$offset]);
    }

    public function offsetGet($offset)
    {
        return isset($this->container[$offset]) ? $this->container[$offset] : null;
    }

    public function current()
    {
        return $this->container[$this->index];
    }

    public function next()
    {
        $this->index++;
    }

    public function key()
    {
        return $this->index;
    }

    public function valid()
    {
        return isset($this->container[$this->key()]);
    }

    public function rewind()
    {
        $this->index = 0;
    }

    public static function getCountryIsoCodes() {
        return Shipments::getCountryIsoCodes();
    }
}
