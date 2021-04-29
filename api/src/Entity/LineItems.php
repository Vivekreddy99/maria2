<?php
// api/src/Entity/LineItems.php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\JoinColumn;
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
 *     normalizationContext={"groups"={"line_items:read"}},
 *     denormalizationContext={"groups"={"line_items:write"}},
 *     attributes={"route_prefix"="/v2"}
 * )
 * @ORM\Entity
 */
class LineItems
{
    /**
     * @ORM\ManyToOne(targetEntity=Orders::class)
     * @ORM\Id
     * @Groups({"line_items:write", "orders:write", "products:read", "products:write"})
     * @SerializedName("order_id")
     */
    public $order;

    /**
     * @ORM\ManyToOne(targetEntity=Products::class)
     * @ORM\Id
     * @Groups({"line_items:write", "orders:write", "products:write"})
     * @SerializedName("product_id")
     */
    public $product;

    /**
     * @var string
     *
     * @Groups({"orders:read"})
     */
    public $dg_code;

    /**
     * @var int|null fulfillment_id
     *
     * @ORM\ManyToOne(targetEntity=Fulfillments::class, inversedBy="id")
     * @SerializedName("fulfillment_id")
     */
    private $fulfillment;

    /**
     * @var int
     *
     * @Groups({"line_items:read", "orders:read"})
     */
    public $fulfillment_id;

    /**
     * @var string
     *
     * @Groups({"orders:read"})
     */
    public $hts_code;

    /**
     * @var string
     *
     * @Groups({"orders:read"})
     */
    public $name;

    /**
     * @var int
     *
     * @Groups({"orders:read"})
     */
    public $product_id;

    /**
     * @var int quantity
     *
     * @ORM\Column(name="quantity", type="smallint", options={"unsigned":true, "default": 0})
     * @Groups({"line_items:read", "line_items:write", "orders:read", "orders:write"})
     */
    public $quantity;

    /**
     * @var string SKU
     *
     * @ORM\Column(name="sku", type="string", length=32)
     * @Groups({"line_items:read", "line_items:write", "orders:read", "orders:write"})
     * @Assert\Length(max=32)
     */
    public $sku;

    /**
     * @var decimal|null sold_for
     *
     * @ORM\Column(name="sold_for", type="decimal", precision=6, scale=2, nullable=true)
     * @Groups({"line_items:read", "line_items:write", "orders:read", "orders:write"})
     */
    public $sold_for;

    /**
     * @var string|null shop_line_item_id
     *
     * @ORM\Column(name="shop_line_item_id", type="string", length=32, nullable=true)
     * @Groups({"line_items:read", "line_items:write"})
     * @Assert\Length(max=32)
     */
    public $shop_line_item_id;

    public function __construct(Orders $order, Products $product, $sku, $quantity, $sold_for) {
        $this->order = $order;
        $this->product = $product;
        $this->sku = $sku;
        $this->quantity = $quantity;
        $this->sold_for = $sold_for;
    }

    public function getProductId() :?int
    {
        return $this->product->getId();
    }

    public function getDgCode() :?string
    {
        return $this->product->getDgCode();
    }

    public function getHtsCode() :?string
    {
        return $this->product->getHsCode();
    }

    public function getName() :?string
    {
        return $this->product->getName();
    }

    public function setQuantity(int $quantity)
    {
        $this->quantity = $quantity;
    }

    public function setSku(string $sku)
    {
        $this->sku = $sku;
    }

    public function getFulfillmentId() :?int
    {
        if (isset($this->fulfillment) && $this->fulfillment instanceof Fulfillments) {
            return $this->fulfillment->getId();
        } else {
            return null;
        }
    }
}
