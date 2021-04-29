<?php
// api/src/Entity/WarehousesStorage.php

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
 *     normalizationContext={"groups"={"warehouses_storage:read"}},
 *     denormalizationContext={"groups"={"warehouses_storage:write"}},
 *     attributes={"route_prefix"="/v2"}
 * )
 * @ORM\Entity
 */
class WarehousesStorage
{
    /**
     * @var date Date
     *
     * @ORM\Id
     * @ORM\Column(name="date", type="date")
     */
    public $date;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity=Warehouses::class)
     * @Groups({"warehouses_storage:write"})
     * @SerializedName("wh_id")
     */
    public $wh;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity=Users::class, inversedBy="warehouses_storage")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     * @Groups({"warehouses_storage:write"})
     * @SerializedName("user_id")
     */
    private $user;

    /**
     * @var int small_pieces
     *
     * @ORM\Column(name="small_pieces", type="integer", options={"unsigned":true, "default": 0})
     * @Groups({"warehouses_storage:read", "warehouses_storage:write"})
     */
    public $small_pieces;

    /**
     * @var int medium_pieces
     *
     * @ORM\Column(name="medium_pieces", type="integer", options={"unsigned":true, "default": 0})
     * @Groups({"warehouses_storage:read", "warehouses_storage:write"})
     */
    public $medium_pieces;

    /**
     * @var decimal Large CBM.
     *
     * @ORM\Column(name="large_cbm", type="decimal", precision=8, scale=3, options={"default": 0.0})
     * @Groups({"warehouses_storage:read", "warehouses_storage:write"})
     */
    public $large_cbm;

    /**
     * @var decimal Small cost.
     *
     * @ORM\Column(name="small_cost", type="decimal", precision=8, scale=4, options={"default": 0.0})
     * @Groups({"warehouses_storage:read", "warehouses_storage:write"})
     */
    public $small_cost;

    /**
     * @var decimal Medium cost.
     *
     * @ORM\Column(name="medium_cost", type="decimal", precision=8, scale=4, options={"default": 0.0})
     * @Groups({"warehouses_storage:read", "warehouses_storage:write"})
     */
    public $medium_cost;

    /**
     * @var decimal Large Cost
     *
     * @ORM\Column(name="large_cost", type="decimal", precision=8, scale=4, options={"default": 0.0})
     * @Groups({"warehouses_storage:read", "warehouses_storage:write"})
     */
    public $large_cost;

    /**
     * @var int Invoiced flag
     *
     * @ORM\Column(name="invoiced", type="boolean", options={"unsigned"=true, "default"=0})
     * @Groups({"warehouses_storage:read", "warehouses_storage:write"})
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
}
