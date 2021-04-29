<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Entity\EntryPoints;
use App\Entity\Manifests;
use App\Entity\Shipments;
use App\Entity\Helper\Service;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiFilter;
use App\Filter\OrderByFixedPropertyFilter;
use App\Filter\DateInclusiveFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;

/**
 *
 * @ApiResource(
 *     collectionOperations={
 *         "get"={
 *             "normalization_context"={"skip_null_values" = false, "groups"={"overpacks:read", "overpacks:collection:get"}}
 *          },
 *         "post"={
 *             "denormalization_context"={"groups"={"overpacks:write"}, "validation_groups"={"postv"}}
 *         }
 *     },
 *     itemOperations={
 *         "get"={
 *             "normalization_context"={"skip_null_values" = false, "groups"={"overpacks:read", "overpacks:readid", "overpacks:item:get"}}
 *         },
 *         "delete"={
 *             "security"="not object.getManifestId()",
 *             "security_message"="Manifested overpacks cannot be deleted."
 *         },
 *         "put"={
 *             "security"="not object.getManifestId()",
 *             "security_message"="Manifested overpacks cannot be updated.",
 *             "denormalization_context"={"groups"={"overpacks:putwrite"}}
 *         },
 *         "patch"={
 *             "denormalization_context"={"groups"={"overpacks:patchwrite"}}
 *         }
 *     },
 *     normalizationContext={"skip_null_values" = false, "groups"={"overpacks:read"}},
 *     attributes={"route_prefix"="/v2"}
 * )
 * @ORM\Entity
 * @ORM\Table(name="cartons")
 * @ApiFilter(OrderByFixedPropertyFilter::class, arguments={"orderParameterName"="sort"}, properties={"id": "desc"})
 * @ApiFilter(DateInclusiveFilter::class, properties={"created"})
 * @ApiFilter(SearchFilter::class, properties={"ep.id": "partial"})
 */
class Overpacks
{
    /**
     * @var string|null Name of Carrier
     *
     * @Assert\Type("string")
     * @Assert\Length(max=16)
     * @ORM\Column(name="carrier", type="string", length=16, nullable=true)
     * @Groups({"overpacks:read", "overpacks:write", "overpacks:putwrite"})
     */
    public $carrier;

    /**
     * @var int Contains DG.
     *
     * @Assert\Type("integer")
     * @Assert\Choice({0,1})
     * @ORM\Column(name="contains_dg", type="smallint", options={"unsigned"=true, "default"=0})
     * @Groups({"overpacks:read", "overpacks:write"})
     */
    public $contains_dg = 0;

    /**
     * @var \DateTimeInterface Creation Date
     *
     * @ORM\Column(name="created", type="datetime", options={"default": "CURRENT_TIMESTAMP"})
     * @Groups({"overpacks:read"})
     */
    private $created;

    /**
     * @var string|null The Destination
     *
     * @Assert\Type("string")
     * @Assert\Length(max=3)
     * @ORM\Column(name="destination", type="string", length=3, options={"fixed" = true}, nullable=true)
     * @Groups({"overpacks:read", "overpacks:write"})
     */
    public $destination;

    /**
     * @ORM\ManyToOne(targetEntity=EntryPoints::class)
     * @Groups({"overpacks:write", "overpacks:putwrite", "manifests:readabbr", "shipments:read", "shipments:write"})
     * @SerializedName("entry_point")
     */
    public $ep;

    /**
     * @var string Non-IRI Entry Point value
     *
     * @Assert\Type("string")
     * @Assert\Length(min=6,max=6)
     * @Groups({"overpacks:read", "manifests:read"})
     * @SerializedName("entry_point")
     */
    public $ep_plain;

    /**
     * @var string|null External tracking
     *
     * @Assert\Type("string")
     * @Assert\Length(max=40)
     * @ORM\Column(name="external_tracking", type="string", length=40, nullable=true)
     * @Groups({"overpacks:read", "overpacks:write"})
     */
    public $external_tracking;

