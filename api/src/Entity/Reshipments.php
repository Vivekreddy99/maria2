<?php
// api/src/Entity/Reshipments.php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Entity\EntryPoints;
use App\Entity\Helper\LineItems;
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
 *         "delete",
 *         "put"
 *     },
 *     normalizationContext={"groups"={"reshipments:read"}},
 *     denormalizationContext={"groups"={"reshipments:write"}},
 *     attributes={"route_prefix"="/v2"}
 * )
 * @ORM\Entity
 */
class Reshipments
{
    /**
     * @var int The entity Id
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(name="id", type="bigint", options={"unsigned"=true})
     * @Groups({"reshipments:read", "reshipments:write"})
     */
    public $id;

    /**
     * @ORM\ManyToOne(targetEntity=Users::class, inversedBy="reshipments")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     * @Groups({"reshipments:write"})
     * @SerializedName("user_id")
     */
    private $user;

    /**
     * @var string status
     *
     * @ORM\Column(type="string")
     */
    public $status;

    /**
     * @var decimal The Estimated Reship Fee (3.58)
     *
     * @ORM\Column(name="estimated_reship_fee", type="decimal", precision=5, scale=2, options={"default": 0.0})
     * @Groups({"reshipments:read", "reshipments:write"})
     */
    public $estimated_reship_fee;

    /**
     * @var decimal The Reship Fee (3.58)
     *
     * @ORM\Column(name="reship_fee", type="decimal", precision=5, scale=2, options={"default": 0.0})
     * @Groups({"reshipments:read", "reshipments:write"})
     */
    public $reship_fee;

    /**
     * @var decimal The Weight
     *
     * @ORM\Column(name="weight", type="decimal", precision=7, scale=3, options={"unsigned"=true}, nullable=true)
     * @Groups({"reshipments:read", "reshipments:write"})
     */
    public $weight;

    /**
     * @var string|null The tracking number for this shipment. Default is null. Set by the system.
     *
     * @ORM\Column(name="tracking_number", type="string", length=40, nullable=true)
     * @Groups({"reshipments:read", "reshipments:write"})
     * @Assert\Length(max=40)
     */
    public $tracking_number;

    /**
     * @var string|null The Service used
     *
     * @ORM\Column(name="service", type="string", length=16)
     * @Groups({"shipments:read", "shipments:write"})
     * @Assert\Length(max=16)
     * @Assert\Choice(choices={"BoxC Post", "BoxC Parcel", "BoxC Plus", "BoxC Priority"}, message="Service must be one of these options: BoxC Post, BoxC Parcel, BoxC Plus, BoxC Priority")
     */
    public $service;

    /**
     * @var string The From Name
     *
     * @ORM\Column(name="from_name", type="string", length=40)
     * @Groups({"reshipments:read", "reshipments:write"})
     */
    public $from_name;

    /**
     * @var string|null The "To" Company
     *
     * @ORM\Column(name="to_company", type="string", length=40, nullable=true)
     * @Groups({"reshipments:read", "reshipments:write"})
     */
    public $to_company;

    /**
     * @var string The "To" Name
     *
     * @ORM\Column(name="to_name", type="string", length=40)
     * @Groups({"reshipments:read", "reshipments:write"})
     */
    public $to_name;

    /**
     * @var string|null The "To" Phone
     *
     * @ORM\Column(name="to_phone", type="string", length=20, nullable=true)
     * @Groups({"reshipments:read", "reshipments:write"})
     */
    public $to_phone;

    /**
     * @var string The "To" Street 1
     *
     * @ORM\Column(name="to_street1", type="string", length=40)
     * @Groups({"reshipments:read", "reshipments:write"})
     */
    public $to_street1;

    /**
     * @var string|null The "To" Street 2
     *
     * @ORM\Column(name="to_street2", type="string", length=40, nullable=true)
     * @Groups({"reshipments:read", "reshipments:write"})
     */
    public $to_street2;

    /**
     * @var string The "To" City
     *
     * @ORM\Column(name="to_city", type="string", length=40)
     * @Groups({"reshipments:read", "reshipments:write"})
     */
    public $to_city;

    /**
     * @var string|null The "To" Province
     *
     * @ORM\Column(name="to_province", type="string", length=40, nullable=true)
     * @Groups({"reshipments:read", "reshipments:write"})
     */
    public $to_province;

    /**
     * @var string|null The "To" Postal Code
     *
     * @ORM\Column(name="to_postal_code", type="string", length=10, nullable=true)
     * @Groups({"reshipments:read", "reshipments:write"})
     */
    public $to_postal_code;

    /**
     * @var string The To Address Country
     *
     * @ORM\Column(name="to_country", type="string", length=2, options={"fixed" = true})
     * @Groups({"shipments:read", "shipments:write"})
     * @Assert\Choice(callback="getCountryIsoCodes", message="The country of origin or where the product was manufactured must be in the ISO 3166-1 alpha-2 format.")
     */
    public $to_country;

    /**
     * @var decimal The Value (3.58)
     *
     * @ORM\Column(name="value", type="decimal", precision=6, scale=2, options={"default": 0.0})
     * @Groups({"products:read", "products:write"})
     */
    public $value;

    /**
     * @var string The Contents
     *
     * @ORM\Column(name="contents", type="string", length=60)
     * @Groups({"reshipments:read", "reshipments:write"})
     */
    public $contents;

    /**
     * @var \DateTimeInterface Creation Date
     *
     * @ORM\Column(name="created", type="datetime", options={"default": "CURRENT_TIMESTAMP", "on update": "CURRENT_TIMESTAMP"})
     * @Groups({"reshipments:read"})
     */
    public $created;

    /**
     * @var int Reshipped flag
     *
     * @ORM\Column(name="reshipped", type="boolean", options={"unsigned"=true, "default"=0})
     * @Groups({"reshipments:read", "reshipments:write"})
     */
    public $reshipped;

    /**
     * @var int Invoiced flag
     *
     * @ORM\Column(name="invoiced", type="boolean", options={"unsigned"=true, "default"=0})
     * @Groups({"reshipments:read", "reshipments:write"})
     */
    public $invoiced = 0;

    public function getId(): ?int
    {
        return $this->id;
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

    public static function getCountryIsoCodes() {
        return Shipments::getCountryIsoCodes();
    }
}
