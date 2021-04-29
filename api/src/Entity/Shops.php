<?php
// api/src/Entity/Shops.php

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
 *         "put"
 *     },
 *     normalizationContext={"groups"={"shops:read"}},
 *     denormalizationContext={"groups"={"shops:write"}},
 *     attributes={"route_prefix"="/v2"}
 * )
 * @ORM\Entity
 */
class Shops
{
    /**
     * @var int Active flag.
     *
     * @ORM\Column(name="active", type="boolean", options={"unsigned"=true, "default"=1})
     * @Groups({"shops:read", "shops:write"})
     */
    public $active = 1;

    /**
     * @var string The entity Id
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="string", length=32, options={"fixed" = true})
     * @Groups({"shops:read", "shops:write", "orders:read"})
     * @Assert\Length(min=3, max=32)
     * @Assert\Regex(
     *     pattern="/^[A-Za-z0-9_-]+$/",
     *     message="Your shop id may only use the following characters: A-Za-z0-9-_"
     * )
     */
    public $id;

    /**
     * @ORM\ManyToOne(targetEntity=Users::class, inversedBy="shops")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     * @Groups({"shops:write"})
     * @SerializedName("user_id")
     */
    private $user;

    /**
     * @ORM\OneToMany(targetEntity=Orders::class, mappedBy="shop", cascade={"remove"})
     * @Groups({"orders:write", "products:read"})
     */
    public $orders;

    /**
     * @ORM\OneToMany(targetEntity=ProductsSkus::class, mappedBy="shop", cascade={"remove"})
     * @Groups({"shops:write"})
     * @SerializedName("skus")
     */
    public $products_skus;

    /**
     * @var string Name
     *
     * @ORM\Column(name="name", type="string", length=64)
     * @Groups({"shops:read", "shops:write", "orders:read"})
     * @Assert\NotBlank
     * @Assert\Length(min=3, max=64)
     */
    public $name;

    /**
     * @var string|null Type
     *
     * @ORM\Column(name="type", type="string", length=16, nullable=true)
     * @Groups({"shops:read", "shops:write", "orders:read"})
     * @Assert\Length(max=16)
     */
    public $type;

    /**
     * @var string|null M1
     *
     * @ORM\Column(name="m1", type="string", length=128, nullable=true)
     * @Groups({"shops:read", "shops:write"})
     * @Assert\Length(max=128)
     */
    private $m1;

    /**
     * @var string|null M2
     *
     * @ORM\Column(name="m2", type="string", length=128, nullable=true)
     * @Groups({"shops:read", "shops:write"})
     * @Assert\Length(max=128)
     */
    private $m2;

    /**
     * @var int Connected flag.
     *
     * @ORM\Column(name="connected", type="boolean", options={"unsigned"=true, "default"=0})
     * @Groups({"shops:read"})
     */
    private $connected = 0;

    /**
     * @var int Default Partial flag.
     *
     * @ORM\Column(name="default_partial", type="boolean", options={"unsigned"=true, "default"=0})
     * @Groups({"shops:read", "shops:write"})
     */
    public $default_partial = 0;

    /**
     * @var string default_service
     *
     * @ORM\Column(name="default_service", type="string", length=16, options={"default"="BoxC Parcel"})
     * @Groups({"shops:read", "shops:write"})
     * @Assert\Length(max=16)
     * @Assert\Choice(choices={"BoxC Post", "BoxC Parcel", "BoxC Plus", "BoxC Priority"}, message="Default service must be one of these options: BoxC Post, BoxC Parcel, BoxC Plus, BoxC Priority")
     */
    public $default_service = 'BoxC Parcel';

    /**
     * @var int The number of hours an order should remain unprocessed in the system before packing. Orders will be processed if their created value plus the shop's delay_processing value is greater than the current time. Maximum: 240.
     *
     * @ORM\Column(name="delay_processing", type="smallint", options={"unsigned"=true, "default"=0})
     * @Groups({"shops:read", "shops:write"})
     * @Assert\LessThanOrEqual(
     *     value = 240
     * )
     */
    public $delay_processing = 0;

    /**
     * @var int Packing Slip flag.
     *
     * @ORM\Column(name="packing_slip", type="boolean", options={"unsigned"=true, "default"=0})
     * @Groups({"shops:read", "shops:write"})
     */
    public $packing_slip;

    /**
     * @var \DateTimeInterface|null expires
     *
     * @ORM\Column(name="expires", type="datetime", nullable=true)
     * @Groups({"shops:read", "shops:write"})
     */
    private $expires;

    /**
     * @var int|null shopify_location_id
     *
     * @ORM\Column(name="shopify_location_id", type="bigint", options={"unsigned"=true}, nullable=true)
     * @Groups({"shops:read", "shops:write"})
     */
    private $shopify_location_id;

    /**
     * @Groups({"shops:read"})
     */
    public $settings;

    /**
     * @var int Test flag.
     *
     * @ORM\Column(name="test", type="boolean", options={"unsigned"=true, "default"=0})
     * @Groups({"shops:read", "shops:write"})
     */
    public $test = 0;

    /**
     * @var \DateTimeInterface Creation Date
     *
     * @ORM\Column(name="created", type="datetime", options={"default": "CURRENT_TIMESTAMP"})
     * @Groups({"shops:read"})
     */
    public $created;

    public function __construct($packing_slip = false, $default_service = 'BoxC Parcel') {
        $this->created = new \DateTimeImmutable();
        $this->packing_slip = $packing_slip;
        $this->default_service = $default_service;
    }

    public function getId(): ?string
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

    public function setId($id) {
        $this->id = $id;
    }

}
