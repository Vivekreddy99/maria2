<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Entity\EntryPoints;
use App\Entity\Manifests;
use App\Entity\Helper\LineItems;
use App\Entity\Helper\LineItem;
use App\Entity\Helper\Consignee;
use App\Entity\Helper\Consignor;
use App\Entity\Helper\Package;
use App\Entity\Helper\ReturnAddress;
use App\Entity\Helper\ShippingAddress;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Validator\Exception;
use App\Exception\TooManyPackagesException;
use ApiPlatform\Core\Annotation\ApiFilter;
use App\Filter\OrderByFixedPropertyFilter;
use App\Filter\DateInclusiveFilter;
use App\Filter\SearchFilterWithMapping;
use App\Filter\ExistsFilterWithMapping;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 *
 * @ApiResource(
 *     collectionOperations={
 *         "get"={
 *             "normalization_context"={"groups"={"shipments:collectionread", "shipments:read"}}
 *         },
 *         "post"
 *     },
 *     itemOperations={
 *         "get"={
 *             "normalization_context"={"groups"={"shipments:read", "shipments:item:get"}}
 *         },
 *         "delete"
 *     },
 *     normalizationContext={"groups"={"shipments:read"}},
 *     denormalizationContext={"groups"={"shipments:write"}},
 *     attributes={"route_prefix"="/v2"}
 * )
 * @ORM\Entity
 * @ApiFilter(OrderByFixedPropertyFilter::class, arguments={"orderParameterName"="sort"}, properties={"id": "desc"})
 * @ApiFilter(DateInclusiveFilter::class, properties={"created"})
 * @ApiResource(attributes={"pagination_items_per_page"=50})
 * @ApiFilter(SearchFilterWithMapping::class, properties={"id": "exact", "comments": "partial", "order_number": "partial", "to_name": "partial", "carton.id": "exact"})
 * @ApiFilter(ExistsFilterWithMapping::class, properties={"carton", "labels"})
 */
class Shipments
{
    /**
     * @var int Canceled flag
     *
     * @ORM\Column(name="canceled", type="boolean", options={"unsigned"=true, "default"=0})
     * @Groups({"shipments:read"})
     */
    public $canceled = 0;

    /**
     * @var decimal The Chargeable Weight
     *
     * @ORM\Column(name="chargeable_weight", type="decimal", precision=7, scale=3, options={"unsigned"=true}, nullable=true)
     * @Groups({"shipments:read"})
     */
    private $chargeable_weight;

    /**
     * @var string|null Comments
     *
     * @ORM\Column(name="comments", type="json", nullable=true)
     * @Groups({"shipments:read", "shipments:write"})
     */
    private $comments;

    /**
     * @var string Class (eCommerce or Payload)
     *
     * @ORM\Column(name="class", type="string", length=32, options={"default"="eCommerce"}, nullable=true)
     * @Groups({"shipments:read", "shipments:write"})
     * @SerializedName("class")
     * @Assert\Choice(choices={"eCommerce", "Payload"}, message="Class must be eCommerce or Payload. Note: eCommerce can have only one package.")
     */
    public $class = "eCommerce";

    /**
     * @var Consignee The Consignee
     *
     * @Assert\Valid
     * @Groups({"shipments:read", "shipments:write"})
     */
    public $consignee;

    /**
     * @var string The Consignee Name
     *
     * @ORM\Column(name="cne_name", type="string", length=40)
     * @Groups({"shipments:write"})
     */
    public $cne_name;

    /**
     * @var string|null The Consignee Phone
     *
     * @ORM\Column(name="cne_phone", type="string", length=20, nullable=true)
     * @Groups({"shipments:write"})
     */
    private $cne_phone;

    /**
     * @var string|null The Consignee Email
     *
     * @ORM\Column(name="cne_email", type="string", length=20, nullable=true)
     * @Groups({"shipments:read", "shipments:write"})
     */
    private $cne_email;

    /**
     * @var string|null The Consignee Id
     *
     * @ORM\Column(name="cne_id", type="string", length=20, nullable=true)
     * @Groups({"shipments:read", "shipments:write"})
     */
    private $cne_id;