    /**
     * @var int Height of the Overpack
     *
     * @ORM\Column(name="height", type="smallint", options={"unsigned":true, "default": 0})
     * @Groups({"overpacks:read", "overpacks:putwrite", "manifests:read"})
     * @Assert\Type("integer")
     * @Assert\GreaterThan(-1)
     * @Assert\Length(max=4, maxMessage="Height is above the limit.")
     */
    public $height = 0;

    /**
     * @var int The entity Id
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(name="id", type="bigint", options={"unsigned"=true})
     * @Groups({"overpacks:read", "manifests:read", "shipments:read", "shipments:write", "manifests:readabbr", "manifests:writeabbr", "overpacks:readid"})
     */
    private $id;

    /**
     * @var int Length of the Overpack
     *
     * @Assert\Type("integer")
     * @Assert\GreaterThan(-1)
     * @Assert\Length(max=4, maxMessage="Height is above the limit.")
     * @ORM\Column(name="length", type="smallint", options={"unsigned":true, "default": 0})
     * @Groups({"overpacks:read", "overpacks:putwrite", "manifests:read"})
     */
    public $length = 0;

    /**
     * @ORM\ManyToOne(targetEntity=Manifests::class, inversedBy="overpacks")
     * @Groups({"manifests:write"})
     */
    private $manifest;

    /**
     * @var int|null The Manifest
     *
     * @Groups({"overpacks:read"})
     */
    public $manifest_id = null;

    /**
     * @var string|null Route heading
     *
     * @Assert\Type("string")
     * @Assert\Length(max=32)
     * @ORM\Column(name="route_heading", type="string", length=32, nullable=true)
     * @Groups({"overpacks:read", "overpacks:write"})
     */
    public $route_heading;

    /**
     * @var string|null Route subheading
     *
     * @Assert\Type("string")
     * @Assert\Length(max=64)
     * @ORM\Column(name="route_subheading", type="string", length=64, nullable=true)
     * @Groups({"overpacks:read", "overpacks:write"})
     */
    public $route_subheading;

    /**
     * @var string|null The Service
     *
     * @ORM\Column(name="service", type="string", length=16, options={"default"="BoxC Parcel"}, nullable=true)
     * @Groups({"overpacks:read", "overpacks:write", "overpacks:putwrite", "overpacks:patchwrite", "manifests:read"})
     * @Assert\Type("string", message="Service must be a string.")
     * @Assert\Choice(callback="getServicesList", groups={"postv"}, message="Service must be one of these options: BoxC Post, BoxC Parcel, BoxC Plus, BoxC Priority")
     */
    public $service;

    /**
     * @ORM\OneToMany(targetEntity=Shipments::class, mappedBy="carton", cascade={"remove"})
     * @Groups({"overpacks:item:get", "overpacks:patchwrite", "overpacks:read", "shipments:read", "shipments:write"})
     */
    public $shipments;

    /**
     * New shipments for PATCH operation.
     *
     * @ORM\OneToMany(targetEntity=Shipments::class, mappedBy="carton")
     * @Groups({"overpacks:patchwrite", "shipments:read", "shipments:write"})
     */
    public $new_shipments;

    /**
     * @var int[]|null Ids to be removed in PATCH operation
     *
     * @Groups({"overpacks:patchwrite"})
     */
    public $patch_shipments_remove;

    /**
     * @var string|null The Incoterms for this shipment. Options are: DDU (default), DDP
     *
     * @ORM\Column(name="terms", type="string", length=3, options={"fixed"=true, "default"="DDU"}, nullable=true)
     * @Groups({"shipments:read", "shipments:write"})
     * @Assert\Type("string")
     * @Assert\Choice(choices={"", "DDU", "DDP"}, message="Terms must be either DDU or DDP")
     */
    public $terms = "DDU";

    /**
     * @var int Count of shipments.
     *
     * @Groups({"overpacks:collection:get"})
     */
    public $total_shipments = 0;

