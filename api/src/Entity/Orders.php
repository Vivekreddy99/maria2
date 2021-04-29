<?php
// api/src/Entity/orders.php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Entity\EntryPoints;
use App\Entity\Helper\Consignee;
use App\Entity\Helper\Consignor;
use App\Entity\Helper\ReturnAddress;
use App\Entity\Helper\ShippingAddress;
use App\Entity\Products;
use App\Entity\Shipments;
use App\Entity\Manifests;
use App\Entity\Warehouses;
use App\Entity\LineItems;
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
use App\Filter\DateInclusiveFilter;
use App\Filter\SearchFilterWithMapping;
use App\Filter\ExistsFilterWithMapping;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @ApiResource(
 *     collectionOperations={
 *         "get"={
 *             "normalization_context"={"groups"={"orders:collectionread", "orders:read"}}
 *         },
 *         "post"={
 *             "denormalization_context"={"skip_null_values"=false}
 *         }
 *     },
 *     itemOperations={
 *         "get",
 *         "delete",
 *         "put",
 *         "patch"
 *     },
 *     normalizationContext={"groups"={"orders:read"}},
 *     denormalizationContext={"groups"={"orders:write"}},
 *     attributes={"route_prefix"="/v2"}
 * )
 * @ORM\Entity
 * @ApiFilter(OrderByFixedPropertyFilter::class, arguments={"orderParameterName"="sort"}, properties={"id": "desc"})
 * @ApiFilter(DateInclusiveFilter::class, properties={"created"})
 * @ApiResource(attributes={"pagination_items_per_page"=50})
 * @ApiFilter(SearchFilter::class, properties={"status": "exact", "shop.id":"exact"})
 * @ApiFilter(SearchFilterWithMapping::class, properties={"to_name": "partial", "line_item_list.product.id": "exact", "shop_order_id": "exact"})
 */
class Orders
{
    /**
     * @var Consignee The Consignee
     *
     * @Assert\Valid
     * @Groups({"orders:read", "orders:write"})
     */
    public $consignee;

    /**
     * @var string The Consignee Name
     *
     * @ORM\Column(name="cne_name", type="string", length=40)
     * @Groups({"orders:write"})
     * @Assert\Length(max=40)
     */
    public $cne_name;

    /**
     * @var string|null The Consignee Phone
     *
     * @ORM\Column(name="cne_phone", type="string", length=20, nullable=true)
     * @Groups({"orders:write"})
     * @Assert\Length(max=20)
     */
    private $cne_phone;

    /**
     * @var string|null The Consignee Email
     *
     * @ORM\Column(name="cne_email", type="string", length=20, nullable=true)
     * @Groups({"orders:write"})
     * @Assert\Length(max=20)
     * @Assert\Email
     */
    public $cne_email;

    /**
     * @var string|null The Consignee Id
     *
     * @ORM\Column(name="cne_id", type="string", length=20, nullable=true)
     * @Groups({"orders:write"})
     * @Assert\Length(max=20)
     */
    public $cne_id;

    /**
     * @var string The Consignee Street 1
     *
     * @ORM\Column(name="cne_street1", type="string", length=40, nullable=true)
     * @Groups({"orders:write"})
     * @Assert\Length(max=40)
     */
    public $cne_street1;

    /**
     * @var string|null The Consignee Street 2
     *
     * @ORM\Column(name="cne_street2", type="string", length=40, nullable=true)
     * @Groups({"orders:write"})
     * @Assert\Length(max=40)
     */
    public $cne_street2;

    /**
     * @var string|null cne_city
     *
     * @ORM\Column(name="cne_city", type="string", length=40, nullable=true)
     * @Groups({"orders:write"})
     * @Assert\Length(max=40)
     */
    public $cne_city;

    /**
     * @var string The Consignee Province
     *
     * @ORM\Column(name="cne_province", type="string", length=40, nullable=true)
     * @Groups({"orders:write"})
     * @Assert\Length(max=40)
     */
    public $cne_province;

    /**
     * @var string The Consignee Postal Code
     *
     * @ORM\Column(name="cne_postal_code", type="string", length=10, nullable=true)
     * @Groups({"orders:write"})
     * @Assert\Length(max=10)
     */
    public $cne_postal_code;

