<?php
// api/src/Entity/ProductsSkus.php

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
use App\Controller\SkuController;
use JsonSerializable;

/**
 *
 * @ApiResource(
 *     collectionOperations={
 *         "get",
 *         "post"={
 *              "path"="/products/{id}/skus",
 *          },
 *     },
 *     itemOperations={
 *         "get",
 *         "delete",
 *         "put"
 *     },
 *     normalizationContext={"groups"={"products_skus:read"}},
 *     denormalizationContext={"groups"={"products_skus:write"}},
 *     attributes={"route_prefix"="/v2"}
 * )
 * @ORM\Entity
 */
class ProductsSkus implements JsonSerializable
{
    /**
     * @var int Active flag.
     *
     * @ORM\Column(name="active", type="boolean", options={"unsigned"=true, "default"=1})
     * @Groups({"products_skus:read", "products_skus:write", "products:read", "products:write"})
     */
    public $active = 1;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity=Products::class, inversedBy="products_skus")
     * @Groups({"products_skus:write", "products:write"})
     */
    public $product;

    /**
     * @ORM\ManyToOne(targetEntity=Shops::class, inversedBy="products_skus")
     * @ORM\Id
     * @Groups({"products_skus:write", "products:write"})
     */
    public $shop;

    /**
     * @var string
     *
     * @Groups({"products_skus:read"})
     * @SerializedName("shop")
     */
    public $shop_id;

    /**
     * @var string SKU
     *
     * @ORM\Column(name="sku", type="string", length=32)
     * @Groups({"products_skus:read", "products_skus:write", "products:read", "products:write"})
     * @Assert\NotBlank
     * @Assert\Length(max=32)
     */
    public $sku;

    public function __construct($shop = null, $product = null, $active = null, $sku = null)
    {
        if (is_object($shop)) {
            $this->shop = $shop;
        }

        if (is_object($product)) {
            $this->product = $product;
        }

        if (!empty($active)) {
            $this->active = $active;
        }

        if (!empty($sku)) {
            $this->sku = $sku;
        }
    }

    public function getShopId() :?string
    {
        $shop_id = null;
        if (isset($this->shop) && is_object($this->shop)){
           $shop_id = $this->shop->getId();
        }

        return $shop_id;
    }

    public function setShopId(?string $shop_id)
    {
        $this->shop_id = $shop_id;
    }

    public function getSku():string
    {
        return $this->sku;
    }

    public function setSku(string $sku)
    {
        $this->sku = $sku;
    }

    public function getActive() :bool
    {
        return (bool) $this->active;
    }

    public function setActive(int $active)
    {
        $this->active = $active;
    }

    public function jsonSerialize()
    {
        return [
            'active' => $this->active ? 'true' : 'false',
            'shop_id' => $this->shop_id,
            'sku' => $this->sku,
        ];
    }
}