    /**
     * @var int Type 86.
     *
     * @ORM\Column(name="type86", type="smallint", options={"unsigned"=true, "default"=0})
     * @Groups({"overpacks:read", "overpacks:write"})
     * @Assert\Type("integer")
     * @Assert\Choice({0,1})
     */
    public $type86 = 0;

    /**
     * @var int Weight of the Overpack
     *
     * @ORM\Column(name="weight", type="smallint", precision=5, scale=1, options={"default":0})
     * @Groups({"overpacks:read", "overpacks:putwrite", "manifests:read"})
     * @Assert\Type("integer")
     * @Assert\GreaterThan(-1)
     * @Assert\Length(max=5, maxMessage="Weight is above the limit.")
     */
    public $weight = 0;

    /**
     * @var int Width of the Overpack
     *
     * @ORM\Column(name="width", type="smallint", options={"unsigned":true, "default": 0})
     * @Groups({"overpacks:read", "overpacks:putwrite", "manifests:read"})
     * @Assert\Type("integer")
     * @Assert\GreaterThan(-1)
     * @Assert\Length(max=4, maxMessage="Width is above the limit.")
     */
    public $width = 0;

    /**
     * @ORM\ManyToOne(targetEntity=Users::class, inversedBy="overpacks")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     * @Groups({"overpacks:write"})
     * @SerializedName("user_id")
     */
    private $user;

    public function __construct() {
        $this->created = new \DateTimeImmutable();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCreated(): ?\DateTimeInterface {
        return $this->created;
    }

    public function getManifest(): ?Manifests
    {
        return $this->manifest;
    }

    public function getManifestId(): ?int
    {
        if (isset($this->manifest)) {
            $this->manifest_id = $this->manifest->getId();
        } else {
            $this->manifest_id = null;
        }

        return $this->manifest_id;
    }

    public function setManifest(?Manifests $manifest): self
    {
        // Verify that the user of Overpacks and Shipments match.
        // TODO: not working for POST where user_id has not yet been entered into Manifests table.
        $same_user = true; //$this->getUser()->getId() == $manifest->getUser()->getId();

        if ($same_user) {
            $this->manifest = $manifest;
        } else {
            // TODO: throw Exception.
        }

        return $this;
    }

    /**
     * @return Collection|Shipments[]
     */
    public function getShipments(): Collection
    {
        return $this->shipments;
    }

    public function getTotalShipments(): int
    {
        $coll = $this->getShipments();

        return count($coll);
    }

    public function setNewShipments($shipments): self
    {
        foreach ($shipments as $shipment) {
            $this->addShipment($shipment);
        }

        return $this;
    }

    public function addShipment(Shipments $shipment): self
    {
        // Verify that the user of Overpacks and Shipments match.
        $same_user = $this->getUser()->getId() == $shipment->getUser()->getId();

        if (!$this->shipments->contains($shipment) && $same_user) {
            $this->shipments[] = $shipment;
            $shipment->setCarton($this);
        }

        return $this;
    }

    public function removeShipment(Shipments $shipment): self
    {
        if ($this->shipments->contains($shipment)) {
            $this->shipments->removeElement($shipment);
            // set the owning side to null (unless already changed)
            if ($shipment->getCarton() === $this) {
                $shipment->setCarton(null);
            }
        }

        return $this;
    }

    /**
     * @param $patch_shipments_remove
     * @return $this
     *
     * Removes shipments by Id in PATCH operation.
     */
    public function setPatchShipmentsRemove($patch_shipments_remove) :self
    {
        $shipments = $this->getShipments();
        foreach ($shipments as $shipment) {
            $shipid = '/v2/shipments/' . $shipment->getId();
            foreach ($patch_shipments_remove as $sid) {
               if ($shipid == $sid) {
                   $this->removeShipment($shipment);
               }
            }
        }

        return $this;
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

    public function getUser(): Users
    {
        return $this->user;
    }

    public function setUser(Users $user): self
    {
        $this->user = $user;

        return $this;
    }

    public static function getServicesList() {
        return Service::getServicesList();
    }
}
