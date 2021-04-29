<?php
// api/src/Entity/Fulfillments.php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Entity\EntryPoints;
use App\Entity\Shipments;
use App\Entity\Manifests;
use App\Entity\Warehouses;
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

/**
 * @ApiResource(
 *     collectionOperations={
 *         "get",
 *         "post"
 *     },
 *     itemOperations={
 *         "get",
 *         "delete"
 *     },
 *     normalizationContext={"groups"={"orders:read", "fulfillments:read"}},
 *     denormalizationContext={"groups"={"fulfillments:write"}},
 *     attributes={"route_prefix"="/v2"}
 * )
 * @ORM\Entity
 */
class Fulfillments
{
    /**
     * @var string|null carrier
     *
     * @ORM\Column(name="carrier", type="string", length=16, nullable=true)
     * @Groups({"fulfillments:write"})
     * @Assert\Length(max=16)
     */
    private $carrier;

    /**
     * @var \DateTimeInterface Creation Date
     *
     * @ORM\Column(name="created", type="datetime", options={"default": "CURRENT_TIMESTAMP"})
     * @Groups({"orders:read", "fulfillments:read"})
     */
    public $created;

    /**
     * @var string|null The Destination
     *
     * @ORM\Column(name="destination", type="string", length=3, options={"fixed" = true}, nullable=true)
     * @Groups({"fulfillments:write"})
     */
    private $destination;

    /**
     * @var \DateTimeInterface|null Fulfilled date.
     *
     * @ORM\Column(name="fulfilled", type="datetime", nullable=true)
     * @Groups({"orders:read", "fulfillments:read"})
     */
    public $fulfilled;

    /**
     * @var decimal Fulfillment fee.
     *
     * @ORM\Column(name="fulfillment_fee", type="decimal", precision=5, scale=2, options={"default": 0.00})
     * @Groups({"orders:read", "fulfillments:read", "fulfillments:write"})
     */
    public $fulfillment_fee = 0.00;

    /**
     * @var int The entity Id
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(name="id", type="bigint", options={"unsigned"=true})
     * @Groups({"orders:read"})
     */
    private $id;

    /**
     * @var int Invoiced flag
     *
     * @ORM\Column(name="invoiced", type="boolean", options={"unsigned"=true, "default"=0})
     * @Groups({"fulfillments:write"})
     */
    private $invoiced = 0;

    /**
     * @ORM\ManyToOne(targetEntity=Orders::class, inversedBy="fulfillments")
     * @Groups({"orders:write", "fulfillments:write"})
     */
    public $order;

    /**
     * @var \DateTimeInterface|null Packed date
     *
     * @ORM\Column(name="packed", type="datetime", nullable=true)
     * @Groups({"orders:read", "fulfillments:read"})
     */
    public $packed;

    /**
     * @var decimal Packaging fee.
     *
     * @ORM\Column(name="packaging_fee", type="decimal", precision=4, scale=2, options={"default": 0.00})
     * @Groups({"orders:read", "fulfillments:read", "fulfillments:write"})
     */
    public $packaging_fee;

    /**
     * @var decimal Packing slip fee.
     *
     * @ORM\Column(name="packing_slip_fee", type="decimal", precision=3, scale=2, options={"default": 0.00})
     * @Groups({"orders:read", "fulfillments:read", "fulfillments:write"})
     */
    public $packing_slip_fee;

    /**
     * @var string The Service used
     *
     * @ORM\Column(name="service", type="string", length=16)
     * @Groups({"orders:read", "fulfillments:read", "fulfillments:write"})
     * @Assert\Length(max=16)
     * @Assert\Choice(choices={"BoxC Post", "BoxC Parcel", "BoxC Plus", "BoxC Priority"}, message="Service must be one of these options: BoxC Post, BoxC Parcel, BoxC Plus, BoxC Priority")
     */
    public $service;

    /**
     * @ORM\OneToOne(targetEntity=Shipments::class)
     * @Groups({"fulfillments:read", "fulfillments:write"})
     * @SerializedName("shipment_id")
     */
    public $shipment;

    /**
     * @var int Shipment id without the other fields.
     * @Groups({"orders:read"})
     */
    public $shipment_id;

    /**
     * @var decimal Shipping cost.
     *
     * @ORM\Column(name="shipping_cost", type="decimal", precision=6, scale=2, options={"default": 0.00})
     * @Groups({"orders:read", "fulfillments:read", "fulfillments:write"})
     */
    public $shipping_cost;

    /**
     * @var string|null The tracking number for this fulfillment. Default is null. Set by the system.
     *
     * @ORM\Column(name="tracking_number", type="string", length=40, nullable=true)
     * @Groups({"orders:read", "fulfillments:read", "fulfillments:write"})
     * @Assert\Length(max=40)
     */
    public $tracking_number;

    /**
     * @var string|null tracking_url
     *
     * @ORM\Column(name="tracking_url", type="string", length=128, nullable=true)
     * @Groups({"orders:read", "fulfillments:read", "fulfillments:write"})
     * @Assert\Length(max=128)
     */
    public $tracking_url;

    /**
     * @ORM\ManyToOne(targetEntity=Warehouses::class)
     * @Groups({"fulfillments:read", "fulfillments:write"})
     * @SerializedName("wh_id")
     */
    public $wh;

    /**
     * @ORM\ManyToOne(targetEntity=WarehousesWaves::class)
     * @Groups({"fulfillments:read", "fulfillments:write"})
     * @SerializedName("wave_id")
     */
    public $wave;

    /**
     * @var string|null wh_location
     *
     * @ORM\Column(name="wh_location", type="string", length=8, options={"fixed" = true}, nullable=true)
     * @Groups({"fulfillments:read", "fulfillments:write"})
     * @Assert\Length(max=8)
     */
    public $wh_location;

    /**
     * @var decimal Packaging weight.
     *
     * @ORM\Column(name="packaging_weight", type="decimal", precision=5, scale=3, options={"default": 0.000})
     * @Groups({"fulfillments:read", "fulfillments:write"})
     */
    public $packaging_weight = 0.00;

    /**
     * @var int packaging_volume
     *
     * @ORM\Column(name="packaging_volume", type="integer")
     * @Groups({"fulfillments:read", "fulfillments:write"})
     */
    public $packaging_volume = 0;

    /**
     * @var int Sent to Shop flag.
     *
     * @ORM\Column(name="sent_to_shop", type="boolean", options={"unsigned"=true, "default"=0})
     * @Groups({"fulfillments:read", "fulfillments:write"})
     */
    public $sent_to_shop = 0;

    public function __construct() {
        $this->created = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getShipmentId()
    {
        if (isset($this->shipment) && is_object($this->shipment)) {
            return $this->shipment->getId();
        } else {
            return null;
        }
    }

    public function getFulfilled()
    {
        if (isset($this->fulfilled) && !empty($this->fulfilled)) {
            return $this->fulfilled;
        } else {
            return null;
        }
    }
}
