<?php
// api/src/Entity/InboundProducts.php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Entity\EntryPoints;
use App\Entity\Inbound;
use App\Entity\Shipments;
use App\Entity\Manifests;
use App\Entity\Products;
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
 *         "put",
 *     },
 *     normalizationContext={"groups"={"inbound_products:read"}},
 *     denormalizationContext={"groups"={"inbound_products:write"}},
 *     attributes={"route_prefix"="/v2"}
 * )
 * @ORM\Entity
 */
class InboundProducts
{
    /**
     * @ORM\ManyToOne(targetEntity=Inbound::class, inversedBy="inbound_products")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups({"inbound_products:read", "inbound_products:write", "inbound:write"})
     * @SerializedName("id")
     */
    private $inbound;

    /**
     * @var Products
     * @ApiProperty(identifier=true)
     *
     * @ORM\ManyToOne(targetEntity=Products::class, inversedBy="inbound_products")
     * @ORM\Id()
     * @ORM\GeneratedValue
     * @Groups({"inbound_products:read", "inbound_products:write", "products:read", "products:write", "inbound:read", "inbound:write"})
     */
    private $product;

    /**
     * @var string|null codes
     *
     * @ORM\Column(name="codes", type="json", nullable=true)
     * @Groups({"inbound:read", "inbound_products:read"})
     */
    public $codes;

    /**
     * @var int
     *
     * @ApiProperty(identifier=true)
     * @Groups({"inbound:read", "inbound_products:read"})
     */
    private $id;

    /**
     * @var decimal The Inbound Cost.
     *
     * @ORM\Column(name="inbound_cost", type="decimal", precision=7, scale=2, options={"unsigned"=true}, nullable=true)
     * @Groups({"inbound_products:read", "inbound_products:write"})
     */
    private $inbound_cost;

    /**
     * @var string|null
     *
     * @Groups({"inbound_products:read", "inbound:read"})
     */
    private $name;

    /**
     * @var int The number of units processed by the warehouse. Set by the system.
     *
     * @ORM\Column(name="quantity_processed", type="smallint", options={"unsigned"=true, "default"=0})
     * @Groups({"inbound:read", "inbound_products:read"})
     * @SerializedName("processed")
     */
    private $quantity_processed = 0;

    /**
     * @var int The quantity shipped. Set by the system.
     *
     * @ORM\Column(name="quantity_shipped", type="smallint", options={"unsigned"=true, "default"=0})
     */
    private $quantity_shipped = 0;

    /**
     * @var int The number of units being sent to the warehouse.
     *
     * @ORM\Column(name="quantity", type="smallint", options={"unsigned"=true})
     * @Groups({"inbound:read", "inbound_products:read", "inbound_products:write", "inbound:write"})
     * @SerializedName("quantity")
     */
    private $quantity = 0;

    public function __construct(Inbound $inbound = null, Products $product = null, int $quantity, float $inbound_cost)
    {
        if (is_object($inbound)) {
            $this->inbound = $inbound;
        }

        if (is_object($product)) {
            $this->product = $product;
        }

        $this->quantity = $quantity;
        $this->inbound_cost = $inbound_cost;
        // $this->quantity_processed = $processed; Set by the system.
    }

    public function getId():?int
    {
        return $this->product->getId();
    }

    public function getName()
    {
        return $this->product->getName();
    }

    /**
     * @return int The number of units processed by the warehouse. Set by the system.
     *
     * @Groups({"inbound:read", "inbound_products:read"})
     * @SerializedName("processed")
     */
    public function getQuantityProcessed() :int
    {
        return $this->quantity_processed;
    }

    public function setQuantityProcessed(int $quantity_processed) :self
    {
        $this->quantity_processed = $quantity_processed;

        return $this;
    }

    /**
     * @return int The number of units being sent to the warehouse.
     *
     * @Groups({"inbound:read", "inbound_products:read"})
     * @SerializedName("quantity")
     */
    public function getQuantity() :int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity) :self
    {
        $this->quantity = $quantity;

        return $this;
    }
}
