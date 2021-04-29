<?php
// api/src/Entity/Warehouses.php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Entity\EntryPoints;
use App\Entity\Inbound;
use App\Entity\Shipments;
use App\Entity\Manifests;
use App\Entity\WarehousesProducts;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Validator\Exception;
use ApiPlatform\Core\Annotation\ApiFilter;
use App\Filter\OrderByFixedPropertyFilter;
use App\Filter\SearchFilterWithMapping;
use App\Filter\ExistsFilterWithMapping;

/**
 *
 * @ApiResource(
 *     collectionOperations={
 *         "get",
 *     },
 *     itemOperations={
 *         "get"
 *     },
 *     normalizationContext={"groups"={"warehouses:read"}},
 *     denormalizationContext={"groups"={"warehouses:write"}},
 *     attributes={"route_prefix"="/v2"}
 * )
 * @ORM\Entity
 * @ApiFilter(OrderByFixedPropertyFilter::class, arguments={"orderParameterName"="sort"}, properties={"id": "desc"})
 * @ApiResource(attributes={"pagination_items_per_page"=50})
 */
class Warehouses
{
    /** TODO: see if this is needed or should be created from more granular fields.
     * @var string Address
     *
     * ORM\Column(name="address", type="string", length=255)
     * @Groups({"warehouses:read", "inbound:read"})
     * Assert\Length(max=255)
     */
    public $address;

    /**
     * @var decimal The Base Fee
     *
     * @ORM\Column(name="base_fee", type="decimal", precision=4, scale=2)
     * @Groups({"warehouses:read"})
     * @Assert\LessThan(100)
     */
    public $base_fee;

    /**
     * @var int BoxC owned flag
     *
     * @ORM\Column(name="boxc_owned", type="boolean", options={"unsigned"=true, "default"=0})
     */
    public $boxc_owned = 0;

    /**
     * @var string The city of the warehouse. Set by the system.
     *
     * @ORM\Column(name="city", type="string", length=40)
     * @Groups({"warehouses:read"})
     * @Assert\Length(max=40)
     */
    public $city;

    /**
     * @var string The country code in ISO 3166-1 alpha-2 format. Set by the system.
     *
     * @ORM\Column(name="country", type="string", length=2, options={"fixed" = true})
     * @Groups({"warehouses:read", "warehouses:write"})
     * @Assert\Choice(callback="getCountryIsoCodes", message="The country of origin or where the product was manufactured must be in the ISO 3166-1 alpha-2 format.")
     */
    public $country;

    /**
     * @ORM\ManyToOne(targetEntity=EntryPoints::class)
     * @Groups({"warehouses:write"})
     * @SerializedName("entry_point")
     */
    public $ep;

    /**
     * @var string Non-IRI Entry Point value
     *
     * @Groups({"warehouses:read"})
     * @SerializedName("entry_point")
     */
    public $ep_plain;

    /**
     * @var string The entity Id
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(name="id", type="string", length=8, options={"fixed" = true})
     * @Groups({"warehouses:read", "products:read", "products:write", "warehouses_products:write", "inbound:read",})
     */
    public $id;

    /**
     * @ORM\OneToMany(targetEntity=WarehousesProducts::class, mappedBy="wh")
     */
    public $warehouses_products;

    /**
     * @ORM\OneToMany(targetEntity=Inbound::class, mappedBy="wh")
     */
    public $inbound;

    /**
     * @var decimal bulk_carton_fee
     *
     * @ORM\Column(type="decimal")
     * /
    public $bulk_carton_fee;

    /**
     * @var decimal bulk_peritem_fee
     *
     * @ORM\Column(type="decimal")
     * /
    public $bulk_peritem_fee;

    /**
     * @var decimal packing_slip_fee
     *
     * @ORM\Column(type="decimal")
     * /
    public $packing_slip_fee;
    */

    /**
     * @var string The language product descriptions should be in for the warehouse. Set by the system.
     *
     * @ORM\Column(name="language", type="string", length=16)
     * @Groups({"warehouses:read", "inbound:read"})
     * @Assert\Length(max=16)
     */
    public $language;

