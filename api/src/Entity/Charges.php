<?php
// api/src/Entity/Charges.php

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
 *     normalizationContext={"groups"={"charges:read"}},
 *     denormalizationContext={"groups"={"charges:write"}},
 *     attributes={"route_prefix"="/v2"}
 * )
 * @ORM\Entity
 */
class Charges
{
    /**
     * @var int The entity Id
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(name="id", type="integer", options={"unsigned"=true})
     * @Groups({"charges:read", "charges:write"})
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Users::class, inversedBy="charges")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     * @Groups({"charges:write"})
     * @SerializedName("user_id")
     */
    private $user;

    /**
     * @var string The Service used
     *
     * @ORM\Column(name="service", type="string", length=16)
     * @Groups({"charges:read", "charges:write"})
     * @Assert\Length(max=16)
     * @Assert\Choice(choices={"BoxC Post", "BoxC Parcel", "BoxC Plus", "BoxC Priority"}, message="Service must be one of these options: BoxC Post, BoxC Parcel, BoxC Plus, BoxC Priority")
     */
    public $service;

    /**
     * @var string|null entity
     *
     * @ORM\Column(name="entity", type="string", length=34, nullable=true)
     * @Groups({"bulk_shipments:read", "bulk_shipments:write"})
     * @Assert\Length(max=34)
     */
    public $entity;

    /**
     * @var string Description.
     *
     * @ORM\Column(name="description", type="string", length=255, nullable=true)
     * @Groups({"bulk_shipments:read", "bulk_shipments:write"})
     * @Assert\Length(max=255)
     */
    public $description;

    /**
     * @var int Quantity.
     *
     * @ORM\Column(name="quantity", type="smallint", options={"unsigned"=true, "default"=1})
     * @Groups({"bulk_shipments:read", "bulk_shipments:write"})
     */
    public $quantity = 1;

    /**
     * @var decimal Rate.
     *
     * @ORM\Column(name="rate", type="decimal", precision=6, scale=2, options={"unsigned"=true, "default"=0.00})
     * @Groups({"bulk_shipments:read", "bulk_shipments:write"})
     */
    public $rate;

    /**
     * @var int Invoiced flag.
     *
     * @ORM\Column(name="invoiced", type="boolean", options={"unsigned"=true, "default"=0})
     * @Groups({"bulk_shipments:read"})
     */
    private $invoiced = 0;

    /**
     * @var \DateTimeInterface Creation Date
     *
     * @ORM\Column(name="created", type="datetime", options={"default": "CURRENT_TIMESTAMP"})
     * @Groups({"bulk_shipments:read"})
     */
    public $created;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?Users
    {
        return $this->user;
    }

    public function setUser(Users $user): self
    {
        $this->user = $user;

        return $this;
    }
}
