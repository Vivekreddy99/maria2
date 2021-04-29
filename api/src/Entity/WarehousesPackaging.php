<?php
// api/src/Entity/WarehousesPackaging.php

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
 *     normalizationContext={"groups"={"warehouses_packaging:read"}},
 *     denormalizationContext={"groups"={"warehouses_packaging:write"}},
 *     attributes={"route_prefix"="/v2"}
 * )
 * @ORM\Entity
 */
class WarehousesPackaging
{
    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity=Warehouses::class)
     * @Groups({"warehouses_packaging:write"})
     * @SerializedName("wh_id")
     */
    public $wh_id;

    /**
     * @var int packaging_id
     *
     * @ORM\Id
     * @ORM\Column(name="packaging_id", type="integer")
     */
    public $packaging_id;

    /**
     * @var decimal cost
     *
     * @ORM\Column(type="decimal", precision=4, scale=2, options={"default"=0.0})
     */
    public $cost;

    /**
     * @var int qty_on_hand
     *
     * @ORM\Column(type="integer")
     */
    public $qty_on_hand;

    public function getId(): ?int
    {
        return $this->id;
    }
}