    /**
     * @var string The Consignee Country
     *
     * @ORM\Column(name="cne_country", type="string", length=2, options={"fixed" = true})
     * @Groups({"orders:write"})
     * @Assert\Choice(callback="getCountryIsoCodes", message="The country of origin or where the product was manufactured must be in the ISO 3166-1 alpha-2 format.")
     */
    public $cne_country;

    /**
     * @var Consignor The Consignor
     *
     * @Assert\Valid
     * @Groups({"orders:read", "orders:write"})
     */
    public $consignor;

    /**
     * @var string The Consignor Name
     *
     * @ORM\Column(name="con_name", type="string", length=40)
     * @Groups({"orders:write"})
     * @Assert\Length(max=40)
     */
    public $con_name;

    /**
     * @var string|null The Consignor Phone
     *
     * @ORM\Column(name="con_phone", type="string", length=20, nullable=true)
     * @Groups({"orders:write"})
     * @Assert\Length(max=20)
     */
    public $con_phone;

    /**
     * @var string|null The Consignor Id
     *
     * @ORM\Column(name="con_id", type="string", length=20, nullable=true)
     * @Groups({"orders:write"})
     * @Assert\Length(max=20)
     */
    public $con_id;

    /**
     * @var string The Consignor Street 1
     *
     * @ORM\Column(name="con_street1", type="string", length=40)
     * @Groups({"orders:write"})
     * @Assert\Length(max=40)
     */
    public $con_street1;

    /**
     * @var string|null The Consignor Street 2
     *
     * @ORM\Column(name="con_street2", type="string", length=40, nullable=true)
     * @Groups({"orders:write"})
     * @Assert\Length(max=40)
     */
    public $con_street2;

    /**
     * @var string The Consignor City
     *
     * @ORM\Column(name="con_city", type="string", length=40, nullable=true)
     * @Groups({"orders:write"})
     * @Assert\Length(max=40)
     */
    public $con_city;

    /**
     * @var string The Consignor Province
     *
     * @ORM\Column(name="con_province", type="string", length=40, nullable=true)
     * @Groups({"orders:write"})
     * @Assert\Length(max=40)
     */
    public $con_province;

    /**
     * @var string The Consignor Postal Code
     *
     * @ORM\Column(name="con_postal_code", type="string", length=10, nullable=true)
     * @Groups({"orders:write"})
     * @Assert\Length(max=10)
     */
    public $con_postal_code;

    /**
     * @var string The Consignor Country
     *
     * @ORM\Column(name="con_country", type="string", length=2, options={"fixed" = true})
     * @Groups({"orders:write"})
     * @Assert\Choice(callback="getCountryIsoCodes", message="The consignor country must be in the ISO 3166-1 alpha-2 format.")
     */
    public $con_country;

    /**
     * @var \DateTimeInterface Creation Date
     *
     * @ORM\Column(name="created", type="datetime", options={"default": "CURRENT_TIMESTAMP"})
     * @Groups({"orders:read", "orders:write"})
     */
    public $created;

    /**
     * @ORM\OneToMany(targetEntity=Fulfillments::class, mappedBy="order", cascade={"persist", "remove"})
     * @Groups({"orders:read"})
     */
    public $fulfillments;

    /**
     * @var string|null Gift message.
     *
     * @ORM\Column(name="gift_msg", type="string", length=128, nullable=true)
     * @Groups({"orders:read", "orders:write"})
     * @Assert\Length(max=128)
     */
    public $gift_msg;

    /**
     * @var int The entity Id
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(name="id", type="bigint", options={"unsigned"=true})
     * @Groups({"orders:read", "orders:write", "line_items:write"})
     */
    public $id;

    /**
     * @var \DateTimeInterface Last Check date.
     *
     * @ORM\Column(name="last_check", type="datetime", options={"default": "CURRENT_TIMESTAMP"})
     * @Groups({"orders:read"})
     */
    public $last_check;

    /**
     * @var array
     * @Groups({"orders:write"})
     */
    public $attributes_line_items;

    /**
     * @ORM\OneToMany(targetEntity=App\Entity\LineItems::class, mappedBy="order", cascade={"persist", "remove"})
     * @Groups({"orders:read", "orders:write", "line_items:read", "line_items:write", "products:read", "products:write"})
     * @SerializedName("line_items")
     */
    public $line_item_list;