    /**
     * @var string The Consignee Street 1
     *
     * @ORM\Column(name="cne_street1", type="string", length=40, nullable=true)
     * @Groups({"shipments:read", "shipments:write"})
     */
    private $cne_street1;

    /**
     * @var string|null The Consignee Street 2
     *
     * @ORM\Column(name="cne_street2", type="string", length=40, nullable=true)
     * @Groups({"shipments:read", "shipments:write"})
     */
    private $cne_street2;

    /**
     * @var string The Consignee City
     *
     * @ORM\Column(name="cne_city", type="string", length=40, nullable=true)
     * @Groups({"shipments:read", "shipments:write"})
     */
    private $cne_city;

    /**
     * @var string The Consignee Province
     *
     * @ORM\Column(name="cne_province", type="string", length=40, nullable=true)
     * @Groups({"shipments:read", "shipments:write"})
     */
    private $cne_province;

    /**
     * @var string The Consignee Postal Code
     *
     * @ORM\Column(name="cne_postal_code", type="string", length=10, nullable=true)
     * @Groups({"shipments:read", "shipments:write"})
     */
    private $cne_postal_code;

    /**
     * @var string The Consignee Country
     *
     * @ORM\Column(name="cne_country", type="string", length=2, options={"fixed" = true})
     * @Groups({"shipments:read", "shipments:write"})
     */
    private $cne_country;

    /**
     * @var Consignor The Consignor
     *
     * @Assert\Valid
     * @Groups({"shipments:read", "shipments:write"})
     */
    public $consignor;

    /**
     * @var string The Consignor Name
     *
     * @ORM\Column(name="con_name", type="string", length=40)
     * @Groups({"shipments:read", "shipments:write"})
     */
    private $con_name;

    /**
     * @var string|null The Consignor Phone
     *
     * @ORM\Column(name="con_phone", type="string", length=20, nullable=true)
     * @Groups({"shipments:read", "shipments:write"})
     */
    private $con_phone;

    /**
     * @var string|null The Consignor Id
     *
     * @ORM\Column(name="con_id", type="string", length=20, nullable=true)
     * @Groups({"shipments:read", "shipments:write"})
     */
    private $con_id;

    /**
     * @var string The Consignor Street 1
     *
     * @ORM\Column(name="con_street1", type="string", length=40)
     * @Groups({"shipments:read", "shipments:write"})
     */
    private $con_street1;

    /**
     * @var string|null The Consignor Street 2
     *
     * @ORM\Column(name="con_street2", type="string", length=40, nullable=true)
     * @Groups({"shipments:read", "shipments:write"})
     */
    private $con_street2;

    /**
     * @var string The Consignor City
     *
     * @ORM\Column(name="con_city", type="string", length=40, nullable=true)
     * @Groups({"shipments:read", "shipments:write"})
     */
    private $con_city;

    /**
     * @var string|null The Consignor Province
     *
     * @ORM\Column(name="con_province", type="string", length=40, nullable=true)
     * @Groups({"shipments:read", "shipments:write"})
     */
    private $con_province;

    /**
     * @var string The Consignor Postal Code
     *
     * @ORM\Column(name="con_postal_code", type="string", length=10, nullable=true)
     * @Groups({"shipments:read", "shipments:write"})
     */
    private $con_postal_code;

    /**
     * @var string The Consignor Country
     *
     * @ORM\Column(name="con_country", type="string", length=2, options={"fixed" = true})
     * @Groups({"shipments:read", "shipments:write"})
     */
    private $con_country;

    /**
     * @var decimal The Cost (3.58)
     *
     * @ORM\Column(name="cost", type="decimal", precision=12, scale=2, options={"default": 0.0})
     * @Groups({"shipments:read"})
     */
    public $cost = 0.0;

    /**
     * @var \DateTimeInterface Creation Date
     *
     * @ORM\Column(name="created", type="datetime", options={"default": "CURRENT_TIMESTAMP", "on update": "CURRENT_TIMESTAMP"})
     * @Groups({"shipments:read"})
     */
    public $created;

    /**
     * @var int Create label flag
     *
     * @ORM\Column(name="create_label", type="boolean", options={"unsigned"=true, "default": 0})
     * @Groups({"shipments:read", "shipments:write"})
     */
    private $create_label = 0;

