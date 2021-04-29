<?php

namespace App\Entity\Helper;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Entity\EntryPoints;
use App\Entity\Manifests;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;
use ApiPlatform\Core\Annotation\ApiProperty;

/**
 * Helper class for Shipments
 */
class LineItem
{

    /**
     * @var int The entity Id
     */
    private $id;

    /**
     * @var string The country of origin or where the product was manufactured in ISO 3166-1 alpha-2 format.
     *
     * @Groups({"shipments:read", "shipments:write"})
     */
    public $coo;

    /**
     * @var string The 3-letter currency code. Default: "USD".
     *
     * @Groups({"shipments:read", "shipments:write"})
     */
    public $currency;

    /**
     * @var string A concise description of the line item in English. Max length: 64.
     *
     * @Groups({"shipments:read", "shipments:write"})
     */
    public $description;

    /**
     * @var string|null A code that identifies dangerous goods. Required if shipping lithium batteries, ORM-D, or other dangerous goods.
     *
     * @Groups({"shipments:read", "shipments:write"})
     */
    public $dg_code;

    /**
     * @var string|null The Harmonized System classification number for Customs clearance.
     *
     * @Groups({"shipments:read", "shipments:write"})
     */
    public $hts_code;

    /**
     * @var string|null A concise description of the line item in the entry point country's language. Max length: 64.
     *
     * @Groups({"shipments:read", "shipments:write"})
     */
    public $origin_description;

    /**
     * @var int The number of units of this line item. Max: 999.
     *
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
     * @var float  The total declared value of this line item.
     *
     * @Groups({"shipments:read", "shipments:write"})
     */
    public $value;

    /**
     * @var float The Weight as decimal
     *
     * @Groups({"shipments:read", "shipments:write"})
     */
    public $weight;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function offsetExists($offset)
    {
        if (isset($this->$offset)) {
            return true;
        } else {
            return false;
        }
    }

    public function offsetGet($offset)
    {
        return $this->$offset;
    }

    public function offsetSet($offset, $value)
    {
        // die("In Offset Set");
        // TODO: Implement offsetSet() method.
    }

    public function offsetUnset($offset)
    {
        // die("In Offset Unset");
        // TODO: Implement offsetUnset() method.
    }

    public function current()
    {
        // die("In Current");
        // TODO: Implement current() method.
        return $this;
    }

    public function next()
    {
        // die("In Next");
        // TODO: Implement next() method.
        return $this;
    }

    public function key()
    {
        // die("In Key");
        // TODO: Implement key() method.
        return 0;
    }

    public function valid()
    {
       //  die("In Valid");
        // TODO: Implement valid() method.
        return true;
    }

    /**
     * @return void|null
     *
     * Sequence is rewind, valid, key, current, next
     */
    public function rewind()
    {
        // die("In rewind.");
        // TODO: Implement rewind() method.
        return null;
    }

}
