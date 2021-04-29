<?php
// api/src/Entity/Statements.php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
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
 *         "get"
 *     },
 *     itemOperations={
 *         "get"
 *     },
 *     normalizationContext={"groups"={"statements:read"}},
 *     attributes={"route_prefix"="/v2"}
 * )
 *
 * @ApiFilter(OrderByFixedPropertyFilter::class, properties={"id": "ASC"})
 * @ApiFilter(DateInclusiveFilter::class, properties={"created"})
 * @ApiResource(attributes={"pagination_items_per_page"=50})
 *
 * @ORM\Entity
 * @ORM\Table(name="invoices")
 */
class Statements
{
    /**
     * @var date Creation Date
     *
     * @ORM\Column(name="created", type="date")
     * @Groups({"statements:read"})
     */
    public $created;

    /**
     * @var int The entity Id
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(name="id", type="bigint", options={"unsigned"=true})
     * @Groups({"statements:read"})
     */
    public $id;

    /**
     * @ORM\ManyToOne(targetEntity=Users::class, inversedBy="statements")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     * @SerializedName("user_id")
     */
    private $user;

    /**
     * @var decimal Total billed.
     *
     * @ORM\Column(name="total_billed", type="decimal", precision=9, scale=2, options={"default": 0.00})
     * @Groups({"statements:read"})
     * @SerializedName("total")
     */
    public $total_billed;

    /**
     * @var int Total statement count.
     *
     * @Groups({"statements:read"})
     */
    public $count = 0;

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

    public function getCreated()
    {
        return date_format($this->created, 'Y-m-d');
    }

    public function getTotalBilled()
    {
        return \floatval($this->total_billed);
    }
}