    /**
     * @ORM\ManyToOne(targetEntity=EntryPoints::class)
     * @Groups({"shipments:write"})
     * @SerializedName("entry_point")
     */
    public $ep;

    /**
     * @var string Non-IRI Entry Point value
     *
     * @Groups({"shipments:read"})
     * @SerializedName("entry_point")
     */
    public $ep_plain;

    /**
     * @var string JSON array of events.
     *
     * @ORM\Column(name="events", type="json", nullable=true)
     * @Groups({"shipments:read"})
     */
    public $events;

    /**
     * @var int The entity Id
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(name="id", type="bigint", options={"unsigned"=true})
     * @Groups({"orders:read", "shipments:read", "shipments:write", "overpacks:read", "overpacks:write"})
     */
    public $id;

    /**
     * @ORM\OneToMany(targetEntity=Labels::class, mappedBy="shipment", cascade={"All"})
     * @Groups({"shipments:read", "shipments:write", "labels:read", "labels:write"})
     */
    private $labels;

    /**
     * @var string JSON formatted Line Items for storage.
     *
     * @ORM\Column(name="line_items", type="json", nullable=true)
     */
    public $line_items;

    /**
     * @var LineItems[]
     *
     * @Assert\Valid
     * @Groups({"shipments:read", "shipments:write"})
     * @SerializedName("line_items")
     */
    public $line_item_list;

    /**
     * @var LineItem[]
     *
     */
    public $line_item_arr;

    /**
     * @var array Used for line items count constraint
     * @Assert\Count(max=999)
     */
    private $line_item_count;

    /**
     * @var string|null A user supplied order number. It does not need to be unique. Max length: 40.
     *
     * @ORM\Column(name="order_number", type="string", length=40, nullable=true)
     * @Groups({"shipments:read", "shipments:write"})
     * @Assert\Length(max=40)
     */
    public $order_number;

    /**
     * @ORM\ManyToOne(targetEntity=Overpacks::class, inversedBy="shipments")
     * @Groups({"overpacks:write"})
     * @SerializedName("overpack_id")
     */
    private $carton;

    /**
     * @var int|null
     *
     * @Groups({"shipments:read"})
     */
    public $overpack_id;

    /**
     * @var string JSON formatted Line Items for storage.
     *
     * @ORM\Column(name="packages", type="json", nullable=true)
     */
    public $packages;

    /**
     * @var Package[] List of Packages
     *
     * @Groups({"shipments:read", "shipments:write"})
     * @SerializedName("packages")
     */
    public $package_list;

    /**
     * @var string[] References and notes for the bottom of the label. Max of 3 items. Each item in the array is a String with max length of 32.
     *
     * @Assert\Count(max=3, maxMessage="The maximum number of references is 3.")
     * @Assert\All(@Assert\Length(max=32, maxMessage="Each reference must be 32 characters or less."))
     * @ORM\Column(name="refs", type="json", length=96, nullable=true)
     * @Groups({"shipments:read", "shipments:write"})
     */
    public $references;

    /**
     * @var ReturnAddress|null The From Address
     *
     * @Assert\Valid
     * @Groups({"shipments:read", "shipments:write"})
     */
    public $return_address;

    /**
     * @var string|null The From Address Name
     *
     * @ORM\Column(name="from_name", type="string", length=40, nullable=true)
     * @Groups({"shipments:read", "shipments:write"})
     */
    private $from_name;

    /**
     * @var string The From Address Street 1
     *
     * @ORM\Column(name="from_street1", type="string", length=40)
     * @Groups({"shipments:read", "shipments:write"})
     */
    private $from_street1;

    /**
     * @var string|null The From Address Street 2
     *
     * @ORM\Column(name="from_street2", type="string", length=40, nullable=true)
     * @Groups({"shipments:read", "shipments:write"})
     */
    private $from_street2;

    /**
     * @var string The From Address City
     *
     * @ORM\Column(name="from_city", type="string", length=40)
     * @Groups({"shipments:read", "shipments:write"})
     */
    private $from_city;

    /**
     * @var string|null The From Address Province
     *
     * @ORM\Column(name="from_province", type="string", length=40, nullable=true)
     * @Groups({"shipments:read", "shipments:write"})
     */
    private $from_province;