    /**
     * @var \DateTimeInterface Next Check date.
     *
     * @ORM\Column(name="next_check", type="datetime", options={"default": "CURRENT_TIMESTAMP"})
     * @Groups({"orders:read"})
     */
    public $next_check;

    /**
     * @var int Override flag.
     *
     * @ORM\Column(name="override", type="boolean", options={"unsigned"=true, "default"=0})
     * @Groups({"orders:read", "orders:write"})
     */
    public $override = 0;

    /**
     * @var int Pack Slip flag.
     *
     * @ORM\Column(name="pack_slip", type="boolean", options={"unsigned"=true, "default"=0})
     * @Groups({"orders:read", "orders:write"})
     */
    public $pack_slip = 0;

    /**
     * @var int Partial Ok flag
     *
     * @ORM\Column(name="partial_ok", type="boolean", options={"unsigned"=true, "default"=0})
     * @Groups({"orders:read", "orders:write"})
     * @SerializedName("partial_fulfillment")
     */
    public $partial_ok = 0;

    /**
     * @var \DateTimeInterface Placed At date.
     *
     * @ORM\Column(name="placed_at", type="datetime", options={"default": "CURRENT_TIMESTAMP"})
     * @Groups({"orders:read"})
     */
    public $placed_at;

    /**
     * @var int Sets the priority for this order in your fulfillment queue in case some orders should be fulfilled before others. A higher number indicates a higher priority. Min: 1. Max: 10. Default: 5.
     *
     * @ORM\Column(name="width", type="smallint", options={"unsigned":true, "default": 5})
     * @Groups({"orders:read", "orders:write"})
     * @Assert\GreaterThan(0)
     * @Assert\LessThan(11)
     */
    public $priority = 5;

    /**
     * @var ReturnAddress|null The From Address
     *
     * @Assert\Valid
     * @Groups({"orders:read", "orders:write"})
     */
    public $return_address;

    /**
     * @var string The From Address Name
     *
     * @ORM\Column(name="from_name", type="string", length=40, nullable=true)
     * @Groups({"orders:write"})
     * @Assert\Length(max=40)
     */
    public $from_name;

    /**
     * @var string The From Address Street 1
     *
     * @ORM\Column(name="from_street1", type="string", length=40, nullable=true)
     * @Groups({"orders:write"})
     * @Assert\Length(max=40)
     */
    public $from_street1;

    /**
     * @var string|null The From Address Street 2
     *
     * @ORM\Column(name="from_street2", type="string", length=40, nullable=true)
     * @Groups({"orders:write"})
     * @Assert\Length(max=40)
     */
    public $from_street2;

    /**
     * @var string The From Address City
     *
     * @ORM\Column(name="from_city", type="string", length=40, nullable=true)
     * @Groups({"orders:write"})
     * @Assert\Length(max=40)
     */
    public $from_city;

    /**
     * @var string The From Address Province
     *
     * @ORM\Column(name="from_province", type="string", length=40, nullable=true)
     * @Groups({"orders:write"})
     * @Assert\Length(max=40)
     */
    public $from_province;

    /**
     * @var string The From Address Postal Code
     *
     * @ORM\Column(name="from_postal_code", type="string", length=10, nullable=true)
     * @Groups({"orders:write"})
     * @Assert\Length(max=10)
     */
    public $from_postal_code;

    /**
     * @var string The From Address Country
     *
     * @ORM\Column(name="from_country", type="string", length=2, options={"fixed" = true})
     * @Groups({"orders:write"})
     * @Assert\Choice(callback="getCountryIsoCodes", message="The country of origin or where the product was manufactured must be in the ISO 3166-1 alpha-2 format.")
     */
    public $from_country;

    /**
     * @var string The Service used
     *
     * @ORM\Column(name="service", type="string", length=16)
     * @Groups({"orders:read", "orders:write"})
     * @Assert\Length(max=16)
     * @Assert\Choice(choices={"BoxC Post", "BoxC Parcel", "BoxC Plus", "BoxC Priority"}, message="Service must be one of these options: BoxC Post, BoxC Parcel, BoxC Plus, BoxC Priority")
     */
    public $service;

