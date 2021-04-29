<?php
// api/src/Entity/Products.php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Entity\EntryPoints;
use App\Entity\Shipments;
use App\Entity\Manifests;
use App\Entity\LineItems;
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
use JsonSerializable;

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
 *     normalizationContext={"groups"={"products:read"}},
 *     denormalizationContext={"groups"={"products:write"}},
 *     attributes={"route_prefix"="/v2"}
 * )
 * @ApiFilter(SearchFilterWithMapping::class, properties={"products_skus.sku": "exact", "products_skus.shop.id": "exact"})
 * @ORM\Entity
 */
class Products implements JsonSerializable
{
    /**
     * @var int backordered
     *
     * @ORM\Column(name="backordered", type="smallint", options={"default": 0}, nullable=true)
     * @Groups({"products:read", "products:write"})
     */
    public $backordered = 0;

    /**
     * @var string|null barcode
     *
     * @ORM\Column(name="barcode", type="string", length=32, nullable=true)
     * @Groups({"products:read", "products:write"})
     */
    public $barcode;

    /**
     * @var string The country of origin or where the product was manufactured in ISO 3166-1 alpha-2 format.
     *
     * @ORM\Column(name="coo", type="string", length=2, options={"fixed" = true})
     * @Assert\Choice(callback="getCountryIsoCodes", message="The country of origin or where the product was manufactured must be in the ISO 3166-1 alpha-2 format.")
     * @Groups({"products:read", "products:write"})
     */
    public $coo;

    /**
     * @var decimal
     *
     * @ORM\Column(name="cost", type="decimal", precision=12, scale=2, options={"default": 0.0})
     * @Groups({"products:read", "products:write"})
     */
    public $cost = 0.0;

    /**
     * @var int Total product count.
     *
     * @Groups({"products:read"})
     */
    public $count = 0;

    /**
     * @var \DateTimeInterface created
     *
     * @ORM\Column(name="created", type="datetime", options={"default": "CURRENT_TIMESTAMP"})
     * @Groups({"products:read", "products:write"})
     */
    public $created;

    /**
     * @var string Product description
     *
     * @ORM\Column(name="description", type="string", length=64)
     * @Groups({"products:read", "products:write"})
     */
    public $description;

    /**
     * @var string|null dg_code
     *
     * @ORM\Column(name="dg_code", type="string", length=5, options={"fixed": true}, nullable=true)
     * @Assert\Choice(callback="getDgCodeList")
     * @Groups({"orders:read", "products:read", "products:write"})
     */
    public $dg_code;

    /**
     * @var int Height of the Product
     *
     * @ORM\Column(name="height", type="smallint", options={"unsigned":true, "default": 0})
     * @Groups({"products:read", "products:write"})
     */
    public $height = 0;

    /**
     * @var string|null hs_code
     *
     * @ORM\Column(name="hs_code", type="string", length=10, options={"fixed" = true}, nullable=true)
     * @Groups({"products:read", "products:write"})
     * @SerializedName("hts_code")
     */
    public $hs_code;

    /**
     * @var string|null hs_codes
     *
     * @ORM\Column(name="hs_codes", type="json", nullable=true)
     * @Groups({"products:read", "products:write"})
     * @SerializedName("hts_codes")
     */
    public $hs_codes;

    /**
     * @var int The entity Id
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(name="id", type="integer", options={"unsigned"=true})
     * @Groups({"products:read", "products:write", "inbound:read", "inbound:write", "inbound_products:read", "inbound_products:write"})
     */
    private $id;

    /**
     * @var int Is Packaging flag
     *
     * @ORM\Column(name="is_packaging", type="boolean", options={"unsigned"=true, "default"=0})
     * @Groups({"products:read", "products:write"})
     */
    private $is_packaging = 0;

    /**
     * @var int Length of the Product
     *
     * @ORM\Column(name="length", type="smallint", options={"unsigned":true, "default": 0})
     * @Groups({"products:read", "products:write"})
     */
    public $length = 0;

    /**
     * @var string Product name
     *
     * @ORM\Column(name="name", type="string", length=64)
     * @Groups({"products:read", "products:write", "inbound:read", "inbound_products:read", "orders:read"})
     */
    private $name;

    /**
     * @var int The total number of units on hand across all warehouses. Set by the system.
     *
     * @Groups({"products:read"})
     */
    private $quantity = 0;

    /**
     * @ORM\OneToMany(targetEntity=ProductsSkus::class, mappedBy="product", cascade={"persist", "remove"})
     * @Groups({"products:read", "products:write", "products_skus:write"})
     * @SerializedName("skus")
     */
    public $products_skus;

    /**
     * @ORM\OneToMany(targetEntity=InboundProducts::class, mappedBy="product")
     * @Groups({"products:write", "inbound:write", "inbound_products:write"})
     * @SerializedName("products")
     */
    public $inbound_products;

