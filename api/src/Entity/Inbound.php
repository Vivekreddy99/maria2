<?php
// api/src/Entity/Inbound.php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Entity\EntryPoints;
use App\Entity\Manifests;
use App\Entity\Users;
use App\Entity\LineItems;
use App\Entity\Orders;
use App\Entity\ProductsSkus;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Validator\Exception;
use App\Exception\TooManyPackagesException;
use ApiPlatform\Core\Annotation\ApiFilter;
use App\Filter\OrderByFixedPropertyFilter;
use App\Filter\DateInclusiveFilter;
use App\Filter\SearchFilterWithMapping;
use App\Filter\ExistsFilterWithMapping;

/**
 * @ApiResource(
 *     collectionOperations={
 *         "get"={
 *             "path"="/inbound"
 *          },
 *         "post"={
 *             "path"="/inbound",
 *              "denormalization_context"={"groups"={"inbound:create"}}
 *          },
 *     },
 *     itemOperations={
 *         "get"={
 *             "path"="/inbound/{id}"
 *          },
 *         "put"={
 *             "path"="/inbound/{id}"
 *          },
 *         "delete"={
 *             "path"="/inbound/{id}"
 *          },
 *     },
 *     normalizationContext={"groups"={"inbound:read"}},
 *     denormalizationContext={"groups"={"inbound:write"}},
 *     attributes={"route_prefix"="/v2"}
 * )
 * @ApiFilter(OrderByFixedPropertyFilter::class, properties={"id": "ASC"})
 * @ApiFilter(DateInclusiveFilter::class, properties={"created"})
 * @ApiResource(attributes={"pagination_items_per_page"=50})
 * @ORM\Entity
 */
class Inbound
{
    /**
     * @var string|null The carrier for the inbound shipment. Max length: 32
     *
     * @Assert\Type("string")
     * @Assert\Length(max=32)
     * @ORM\Column(name="carrier", type="string", length=32, nullable=true)
     * @Groups({"inbound:read", "inbound:create", "inbound:write"})
     */
    public $carrier;

    /**
     * @var \DateTimeInterface The date and time the inbound shipment was created. Set by the system.
     *
     * @ORM\Column(name="created", type="datetime", options={"default": "CURRENT_TIMESTAMP"})
     * @Groups({"inbound:read"})
     */
    public $created;

    /**
     * @var int The entity Id
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(name="id", type="integer", options={"unsigned"=true})
     * @Groups({"inbound:read", "inbound:create", "inbound_products:read", "inbound_products:write"})
     */
    public $id;

    /**
     * @var string|null User defined notes for reference. Default: null. Max length: 64.
     *
     * @Assert\Type("string")
     * @Assert\Length(max=64)
     * @ORM\Column(name="notes", type="string", length=64, nullable=true)
     * @Groups({"inbound:read", "inbound:create", "inbound:write"})
     */
    public $notes;

    /**
     * @ORM\ManyToOne(targetEntity=Users::class, inversedBy="inbound")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     * @Groups({"inbound:create", "inbound:write"})
     * @SerializedName("user_id")
     */
    private $user;

    /**
     * @var string[]
     *
     * @Groups({"inbound:create"})
     */
    public $attributes_products;

    /**
     * @ORM\OneToMany(targetEntity=InboundProducts::class, mappedBy="inbound", cascade={"persist", "remove"})
     * @Groups({"inbound:create", "inbound:read", "products:write"})
     * @SerializedName("products")
     */
    public $inbound_products;

    /**
     * @var \DateTimeInterface|null The date and time the inbound shipment was received by the warehouse. Set by the system.
     *
     * @ORM\Column(name="received", type="datetime", nullable=true)
     * @Groups({"inbound:read"})
     */
    public $received;

    /**
     * @var string|null The status of the inbound shipment. Set by the system.
     *
     * @Assert\Choice(choices={"Pending", "Received", "Processed"})
     * @ORM\Column(name="status", type="string", length=16, options={"default"="Pending"}, nullable=true)
     * @Groups({"inbound:read"})
     */
    private $status;

