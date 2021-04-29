<?php
// api/src/Entity/WarehousesWaves.php

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
 *     normalizationContext={"groups"={"warehouses_waves:read"}},
 *     denormalizationContext={"groups"={"warehouses_waves:write"}},
 *     attributes={"route_prefix"="/v2"}
 * )
 * @ORM\Entity
 */
class WarehousesWaves
{
    /**
     * @var int The entity Id
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(name="id", type="integer", options={"unsigned"=true})
     * @Groups({"warehouses_waves:read", "warehouses_waves:write"})
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Warehouses::class)
     * @Groups({"warehouses_waves:write"})
     * @SerializedName("wh_id")
     */
    public $wh;

    /**
     * @var string|null Filters
     *
     * @ORM\Column(name="line_items", type="json", nullable=true)
     * @Groups({"warehouses_waves:read", "warehouses_waves:write"})
     */
    public $filters;

    /**
     * @var \DateTimeInterface Creation Date
     *
     * @ORM\Column(name="created", type="datetime", options={"default": "CURRENT_TIMESTAMP"})
     * @Groups({"warehouses_waves:read"})
     */
    private $created;

    /**
     * @var \DateTimeInterface|null Start Time
     *
     * @ORM\Column(name="start_time", type="datetime", options={"default": "CURRENT_TIMESTAMP"})
     * @Groups({"warehouses_waves:read"})
     */
    public $start_time;

    /**
     * @var \DateTimeInterface|null End Time
     *
     * @ORM\Column(name="end_time", type="datetime", options={"default": "CURRENT_TIMESTAMP"})
     * @Groups({"warehouses_waves:read"})
     */
    public $end_time;

    public function getId(): ?int
    {
        return $this->id;
    }
}
