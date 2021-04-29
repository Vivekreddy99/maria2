<?php
// api/src/Entity/Labels.php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Entity\EntryPoints;
use App\Entity\Shipments;
use App\Entity\Manifests;
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
 *
 * @ApiResource(
 *     collectionOperations={
 *         "get",
 *         "post"
 *     },
 *     itemOperations={
 *         "get",
 *         "put"
 *     },
 *     normalizationContext={"groups"={"labels:read"}},
 *     denormalizationContext={"groups"={"labels:write"}},
 *     attributes={"route_prefix"="/v2"}
 * )
 * @ORM\Entity
 */
class Labels
{
    /**
     * @var decimal The Billed Cost (3.58)
     *
     * @ORM\Column(name="billed_cost", type="decimal", precision=12, scale=2, options={"default": 0.0})
     * @Groups({"labels:read"})
     */
    public $billed_cost = 0.0;

    /**
     * @var decimal The Billed Weight
     *
     * @ORM\Column(name="billed_weight", type="decimal", precision=7, scale=3, options={"unsigned"=true}, nullable=true)
     * @Groups({"labels:read", "labels:write"})
     */
    private $billed_weight;

    /**
     * @var int Canceled flag
     *
     * @ORM\Column(name="canceled", type="boolean", options={"unsigned"=true, "default"=0})
     * @Groups({"labels:read", "labels:write"})
     */
    public $canceled = 0;

    /**
     * @var string Name of Carrier
     *
     * @ORM\Column(name="carrier", type="string", length=16, nullable=true)
     * @Groups({"labels:read", "labels:write"})
     */
    public $carrier;

    /**
     * @var decimal The Chargeable Weight
     *
     * @ORM\Column(name="chargeable_weight", type="decimal", precision=7, scale=3, options={"unsigned"=true}, nullable=true)
     * @Groups({"labels:read", "labels:write"})
     */
    private $chargeable_weight;

    /**
     * @var decimal The Cost (3.58)
     *
     * @ORM\Column(name="cost", type="decimal", precision=12, scale=2, options={"default": 0.0})
     * @Groups({"labels:read"})
     */
    public $cost = 0.0;

    /**
     * @var date|null Customs In Date
     *
     * @ORM\Column(name="customs_in_date", type="date", nullable=true)
     * @Groups({"labels:read"})
     */
    public $customs_in_date;

    /**
     * @var date|null Customs Out Date
     *
     * @ORM\Column(name="customs_out_date", type="date", nullable=true)
     * @Groups({"labels:read"})
     */
    public $customs_out_date;

    /**
     * @var datetime Creation Date
     *
     * @ORM\Column(name="created", type="datetime", options={"default": "CURRENT_TIMESTAMP", "on update": "CURRENT_TIMESTAMP"})
     * @Groups({"labels:read"})
     */
    public $created;

    /**
     * @var date|null delivery_date
     *
     * @ORM\Column(name="delivery_date", type="date", nullable=true)
     * @Groups({"labels:read"})
     */
    public $delivery_date;

    /**
     * @var string The Destination
     *
     * @ORM\Column(name="destination", type="string", length=3, options={"fixed" = true}, nullable=true)
     * @Groups({"labels:read", "labels:write"})
     * @SerializedName("exit_point")
     */
    public $destination;

    /**
     * @var int Event Code
     *
     * @ORM\Column(name="event_code", type="smallint", options={"unsigned":true, "default": null}, nullable=true)
     * @Groups({"labels:read"})
     */
    public $event_code;

    /**
     * @var date|null First Attempt Date
     *
     * @ORM\Column(name="first_attempt_date", type="date", nullable=true)
     * @Groups({"labels:read"})
     */
    public $first_attempt_date;

    /**
     * @var int The entity Id
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(name="id", type="bigint", options={"unsigned"=true})
     * @Groups({"labels:read", "labels:write", "shipments:read", "shipments:write"})
     */
    private $id;

    /**
     * @var int Invoiced flag
     *
     * @ORM\Column(name="invoiced", type="boolean", options={"unsigned"=true, "default"=0})
     * @Groups({"labels:read", "labels:write"})
     */
    public $invoiced = 0;

    /**
     * @var int Is ready flag.
     *
     * @ORM\Column(name="is_ready", type="boolean", options={"unsigned"=true, "default"=0})
     * @Groups({"labels:read"})
     */
    public $is_ready = 0;

    /**
     * @var date|null LM Scan Date
     *
     * @ORM\Column(name="lm_scan_date", type="date", nullable=true)
     * @Groups({"labels:read"})
     */
    public $lm_scan_date;

    /**
     * @var string|null Metadata
     *
     * @ORM\Column(name="metadata", type="json", nullable=true)
     * @Groups({"labels:read", "labels:write"})
     */
    public $metadata;

    /**
     * @var decimal The Override Fee (3.58)
     *
     * @ORM\Column(name="override_fee", type="decimal", precision=3, scale=2, options={"default": 0.0})
     * @Groups({"labels:read", "labels:write"})
     */
    public $override_fee = 0.0;

    /**
     * @var decimal The Oversize Fee (3.58)
     *
     * @ORM\Column(name="oversize_fee", type="decimal", precision=5, scale=2, options={"default": 0.0})
     * @Groups({"labels:read", "labels:write"})
     */
    public $oversize_fee = 0.0;

    /**
     * @var int Processed flag
     *
     * @ORM\Column(name="processed", type="boolean", options={"unsigned"=true, "default"=0})
     * @Groups({"labels:read", "labels:write"})
     */
    public $processed = 0;

    /**
     * @var string|null The Service used
     *
     * @ORM\Column(name="service", type="string", length=16, nullable=true)
     * @Groups({"labels:read", "labels:write"})
     * @Assert\Length(max=16)
     * @Assert\Choice(choices={"BoxC Post", "BoxC Parcel", "BoxC Plus", "BoxC Priority"}, message="Service must be one of these options: BoxC Post, BoxC Parcel, BoxC Plus, BoxC Priority")
     */
    public $service;

    /**
     * @ORM\ManyToOne(targetEntity=Shipments::class, inversedBy="labels")
     * @Groups({"shipments:write", "labels:write"})
     * @SerializedName("shipment_id")
     */
    private $shipment;

    /**
     * @var int|null
     *
     * @Groups({"labels:read"})
     */
    public $shipment_id;

    // Placeholder for Status field.

    /**
     * @var decimal The Tax (3.58)
     *
     * @ORM\Column(name="tax", type="decimal", precision=5, scale=2, options={"default": 0.0})
     * @Groups({"labels:read", "labels:write"})
     */
    public $tax = 0.0;

    /**
     * @var string|null The tracking number for this shipment. Default is null. Set by the system.
     *
     * @ORM\Column(name="tracking_number", type="string", length=40, nullable=true)
     * @Groups({"labels:read", "labels:write"})
     * @Assert\Length(max=40)
     */
    public $tracking_number;

    public function getId(): ?int
    {
        return $this->id;
    }
}