    /**
     * @var string|null The From Address Postal Code
     *
     * @ORM\Column(name="from_postal_code", type="string", length=10, nullable=true)
     * @Groups({"shipments:read", "shipments:write"})
     */
    private $from_postal_code;

    /**
     * @var string The From Address Country
     *
     * @ORM\Column(name="from_country", type="string", length=2, options={"fixed" = true})
     * @Groups({"shipments:read", "shipments:write"})
     */
    private $from_country;

    /**
     * @var string The Service used
     *
     * @ORM\Column(name="service", type="string", length=16, options={"default"="BoxC Parcel"})
     * @Groups({"shipments:read", "shipments:write"})
     * @Assert\Length(max=16)
     * @Assert\Choice(choices={"BoxC Post", "BoxC Parcel", "BoxC Plus", "BoxC Priority"}, message="Service must be one of these options: BoxC Post, BoxC Parcel, BoxC Plus, BoxC Priority")
     */
    public $service;

    /**
     * @var decimal The Height as decimal
     *
     * @ORM\Column(name="height", type="decimal", precision=4, scale=1, options={"default"=0})
     * @Groups({"shipments:read", "shipments:write"})
     */
    private $height = 0.0;

    /**
     * @var int Irregular flag
     *
     * @ORM\Column(name="irregular", type="boolean", options={"unsigned"=true, "default"=0})
     * @Groups({"shipments:read", "shipments:write"})
     */
    private $irregular=0;

    /**
     * @var decimal The Length as decimal
     *
     * @ORM\Column(name="length", type="decimal", precision=4, scale=1, options={"default"=0})
     * @Groups({"shipments:read", "shipments:write"})
     */
    private $length = 0;

    /**
     * @var int Override flag
     *
     * @ORM\Column(name="override", type="boolean", options={"unsigned"=true, "default"=0})
     * @Groups({"shipments:read", "shipments:write"})
     */
    private $override=0;

    /**
     * @var ShippingAddress The To Address
     *
     * @Assert\Valid
     * @Groups({"shipments:read", "shipments:write"})
     */
    public $shipping_address;

    /**
     * @var string|null The To Address Company Name
     *
     * @ORM\Column(name="to_company", type="string", length=40, nullable=true)
     * @Groups({"shipments:read", "shipments:write"})
     */
    private $to_company;

    /**
     * @var string The To Address Name
     *
     * @ORM\Column(name="to_name", type="string", length=40)
     * @Groups({"shipments:read", "shipments:write"})
     */
    private $to_name;

    /**
     * @var string|null The To Address Phone
     *
     * @ORM\Column(name="to_phone", type="string", length=20, nullable=true)
     * @Groups({"shipments:read", "shipments:write"})
     */
    private $to_phone;

    /**
     * @var string|null The To Address Email
     *
     * @ORM\Column(name="to_email", type="string", length=20, nullable=true)
     * @Groups({"shipments:read", "shipments:write"})
     */
    private $to_email;

    /**
     * @var string The To Address Street 1
     *
     * @ORM\Column(name="to_street1", type="string", length=40)
     * @Groups({"shipments:read", "shipments:write"})
     */
    private $to_street1;

    /**
     * @var string|null The To Address Street 2
     *
     * @ORM\Column(name="to_street2", type="string", length=40, nullable=true)
     * @Groups({"shipments:read", "shipments:write"})
     */
    private $to_street2;

    /**
     * @var string The To Address City
     *
     * @ORM\Column(name="to_city", type="string", length=40, nullable=true)
     * @Groups({"shipments:read", "shipments:write"})
     */
    private $to_city;

    /**
     * @var string|null The To Address Province
     *
     * @ORM\Column(name="to_province", type="string", length=40, nullable=true)
     * @Groups({"shipments:read", "shipments:write"})
     */
    private $to_province;

    /**
     * @var string The To Address Postal Code
     *
     * @ORM\Column(name="to_postal_code", type="string", length=10, nullable=true)
     * @Groups({"shipments:read", "shipments:write"})
     */
    private $to_postal_code;

    /**
     * @var string The To Address Country
     *
     * @ORM\Column(name="to_country", type="string", length=2, options={"fixed" = true})
     * @Groups({"shipments:read", "shipments:write"})
     */
    private $to_country;

