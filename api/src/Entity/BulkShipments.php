<?php
// api/src/Entity/BulkShipments.php

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
 *         "delete",
 *         "put",
 *         "patch"
 *     },
 *     normalizationContext={"groups"={"bulk_shipments:read"}},
 *     denormalizationContext={"groups"={"bulk_shipments:write"}},
 *     attributes={"route_prefix"="/v2"}
 * )
 * @ORM\Entity
 */
class BulkShipments
{
    /**
     * @var int The entity Id
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(name="id", type="integer", options={"unsigned"=true})
     * @Groups({"bulk_shipments:read", "bulk_shipments:write"})
     */
    private $id;

    /**
     * @var string Ref
     *
     * @ORM\Column(name="ref", type="string", length=32, nullable=true)
     * @Groups({"bulk_shipments:read", "bulk_shipments:write"})
     * @Assert\Length(max=32)
     */
    public $ref;

    /**
     * @var string Mode
     *
     * @ORM\Column(name="mode", type="string", length=16, nullable=true)
     * @Groups({"bulk_shipments:read", "bulk_shipments:write"})
     * @Assert\Length(max=16)
     */
    public $mode;

    /**
     * @var string|null Inbound Tracking Number
     *
     * @ORM\Column(name="inbound_tracking", type="string", length=40, nullable=true)
     * @Groups({"bulk_shipments:read", "bulk_shipments:write"})
     * @Assert\Length(max=40)
     */
    public $inbound_tracking;

    /**
     * @var string|null Outbound Tracking Number.
     *
     * @ORM\Column(name="outbound_tracking", type="string", length=40, nullable=true)
     * @Groups({"bulk_shipments:read", "bulk_shipments:write"})
     * @Assert\Length(max=40)
     */
    public $outbound_tracking;

    /**
     * @var decimal Weight of the bulk shipment.
     *
     * @ORM\Column(name="weight", type="decimal", precision=7, scale=3, options={"unsigned"=true})
     * @Groups({"bulk_shipments:read", "bulk_shipments:write"})
     */
    public $weight = 0;

    /**
     * @var decimal Processed Weight
     *
     * @ORM\Column(name="processed_weight", type="decimal", precision=7, scale=3, options={"unsigned"=true, "default"="0.000"})
     * @Groups({"bulk_shipments:read", "bulk_shipments:write"})
     */
    public $processed_weight = 0;

    /**
     * @var decimal Volume
     *
     * @ORM\Column(name="volume", type="decimal", precision=6, scale=2, options={"unsigned"=true})
     * @Groups({"bulk_shipments:read", "bulk_shipments:write"})
     */
    public $volume;

    /**
     * @var decimal processed_volume
     *
     * @ORM\Column(name="processed_volume", type="decimal", precision=6, scale=2, options={"unsigned"=true, "default"=0.00})
     * @Groups({"bulk_shipments:read", "bulk_shipments:write"})
     */
    public $processed_volume;

    /**
     * @var string Duty.
     *
     * @ORM\Column(name="duty", type="string", length=3, options={"fixed" = true})
     * @Groups({"bulk_shipments:read", "bulk_shipments:write"})
     * @Assert\Length(max=3)
     */
    public $duty;

    /**
     * @var int Insurance flag.
     *
     * @ORM\Column(name="insurance", type="boolean", options={"unsigned"=true, "default"=0})
     * @Groups({"bulk_shipments:read", "bulk_shipments:write"})
     */
    public $insurance = 0;

    /**
     * @var string The To Address Postal Code
     *
     * @ORM\Column(name="to_postal_code", type="string", length=10, nullable=true)
     * @Groups({"bulk_shipments:read", "bulk_shipments:write"})
     * @Assert\Length(max=10)
     */
    public $to_postal_code;

    /**
     * @var string The To Address country code.
     *
     * @ORM\Column(name="to_country", type="string", length=2, options={"fixed" = true})
     * @Groups({"bulk_shipments:read", "bulk_shipments:write"})
     * @Assert\Choice(callback="getCountryIsoCodes", message="The country of origin or where the product was manufactured must be in the ISO 3166-1 alpha-2 format.")
     */
    public $to_country;

    /**
     * @var string Description.
     *
     * @ORM\Column(name="description", type="string", length=255, nullable=true)
     * @Groups({"bulk_shipments:read", "bulk_shipments:write"})
     * @Assert\Length(max=255)
     */
    public $description;

    /**
     * @var string|null status
     *
     * @ORM\Column(name="status", type="string", length=16, options={"fixed" = true})
     * @Groups({"bulk_shipments:read", "bulk_shipments:write"})
     * @Assert\Length(max=16)
     */
    public $status;

    /**
     * @var \DateTimeInterface Creation Date
     *
     * @ORM\Column(name="created", type="datetime", options={"default": "CURRENT_TIMESTAMP", "on update": "CURRENT_TIMESTAMP"})
     * @Groups({"bulk_shipments:read"})
     */
    public $created;

    /**
     * @var string|null Cartons.
     *
     * @ORM\Column(name="cartons", type="json", nullable=true)
     * @Groups({"bulk_shipments:read", "bulk_shipments:write"})
     */
    public $cartons;

    /**
     * @var string|null DG codes.
     *
     * @ORM\Column(name="dg_codes", type="json", nullable=true)
     * @Groups({"bulk_shipments:read", "bulk_shipments:write"})
     */
    public $dg_codes;

    /**
     * @var string|null Files.
     *
     * @ORM\Column(name="files", type="json", nullable=true)
     * @Groups({"bulk_shipments:read", "bulk_shipments:write"})
     */
    public $files;

    public function getId(): ?int
    {
        return $this->id;
    }

    public static function getCountryIsoCodes() {
        return Shipments::getCountryIsoCodes();
    }
}