    /**
     * @ORM\Column(name="value", type="decimal", precision=7, scale=2, options={"default": 0.0})
     * @Groups({"products:read", "products:write"})
     */
    public $value;

    /**
     * @ORM\OneToMany(targetEntity=WarehousesProducts::class, mappedBy="product", cascade={"persist", "merge", "remove"})
     * @Groups({"products:read", "products:write", "warehouses_products:write"})
     * @SerializedName("warehouses")
     */
    public $warehouses_products;

    public function setWarehousesProducts($warehouses_products) {
        foreach ($warehouses_products as $warehouses_product) {
            $this->addWarehousesProduct($warehouses_product);
        }
    }

    public function addWarehousesProduct($warehouses_product) {
        if (is_object($warehouses_product) && isset($warehouses_product->wh) && !isset($warehouses_product->product)) {
            $warehouses_product = new WarehousesProducts($warehouses_product->wh, $this, $warehouses_product);
        }

        $this->warehouses_products[] = $warehouses_product;
    }

    public function setProductsSkus($products_skus) {
        foreach ($products_skus as $products_sku) {
            $this->addProductsSku($products_sku);
        }
    }

    public function addProductsSku($products_sku) {
        if (is_object($products_sku) && isset($products_sku->shop)) {
            $active = $products_sku->active ?? null;
            $sku = $products_sku->sku ?? null;
            $this->products_skus[] = new ProductsSkus($products_sku->shop, $this, $active, $sku);
        }
    }

    /**
     * @var decimal The Weight as decimal
     *
     * @ORM\Column(name="weight", type="decimal", precision=6, scale=3)
     * @Groups({"products:read", "products:write"})
     */
    private $weight = 0;

    /**
     * @ORM\OneToMany(targetEntity=LineItems::class, mappedBy="product", cascade={"remove"})
     * @Groups({"products:write", "orders:read", "orders:write", "line_items:read", "line_items:write"})
     */
    public $line_items;

    /**
     * @var int Width of the Product
     *
     * @ORM\Column(name="width", type="smallint", options={"unsigned":true, "default": 0})
     * @Groups({"products:read", "products:write"})
     */
    public $width = 0;

    /**
     * @ORM\ManyToOne(targetEntity=Users::class, inversedBy="products")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     * @Groups({"products:write"})
     * @SerializedName("user_id")
     */
    private $user;

    public function __construct() {
        $this->created = new \DateTimeImmutable();
        $this->warehouses_products = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBarcode() :?string
    {
        if (empty($this->barcode)) {
            $this->barcode = $this->getId();
        }

        return $this->barcode;
    }

    public function getCreated()
    {
        return $this->created->format('Y-m-d H:m:s');
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

    public function getName() :?string
    {
        return $this->name;
    }

    public function setName(?string $name)
    {
        $this->name = $name;
    }

    public function getDgCode() :?string
    {
        return $this->dg_code;
    }

    public function getHsCode() :?string
    {
        return $this->hs_code;
    }

    public function getQuantity() :int
    {
        $total = 0;

        // Sum WarehousesProducts qty_on_hand.
        $whs = $this->warehouses_products;
        foreach ($whs as $wh)
        {
            $temp = $wh->getQtyOnHand();
            $total += intval($temp);
        }

        return $total;
    }

    public function getWeight() :float
    {
        return $this->weight;
    }

    public static function getCountryIsoCodes() {
        return Shipments::getCountryIsoCodes();
    }

    public static function getDgCodeList() {
        return ["0965", "0966", "0967", "0968", "0969", "0970", "ORMD1", "ORMD2", "ORMD3"];
    }

    public function jsonSerialize()
    {
        // Get related SKU data.
        $sku_data = [];
        $skus = $this->products_skus;
        foreach ($skus as $sku) {
            $sku_data[] = $sku->jsonSerialize();
        }

        // Get related Warehouse data.
        $warehouse_data = [];
        $warehouses = $this->warehouses_products;
        foreach ($warehouses as $warehouse) {
            $warehouse_data[] = $warehouse->jsonSerialize();
        }

        $data = [
            'backordered' => $this->backordered,
            'barcode' => $this->barcode,
            'coo' => $this->coo,
            'cost' => $this->cost,
            'created' => $this->getCreated(),
            'description' => $this->description,
            'dg_code' => $this->dg_code,
            'height' => $this->height,
            'hts_code' => $this->hs_code,
            'id' => $this->getId(),
            'length' => $this->length,
            'name' => $this->name,
            'quantity' => $this->getQuantity(),
            'skus' => $sku_data,
            'value' => $this->value,
            'warehouses' => $warehouse_data,
        ];

        return ['product' => $data];
    }
}