    /**
     * @var int Request signature confirmation from the recipient upon delivery. Not available for all services or routes. Additional fees apply. Default is false.
     *
     * @ORM\Column(name="sig_con", type="boolean", options={"unsigned"=true, "default"=0})
     * @Groups({"shipments:read", "shipments:write"})
     * @SerializedName("signature_confirmation")
     */
    public $sig_con=0;

    /**
     * @var string The Incoterms for this shipment. Options are: DDU (default), DDP
     *
     * @ORM\Column(name="terms", type="string", length=3, options={"fixed"=true, "default"="DDU"})
     * @Groups({"shipments:read", "shipments:write"})
     * @Assert\Choice(choices={"", "DDU", "DDP"}, message="Terms must be either DDU or DDP")
     */
    public $terms = "DDU";

    /**
     * @var int Whether or not this is a test shipment that will generate test labels. Default is false.
     *
     * @ORM\Column(name="test", type="boolean", options={"unsigned"=true, "default"=0})
     * @Groups({"shipments:read", "shipments:write"})
     *
     * Note: if PUT/PATCH operations are enabled, this field must be made Immutable.
     */
    public $test = 0;

    /**
     * @var string|null The tracking number for this shipment. Default is null. Set by the system.
     *
     * @Assert\Type("string")
     * @Assert\Length(max=40)
     * @ORM\Column(name="tracking_number", type="string", length=40, nullable=true, unique=true)
     * @Groups({"shipments:read", "overpacks:read", "overpacks:write"})
     */
    public $tracking_number;

    /**
     * @var \DateTimeInterface Updated Date
     *
     * @ORM\Column(name="updated", type="datetime", options={"default": "CURRENT_TIMESTAMP", "on update": "CURRENT_TIMESTAMP"})
     * @Groups({"shipments:read", "shipments:write"})
     */
    private $updated;

    /**
     * @var int Verified flag
     *
     * @ORM\Column(name="verified", type="boolean", options={"unsigned"=true, "default"=0})
     * @Groups({"shipments:read", "shipments:write"})
     */
    private $verified = 0;

    /**
     * @var decimal The Weight as decimal
     *
     * @ORM\Column(name="weight", type="decimal", precision=7, scale=3)
     * @Groups({"shipments:read", "shipments:write"})
     */
    private $weight = 0;

    /**
     * @var decimal The Volumetric Weight as decimal
     *
     * @ORM\Column(name="volumetric_weight", type="decimal", precision=7, scale=3, options={"default"="0.000"})
     * @Groups({"shipments:read", "shipments:write"})
     */
    private $volumetric_weight = 0;

    /**
     * @var decimal The Width as decimal
     *
     * @ORM\Column(name="width", type="decimal", precision=4, scale=1)
     * @Groups({"shipments:read", "shipments:write"})
     */
    private $width = 0;

    /**
     * @ORM\ManyToOne(targetEntity=Users::class, inversedBy="shipments")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     * @Groups({"shipments:write"})
     * @SerializedName("user_id")
     */
    private $user;

    /**
     * @var int The ent id
     *
     * @ORM\Column(name="ent_id", type="integer", length=11, options={"unsigned"=true}, nullable=true)
     * @Assert\Length(max=11)
     */
    private $ent_id;

    /**
     * @var string|null Metadata
     *
     * @ORM\Column(name="metadata", type="json", nullable=true)
     * @Groups({"shipments:read", "shipments:write"})
     */
    public $meta_data;

    /**
     * @var int
     * @Groups({"shipments:collectionread"})
     */
    public $total_shipments = 0;

    public function __construct() {
        $this->created = new \DateTimeImmutable();
    }

    public function getId(): int
    {
        return $this->id;
    }

    // Calculate sum of packages chargeable weights.
    public function calculateChargeableWeight() {
        $sum = 0.0;
        $pkgs = $this->getPackageList();
        foreach ($pkgs as $pkg) {
            if (isset($pkg->chargeable_weight)) {
                $sum += $pkg->chargeable_weight;
            }
        }

        $this->chargeable_weight = $sum;
    }

    public function getCreated(): ?\DateTimeInterface {
        return $this->created;
    }