    /**
     * @var string The language code product descriptions should be in for the warehouse. Set by the system.
     *
     * @ORM\Column(name="language_code", type="string", length=2, options={"fixed" = true})
     * @Groups({"warehouses:read", "inbound:read"})
     */
    public $language_code;

    /**
     * @var string The name of the warehouse. Set by the system.
     *
     * @ORM\Column(name="name", type="string", length=64)
     * @Groups({"warehouses:read", "inbound:read"})
     * @Assert\Length(max=64)
     */
    public $name;

    /**
     * @var string Additional information about the warehouse. Set by the system.
     *
     * @ORM\Column(name="notes", type="string", length=255)
     * @Groups({"warehouses:read"})
     * @Assert\Length(max=255)
     */
    public $notes;

    /**
     * @var decimal The cost to fulfill each unit after the first in a fulfillment. Set by the system.
     *
     * @ORM\Column(name="peritem_fee", type="decimal", precision=3, scale=2)
     * @Groups({"warehouses:read"})
     * @SerializedName("per_unit_fee")
     * @Assert\LessThan(10)
     */
    public $per_unit_fee;

    /**
     * @var string The postal code. Set by the system.
     *
     * @ORM\Column(name="postal_code", type="string", length=10, nullable=true)
     * @Groups({"warehouses:read"})
     */
    public $postal_code;

    /**
     * @var string The province or state code. Set by the system.
     *
     * @ORM\Column(name="province", type="string", length=40, nullable=true)
     * @Groups({"warehouses:read"})
     */
    public $province;

    /**
     * @var string The street address for the warehouse. Set by the system.
     *
     * @ORM\Column(name="street1", type="string", length=40)
     * @Groups({"warehouses:read"})
     */
    public $street1;

    /**
     * @var string|null The street address continued for the warehouse. Set by the system.
     *
     * @ORM\Column(name="street2", type="string", length=40, nullable=true)
     * @Groups({"warehouses:read"})
     */
    public $street2;

    // Placeholder for storage json string field, if needed.

    /**
     * @var decimal The price for storing one unit of a small sized product.
     *
     * @ORM\Column(name="storage_sm_price", type="decimal", precision=3, scale=2, options={"default"=0.0})
     * @Groups({"warehouses:read"})
     * @Assert\LessThan(10)
     */
    public $storage_sm_price;

    /**
     * @var decimal The price for storing one unit of a medium sized product.
     *
     * @ORM\Column(name="storage_md_price", type="decimal", precision=3, scale=2, options={"default"=0.0})
     * @Groups({"warehouses:read"})
     * @Assert\LessThan(10)
     */
    public $storage_md_price;

    /**
     * @var decimal The price per cubic meter for storing a large sized product.
     *
     * @ORM\Column(name="storage_lg_price", type="decimal", precision=4, scale=2, options={"default"=0.0})
     * @Groups({"warehouses:read"})
     * @Assert\LessThan(100)
     */
    public $storage_lg_price;

    /**
     * @var int The number of days a product is warehoused before the storage fees take effect.
     *
     * @ORM\Column(name="storage_free_days", type="smallint", options={"unsigned":true, "default": 30})
     * @Groups({"warehouses:read"})
     * @Assert\LessThan(128)
     */
    public $storage_free_days;

    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @return string|null
     *
     * Workaround to avoid returning the default IRI style id.
     */
    public function getEpPlain(): ?string {

        if (isset($this->ep)) {
            $this->ep_plain = $this->ep->getId();
        }

        return $this->ep_plain;
    }

    public function getAddress() {
        $addr = '';
        if (!empty($this->street1)) {
            $addr .= $this->street1 . PHP_EOL;
        }
        if (!empty($this->street2)) {
            $addr .= $this->street2 . PHP_EOL;
        }
        if (!empty($this->city)) {
            $addr .= $this->city . ' ';
        }
        if (!empty($this->province)) {
            $addr .= $this->province . PHP_EOL;
        }
        if (!empty($this->postal_code)) {
            $addr .= $this->postal_code . PHP_EOL;
        }
        if (!empty($this->country)) {
            $addr .= $this->country;
        }
        return $addr;
    }

    public function setAddress($address)
    {
        $this->address = $address;
    }

    public static function getCountryIsoCodes() {
        return Shipments::getCountryIsoCodes();
    }
}