    /**
     * @var string|null The tracking number for the inbound shipment. Default is null. Set by the system.
     *
     * @Assert\Type("string")
     * @Assert\Length(max=40)
     * @ORM\Column(name="tracking_number", type="string", length=40, nullable=true)
     * @Groups({"inbound:read", "inbound:create", "inbound:write"})
     */
    public $tracking_number;

    /**
     * @ORM\ManyToOne(targetEntity=Warehouses::class, inversedBy="inbound")
     * @Groups({"inbound:read", "inbound:create", "inbound:write"})
     */
    public $wh;

    public function __construct()
    {
        $this->created = new \DateTimeImmutable();
        $this->inbound_products = new ArrayCollection();
    }

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

    public function setInboundProducts($inbound_products)
    {
        foreach ($inbound_products as $inbound_product) {
            $this->addInboundProduct($inbound_product);
        }
    }

    public function addInboundProduct($inbound_product)
    {
        if ($inbound_product instanceof Products) {

            // Pass quantity or other values to InboundProducts constructor.
            $quantity = $processed = 0;
            $inbound_cost = 0.0;
            $attrs = $this->getAttributesProducts();
            $prod_id = $inbound_product->getId();
            $prod_user = $inbound_product->getUser()->getId();

            if (isset($prod_id) && is_numeric($prod_id) && isset($prod_user) && is_numeric($prod_user)) {

                $user_match = false;

                if (is_array($attrs) && isset($attrs[$prod_id])) {
                    if (isset($attrs[$prod_id]['quantity'])) {
                        $quantity = $attrs[$prod_id]['quantity'];
                    }

                    if (isset($attrs[$prod_id]['inbound_cost'])) {
                        $quantity = $attrs[$prod_id]['inbound_cost'];
                    }

                    /* Set by the system
                    if (isset($attrs[$prod_id]['processed'])) {
                        $processed = $attrs[$prod_id]['processed'];
                    } */

                    // Verify that current user is the Product owner.
                    if (isset($attrs[$prod_id]['current_user']) && is_numeric($attrs[$prod_id]['current_user']) && $attrs[$prod_id]['current_user'] == $prod_user) {
                        $user_match = true;
                    }
                }

                if ($user_match) {

                    if (is_array($attrs) && isset($attrs[$prod_id]) && isset($attrs[$prod_id]['quantity'])) {
                        $quantity = $attrs[$prod_id]['quantity'];
                    }

                    // Check that Product does not belong to a test Order.
                    $is_test_product = false;
                    if (isset($inbound_product->line_items) && \count($inbound_product->line_items) > 0) {
                        foreach ($inbound_product->line_items as $line_item) {
                            if ($line_item instanceof LineItems && isset($line_item->order) && $line_item->order instanceof Orders) {
                                if (\method_exists($line_item->order, 'getTest') && 1 == $line_item->order->getTest()) {
                                    $is_test_product = true;
                                    break;
                                }
                            }
                        }
                    }

                    // Check that Product does not belong to a test Shop Sku.
                    if (!$is_test_product && isset($inbound_product->products_skus) && count($inbound_product->products_skus) > 0) {
                        foreach ($inbound_product->products_skus as $sku) {
                            if ($sku instanceof ProductsSkus && isset($sku->shop) && $sku->shop instanceof Shops && $sku->shop->test == 1) {
                                $is_test_product = true;
                                break;
                            }
                        }
                    }

                    if (!$is_test_product) {
                        $this->inbound_products[] = new InboundProducts($this, $inbound_product, $quantity, $inbound_cost);
                    }
                }
            }
        }
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus($status)
    {
        $this->status = $status;
    }

    public function setAttributesProducts(?array $attributes_products)
    {
        $this->attributes_products = $attributes_products;
    }

    public function getAttributesProducts(): ?array
    {
        return $this->attributes_products;
    }
}
