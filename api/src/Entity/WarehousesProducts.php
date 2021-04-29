<?php
// api/src/Entity/WarehousesProducts.php

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
use App\Filter\SearchFilterWithMapping;
use App\Filter\ExistsFilterWithMapping;
use JsonSerializable;

/**
 *
 * @ApiResource(
 *     collectionOperations={
 *         "get",
 *         "post"
 *     },
 *     itemOperations={
 *         "get"
 *     },
 *     normalizationContext={"groups"={"warehouses_products:read"}},
 *     denormalizationContext={"groups"={"warehouses_products:write"}},
 *     attributes={"route_prefix"="/v2"}
 * )
 * @ORM\Entity
 */
class WarehousesProducts implements JsonSerializable
{
    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity=Products::class, inversedBy="warehouses_products")
     * @Groups({"warehouses_products:write", "products:write"})
     */
    public $product;

    /**
     * @var integer
     */
    public $product_id = 0;

    /**
     * @var string|null wh_sku
     *
     * @ORM\Column(name="wh_sku", type="string", length=32, nullable=true)
     * @Groups({"products:read"})
     */
    private $wh_sku;

    /**
     * @var string|null description
     *
     * @ORM\Column(name="description", type="string", length=64, nullable=true)
     * @Groups({"warehouses_products:read", "warehouses_products:write", "products:read", "products:write"})
     */
    public $description;

    /**
     * @ORM\ManyToOne(targetEntity=Warehouses::class, inversedBy="warehouses_products")
     * @ORM\Id
     * @Groups({"warehouses_products:write", "products:write"})
     */
    public $wh;

    /**
     * @var string
     *
     * @Groups({"products:read"})
     * @SerializedName("id")
     */
    public $warehouse_id;

    /**
     * @var string
     *
     * @Groups({"products:read"})
     */
    public $language;

    /**
     * @var string
     *
     * @Groups({"products:read"})
     */
    public $language_code;

    /**
     * @var integer qty_on_hand
     *
     * @ORM\Column(name="qty_on_hand", type="integer", options={"default"=0})
     * @Groups({"warehouses_products:read", "warehouses_products:write", "products:read"})
     * @SerializedName("quantity")
     */
    public $qty_on_hand = 0;

    public function __construct($wh = null, $product = null, $warehouses_product = null)
    {
        if (is_object($wh)) {
            $this->wh = $wh;
            if (is_object($warehouses_product)) {
                if (isset($warehouses_product->qty_on_hand)) {
                    $this->qty_on_hand = $warehouses_product->qty_on_hand;
                }
                if (isset($warehouses_product->description)) {
                    $this->description = $warehouses_product->description;
                }
            }
        }

        if (is_object($product)) {
            $this->product = $product;
        }
    }

    public function getWarehouseId() :?string
    {
        return $this->wh->getId();
    }

    public function getQtyOnHand() :?int
    {
        return $this->qty_on_hand;
    }

    public function getLanguage():string
    {
        return is_object($this->wh) ? $this->wh->language : '';
    }

    public function getLanguageCode():string
    {
        return is_object($this->wh) ? $this->wh->language_code : '';
    }

    public function jsonSerialize()
    {
        return [
            'description' => $this->description,
            'id' => $this->getWarehouseId(),
            'language' => $this->wh->language,
            'language_id' => $this->wh->language_code,
            'quantity' => $this->getQtyOnHand(),
        ];
    }
}