    /**
     * @var ShippingAddress The To Address
     *
     * @Assert\Valid
     * @Groups({"orders:read", "orders:write"})
     */
    public $shipping_address;

    /**
     * @var string The To Address Company Name
     *
     * @ORM\Column(name="to_company", type="string", length=40, nullable=true)
     * @Groups({"orders:write"})
     */
    public $to_company;

    /**
     * @var string The To Address Name
     *
     * @ORM\Column(name="to_name", type="string", length=40)
     * @Groups({"orders:write"})
     */
    public $to_name;

    /**
     * @var string|null The To Address Phone
     *
     * @ORM\Column(name="to_phone", type="string", length=20, nullable=true)
     * @Groups({"orders:write"})
     * @Assert\Length(max=20)
     */
    public $to_phone;

    /**
     * @var string|null The To Address Email
     *
     * @ORM\Column(name="to_email", type="string", length=20, nullable=true)
     * @Groups({"orders:write"})
     * @Assert\Length(max=20)
     * @Assert\Email
     */
    public $to_email;

    /**
     * @var string The To Address Street 1
     *
     * @ORM\Column(name="to_street1", type="string", length=40, nullable=true)
     * @Groups({"orders:write"})
     * @Assert\Length(max=40)
     */
    public $to_street1;

    /**
     * @var string|null The To Address Street 2
     *
     * @ORM\Column(name="to_street2", type="string", length=40, nullable=true)
     * @Groups({"orders:write"})
     * @Assert\Length(max=40)
     */
    public $to_street2;

    /**
     * @var string The To Address City
     *
     * @ORM\Column(name="to_city", type="string", length=40, nullable=true)
     * @Groups({"orders:write"})
     * @Assert\Length(max=40)
     */
    public $to_city;

    /**
     * @var string|null The To Address Province
     *
     * @ORM\Column(name="to_province", type="string", length=40, nullable=true)
     * @Groups({"orders:write"})
     * @Assert\Length(max=40)
     */
    public $to_province;

    /**
     * @var string The To Address Postal Code
     *
     * @ORM\Column(name="to_postal_code", type="string", length=10, nullable=true)
     * @Groups({"orders:write"})
     * @Assert\Length(max=10)
     */
    public $to_postal_code;

    /**
     * @var string The To Address Country
     *
     * @ORM\Column(name="to_country", type="string", length=2, options={"fixed" = true})
     * @Groups({"orders:write"})
     * @Assert\Choice(callback="getCountryIsoCodes", message="The country of origin or where the product was manufactured must be in the ISO 3166-1 alpha-2 format.")
     */
    public $to_country;

    /**
     * @ORM\ManyToOne(targetEntity=Shops::class, inversedBy="orders")
     * @Groups({"orders:read", "orders:write"})
     * @SerializedName("shop_id")
     */
    public $shop;

    /**
     * @var string|null Shop order Id
     *
     * @ORM\Column(name="shop_order_id", type="string", length=32, nullable=true)
     * @Groups({"orders:read", "orders:write"})
     * @Assert\Length(max=32)
     */
    public $shop_order_id;

    /**
     * @var int Signature Confirmation flag.
     *
     * @ORM\Column(name="sig_con", type="boolean", options={"unsigned"=true, "default"=0})
     * @Groups({"orders:read", "orders:write"})
     */
    public $sig_con = 0;

    /**
     * @var string status
     *
     * @ORM\Column(name="status", type="string", length=16, options={"default"="Processing"})
     * @Groups({"orders:read", "orders:write"})
     */
    public $status;

    /**
     * @var string The Incoterms for this shipment. Options are: DDU (default), DDP
     *
     * @ORM\Column(name="terms", type="string", length=3, options={"fixed"=true, "default"="DDU"})
     * @Groups({"orders:read", "orders:write"})
     * @Assert\Choice(choices={"", "DDU", "DDP"}, message="Terms must be either DDU or DDP")
     */
    private $terms = "DDU";

    /**
     * @var int Whether or not this order is a test. Test shops always create test orders. Set by the system.
     *
     * @ORM\Column(name="test", type="boolean", options={"unsigned"=true, "default"=0})
     * @Groups({"orders:read", "orders:write"})
     **/
    private $test;