    public function getUpdated(): ?\DateTimeInterface {
        return $this->updated;
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

    public function setLineItemCount(array $line_item_count) {
        $this->line_item_count = $line_item_count;
    }

    /**
     * @return string JSON stored in database.
     */
    public function getLineItems() {
        return $this->line_items;
    }

    public function setLineItems(string $line_items) {
        $this->line_items = $line_items;
    }

    public function setLineItemList($line_item_list) {
        $this->line_item_list = $line_item_list;
    }

    /**
     * @return LineItems[]
     *
     * Parses json stored in database into LineItem[].
     */
    public function getLineItemList() {

        $json = $this->getLineItems();

        if (!empty($json)) {
            $arr = json_decode($json);
            foreach($arr as $obj) {
                $this->addLineItem($obj);
            }
        }

        return $this->line_item_list;
    }

    public function getLineItemArr() {
        $json = $this->getLineItems();

        if (!empty($json)) {
            $arr = json_decode($json);
            foreach($arr as $obj) {
                $this->addLineItem($obj);
            }
        }

        return $this->line_item_arr;
    }

    public function addLineItem($line_item) {
        if (empty($line_item)) return;

        $temp = new LineItem();

        foreach($line_item as $key => $value) {
            if (property_exists($line_item, $key)) {
                $temp->$key = $value;
            }
        }

        $temp2 = new LineItems();
        $temp2->addLineItem($temp);
        $this->line_item_list[] = $temp2;
    }

    public function getOverpackId(): ?int  {
        $id = null;

        if (isset($this->carton)) {
            return $this->carton->getId();
        }

        return $id;
    }

    public function getPackages() {
        return $this->packages;
    }

    public function setPackages(string $packages)
    {
        $arr = json_decode($packages);

        // eCommerce class Shipments can only have one package.
        if ($this->class == 'eCommerce') {
            if (count($arr) > 1) {
                throw new TooManyPackagesException('Shipments using the default eCommerce class, can only have one package. For more than one package, use the Payload class instead.');
            } else {
                // Add dimension defaults if empty.
                if (isset($arr[0])) {
                    foreach($arr[0] as $key => $value) {
                        if (empty($value)) {
                            if ($key == 'height') {
                                $arr[0]->height = 1;
                            }
                            if ($key == 'length') {
                                $arr[0]->length = 15;
                            }
                            if ($key == 'width') {
                                $arr[0]->width = 10;
                            }
                        }
                    }
                }
            }
            $packages = json_encode($arr);
        }

        $this->packages = $packages;
    }

    public function getPackageList() {
        $json = $this->getPackages();
        $this->package_list = [];

        $arr = json_decode($json);

        // eCommerce class Shipments can only have one package.
        if ($this->class == 'eCommerce' && count($arr) > 1) {
            throw new TooManyPackagesException('Shipments using the default eCommerce class, can only have one package. For more than one package, use the Payload class instead.');
        }

        foreach($arr as $obj) {
            $this->addPackage($obj);
        }

        return $this->package_list;
    }

    public function addPackage($package) {
        $temp = new Package();

        foreach($package as $key => $value) {
            if (property_exists($package, $key)) {
                $temp->$key = $value;
            }
        }

        // TODO: handle updating of chargeable weight dependent on route.
        $temp->calculateChargeableWeight(null);

        $this->package_list[] = $temp;
    }

    public function setTerms(?string $terms) {
        if (empty($terms)) {
            $terms = "DDU";
        }

        $this->terms = $terms;
    }

    public function getMetadata(): ?string {
        return $this->meta_data;
    }

    public function setMetadata(?string $meta_data) {
        $this->meta_data = $meta_data;
    }

    public function setTrackingNumber(?string $tracking_number) {
        $this->tracking_number = $tracking_number;
    }

    public function setUpdated($updated)
    {
        $this->updated = new \DateTimeImmutable();

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
        $this->setLineItems(json_encode($this->line_item_list));
        $this->setLineItemCount($this->line_item_list);
        $this->setPackages(json_encode($this->package_list));
        $this->calculateChargeableWeight();
    }

    public function getCarton(): ?Overpacks
    {
        return $this->carton;
    }

    public function setCarton(?Overpacks $overpack): self
    {
        $this->carton = $overpack;

        return $this;
    }

    public function getUser(): Users
    {
        return $this->user;
    }

    public function setUser(Users $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Validate dimensions, required if class is Payload, default values if eCommerce.
     *
     * @param ExecutionContextInterface $context
     * @param $payload
     *
     * @Assert\Callback
     */
    public function validate(ExecutionContextInterface $context, $payload)
    {
        // Dimensions for Payload type are required.
        if ('payload' == strtolower($this->class)) {
            foreach ($this->package_list as $package) {
                if (empty($package->height)) {
                    $context->buildViolation('Packages in a Shipment of Payload class must provide the height in centimeters.')
                        ->atPath('height')
                        ->addViolation();
                }
                if (empty($package->length)) {
                    $context->buildViolation('Packages in a Shipment of Payload class must provide the length in centimeters.')
                        ->atPath('length')
                        ->addViolation();

                }
                if (empty($package->width)) {
                    $context->buildViolation('Packages in a Shipment of Payload class must provide the width in centimeters.')
                        ->atPath('width')
                        ->addViolation();
                }
                if (empty($package->height) || empty($package->length) || empty($package->width)) {
                    break;
                }
            }
        } else { // Not required. Set defaults if empty.
            foreach ($this->package_list as $package) {
                if (empty($package->height)) {
                    $package->height = 1;
                }
                if (empty($package->length)) {
                    $package->length = 15;
                }
                if (empty($package->width)) {
                    $package->width = 10;
                }
            }
        }
    }

    /*
    public function getTotalShipments() :?int
    {
        $usr = $this->getUser();
        return count($usr->getShipments());
    }
    */

    // From api/v2/addresses.json
    public static function getCountryIsoCodes() {
        return [
            "AF", "AX", "AL", "DZ", "AS", "AD", "AO", "AI", "AQ", "AG", "AR", "AM", "AW", "AU", "AT", "AZ",
            "BS", "BH", "BD", "BB", "BY", "BE", "BZ", "BJ", "BM", "BT", "BO", "BA", "BW", "BV", "B
R", "IO", "BN", "BG", "BF", "BI",
            "KH", "CM", "CA", "CV", "KY", "CF", "TD", "CL", "CN", "CX", "CC", "CO", "KM", "CG", "CD", "CK", "CR", "CI", "HR", "CU", "CY", "CZ",
            "DK", "DJ", "DM", "DO",
            "EC", "EG", "SV", "GQ", "ER", "EE", "ET", "FK",
            "FO", "FJ", "FI", "FR",
            "GF", "PF", "TF", "GA", "GM", "GE", "DE", "GH", "GI", "GR", "GL", "GD", "GP", "GU", "GT", "GG", "GN", "GW", "GY",
            "HT", "HM", "VA", "HN", "HK", "HU",
            "IS", "IN", "ID", "IR", "IQ", "IE", "IM", "IL", "IT",
            "JM", "JP", "JE", "JO",
            "KZ", "KE", "KI", "KR", "KW", "KG",
            "LA", "LV", "LB", "LS", "LR", "LY", "LI", "LT", "LU",
            "MO", "MK", "MG", "MW", "MY", "MV", "ML", "MT", "MH", "MQ", "MR", "MU", "YT", "MX", "FM", "MD", "MC", "MN", "ME", "MS", "MA", "MZ", "MM",
            "NA", "NR", "NP", "NL", "AN", "NC", "NZ", "NI", "NE", "NG", "NU", "NF", "MP", "NO",
            "OM",
            "PK", "PW", "PS", "PA", "PG", "PY", "PE", "PH", "PN", "PL", "PT", "PR",
            "QA",
            "RE", "RO", "RU", "RW", "BL", "SH", "KN", "LC", "MF", "PM", "VC", "WS",
            "SM", "ST", "SA", "SN", "RS", "SC", "SL", "SG", "SK", "SI", "SB", "SO", "ZA", "GS", "ES", "LK", "SD", "SR", "SJ", "SZ", "SE", "CH", "SY",
            "TW", "TJ", "TZ", "TH", "TL", "TG", "TK", "TO", "TT", "TN", "TR", "TM", "TC", "TV",
            "UG", "UA", "AE", "GB", "US", "UM", "UY", "UZ",
            "VU", "VE", "VN", "VG", "VI",
            "WF",
            "EH",
            "YE",
            "ZM", "ZW"
        ];
    }
}