    /**
     * @ORM\ManyToOne(targetEntity=Warehouses::class)
     * @SerializedName("preferred_wh_id")
     * @Groups({"orders:read", "orders:write"})
     */
    private $preferred_wh;

    /**
     * @var int The number of products / line items in an order. Only available while searching. Set by the system.
     *
     * @ORM\Column(name="shop_line_items", type="smallint", options={"unsigned"=true, "default"=0})
     * @Groups({"orders:read"})
     * @SerializedName("total_products")
     */
    private $shop_line_items = 0;

    /**
     * @var int The number of units in an order. Only available while searching. Set by the system.
     *
     * @ORM\Column(name="total_quantity", type="smallint", options={"unsigned"=true, "default"=0})
     * @Groups({"orders:read"})
     */
    private $total_quantity = 0;

    /**
     * @var int
     * @Groups({"orders:collectionread"})
     */
    public $total_orders = 0;

    public function __construct() {
        $this->last_check = new \DateTimeImmutable();
        $this->next_check = new \DateTimeImmutable();
        $this->placed_at = new \DateTimeImmutable();
        $this->line_item_list = new ArrayCollection();
    }

    public function getAttributesLineItems() : ?array
    {
        return $this->attributes_line_items;
    }

    public function setAttributesLineItems(?array $attributes_line_items)
    {
        $this->attributes_line_items = $attributes_line_items;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getConsignee(): Consignee {
        $consignee = new Consignee();

        $consignee->setName($this->cne_name);
        $consignee->setPhone($this->cne_phone);
        $consignee->setEmail($this->cne_email);
        $consignee->setId($this->cne_id);
        $consignee->setStreet1($this->cne_street1);
        $consignee->setStreet2($this->cne_street2);
        $consignee->setCity($this->cne_city);
        $consignee->setProvince($this->cne_province);
        $consignee->setPostalCode($this->cne_postal_code);
        $consignee->setCountry($this->cne_country);

        return $consignee;
    }

    public function getConsignor(): Consignor {
        $consignor = new Consignor();

        $consignor->setName($this->con_name);
        $consignor->setPhone($this->con_phone);
        $consignor->setId($this->con_id);
        $consignor->setStreet1($this->con_street1);
        $consignor->setStreet2($this->con_street2);
        $consignor->setCity($this->con_city);
        $consignor->setProvince($this->con_province);
        $consignor->setPostalCode($this->con_postal_code);
        $consignor->setCountry($this->con_country);

        return $consignor;
    }
    public function getCneName(): ?string {
        return $this->cne_name;
    }

    public function setCneName(?string $cne_name) {
        $this->cne_name = $cne_name;
    }

    public function getCnePhone(): ?string {
        return $this->cne_phone;
    }

    public function setCnePhone(?string $cne_phone) {
        $this->cne_phone = $cne_phone;
    }

    public function setCneEmail(?string $cne_email) {
        $this->cne_email = $cne_email;
    }

    public function setCneId(?string $cne_id) {
        $this->cne_id = $cne_id;
    }

    public function setCneStreet1(?string $cne_street1) {
        $this->cne_street1 = $cne_street1;
    }

    public function setCneStreet2(?string $cne_street2) {
        $this->cne_street2 = $cne_street2;
    }

    public function setCneCity(?string $cne_city) {
        $this->cne_city = $cne_city;
    }

    public function setCneProvince(?string $cne_province) {
        $this->cne_province = $cne_province;
    }

    public function setCnePostalCode(?string $cne_postal_code) {
        $this->cne_postal_code = $cne_postal_code;
    }

    public function setCneCountry(?string $cne_country) {
        $this->cne_country = $cne_country;
    }

    public function setConName(?string $con_name) {
        $this->con_name = $con_name;
    }

    public function setConPhone(?string $con_phone) {
        $this->con_phone = $con_phone;
    }

    public function setConId(?string $con_id) {
        $this->con_id = $con_id;
    }

    public function setConStreet1(?string $con_street1) {
        $this->con_street1 = $con_street1;
    }

    public function setConStreet2(?string $con_street2) {
        $this->con_street2 = $con_street2;
    }

    public function setConCity(?string $con_city) {
        $this->con_city = $con_city;
    }

    public function setConProvince(?string $con_province) {
        $this->con_province = $con_province;
    }

    public function setConPostalCode(?string $con_postal_code) {
        $this->con_postal_code = $con_postal_code;
    }

    public function setConCountry(?string $con_country) {
        $this->con_country = $con_country;
    }

    public static function getCountryIsoCodes() {
        return Shipments::getCountryIsoCodes();
    }

    public function getReturnAddress(): ReturnAddress {
        $return_address = new ReturnAddress();

        $return_address->setName($this->from_name);
        $return_address->setStreet1($this->from_street1);
        $return_address->setStreet2($this->from_street2);
        $return_address->setCity($this->from_city);
        $return_address->setProvince($this->from_province);
        $return_address->setPostalCode($this->from_postal_code);
        $return_address->setCountry($this->from_country);

        return $return_address;
    }

    public function getShippingAddress(): ShippingAddress {
        $shipping_address = new ShippingAddress();

        $shipping_address->setName($this->to_name);
        $shipping_address->setEmail($this->to_email);
        $shipping_address->setPhone($this->to_phone);
        $shipping_address->setStreet1($this->to_street1);
        $shipping_address->setStreet2($this->to_street2);
        $shipping_address->setCity($this->to_city);
        $shipping_address->setProvince($this->to_province);
        $shipping_address->setPostalCode($this->to_postal_code);
        $shipping_address->setCountry($this->to_country);

        return $shipping_address;
    }

    public function setFromName(?string $from_name) {
        $this->from_name = $from_name;
    }

    public function setFromStreet1(?string $from_street1) {
        $this->from_street1 = $from_street1;
    }

    public function setFromStreet2(?string $from_street2) {
        $this->from_street2 = $from_street2;
    }

    public function setFromCity(?string $from_city) {
        $this->from_city = $from_city;
    }

    public function setFromProvince(?string $from_province) {
        $this->from_province = $from_province;
    }

    public function setFromPostalCode(?string $from_postal_code) {
        $this->from_postal_code = $from_postal_code;
    }

    public function setFromCountry(?string $from_country) {
        $this->from_country = $from_country;
    }

    public function setToCompany(?string $to_company) {
        $this->to_company = $to_company;
    }

    public function setToName(?string $to_name) {
        $this->to_name = $to_name;
    }

    public function setToPhone(?string $to_phone) {
        $this->to_phone = $to_phone;
    }

    public function setToEmail(?string $to_email) {
        $this->to_email = $to_email;
    }

    public function setToStreet1(?string $to_street1) {
        $this->to_street1 = $to_street1;
    }

    public function setToStreet2(?string $to_street2) {
        $this->to_street2 = $to_street2;
    }

    public function setToCity(?string $to_city) {
        $this->to_city = $to_city;
    }

    public function setToProvince(?string $to_province) {
        $this->to_province = $to_province;
    }

    public function setToPostalCode(?string $to_postal_code) {
        $this->to_postal_code = $to_postal_code;
    }

    public function setToCountry(?string $to_country) {
        $this->to_country = $to_country;
    }

    /**
     * @param array
     * @return $this
     */
    public function setLineItemList(array $line_items):self
    {
        foreach ($line_items as $line_item) {
            $this->addLineItem($line_item);
        }

        return $this;
    }

    public function addLineItem($line_item):self
    {
        // Get product id.
        $product_id = null;
        $new = null;
        $product_user = 0;

        if ($line_item instanceof Products) {
            $product_id = $line_item->getId();
            $product_user = $line_item->getUser()->getId();
        }

        $sku = $quantity = null;

        if (is_numeric($product_id) &&
            isset($this->attributes_line_items) &&
            isset($this->attributes_line_items[$product_id]))
        {
            $sku = isset($this->attributes_line_items[$product_id]['sku']) ? $this->attributes_line_items[$product_id]['sku'] : null;
            $quantity = isset($this->attributes_line_items[$product_id]['quantity']) ? $this->attributes_line_items[$product_id]['quantity'] : null;
            $sold_for = isset($this->attributes_line_items[$product_id]['sold_for']) ? $this->attributes_line_items[$product_id]['sold_for'] : null;
            $current_user = isset($this->attributes_line_items[$product_id]['current_user']) ? $this->attributes_line_items[$product_id]['current_user'] : 0;

            // Add new LineItems object if Product owner matches the current user.
            if ($product_user > 0 && $product_user == $current_user) {
                $this->line_item_list[] = new LineItems($this, $line_item, $sku, $quantity, $sold_for);
            }
        }

        return $this;
    }

    public function setTest ($test) {
        $this->test = $this->shop->test;

        // Update the address and other related fields.
        $this->setCneName($this->consignee->getName());
        $this->setCnePhone($this->consignee->getPhone());
        $this->setCneEmail($this->consignee->getEmail());
        $this->setCneId($this->consignee->getId());
        $this->setCneStreet1($this->consignee->getStreet1());
        $this->setCneStreet2($this->consignee->getStreet2());
        $this->setCneCity($this->consignee->getCity());
        $this->setCneProvince($this->consignee->getProvince());
        $this->setCnePostalCode($this->consignee->getPostalCode());
        $this->setCneCountry($this->consignee->getCountry());
        $this->setConName($this->consignor->getName());
        $this->setConPhone($this->consignor->getPhone());
        $this->setConId($this->consignor->getId());
        $this->setConStreet1($this->consignor->getStreet1());
        $this->setConStreet2($this->consignor->getStreet2());
        $this->setConCity($this->consignor->getCity());
        $this->setConProvince($this->consignor->getProvince());
        $this->setConPostalCode($this->consignor->getPostalCode());
        $this->setConCountry($this->consignor->getCountry());
        $this->setFromName($this->return_address->getName());
        $this->setFromStreet1($this->return_address->getStreet1());
        $this->setFromStreet2($this->return_address->getStreet2());
        $this->setFromCity($this->return_address->getCity());
        $this->setFromProvince($this->return_address->getProvince());
        $this->setFromPostalCode($this->return_address->getPostalCode());
        $this->setFromCountry($this->return_address->getCountry());
        $this->setToCompany($this->shipping_address->getCompany());
        $this->setToName($this->shipping_address->getName());
        $this->setToPhone($this->shipping_address->getPhone());
        $this->setToEmail($this->shipping_address->getEmail());
        $this->setToStreet1($this->shipping_address->getStreet1());
        $this->setToStreet2($this->shipping_address->getStreet2());
        $this->setToCity($this->shipping_address->getCity());
        $this->setToProvince($this->shipping_address->getProvince());
        $this->setToPostalCode($this->shipping_address->getPostalCode());
        $this->setToCountry($this->shipping_address->getCountry());
    }

    public function getTest()
    {
        return $this->test;
    }

    public function setCreated(\DateTime $created)
    {
        $this->created = $created;
    }

    public function setQuantity(int $quantity)
    {
        $this->quantity = $quantity;
    }

    public function getStatus() :string
    {
        return empty($this->status) ? 'Processing' : $this->status;
    }

    public function setStatus(string $status)
    {
        // Updating an order that is 'Ready' will reset its status to
        //   'Processing' and restore product quantities until it's
        //   processed by the system again.
        $current_status = $this->getStatus();

        if ($current_status == 'Ready') {
            $status = 'Processing';

            foreach($this->line_item_list as $key => $obj) {
                if (is_object($obj)) {
                    $this->line_item_list[$key]->quantity = 0;
                }
            }
        }

        $this->status = $status;
    }

    /**
     * Validates Order status.
     *
     * @Assert\Callback
     */
    public function validate(ExecutionContextInterface $context, $payload)
    {
        $tempstatus = strtolower($this->status);

        // Violation occurs for status of Packing or Fulfilled, which can't be updated.
        if (in_array($tempstatus, ['packing', 'fulfilled'])) {
            $context->buildViolation('Orders with the status of Packing or Fulfilled may not be updated.')
                ->atPath('status')
                ->addViolation();
        } // Only Holding and Processing can be entered by the user.
        elseif (!in_array($tempstatus, ['holding', 'processing'])) {
            $context->buildViolation('Order status must be Holding or Processing.')
                ->atPath('status')
                ->addViolation();
        }

        // Verify that Shop is active.
        if (!$this->shop->active) {
            $context->buildViolation('No orders can be created for a shop flagged as inactive.')
                ->atPath('active')
                ->addViolation();           
        }
    }
}
