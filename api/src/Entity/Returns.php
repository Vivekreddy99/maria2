<?php
// api/src/Entity/Returns.php

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
use JsonSerializable;

/**
 *
 * @ApiResource(
 *     collectionOperations={
 *         "get"
 *     },
 *     itemOperations={
 *         "get",
 *         "delete",
 *         "put"
 *     },
 *     normalizationContext={"groups"={"returns:read"}},
 *     denormalizationContext={"groups"={"returns:write"}},
 *     attributes={"route_prefix"="/v2"}
 * )
 * @ORM\Entity
 */
class Returns implements JsonSerializable
{
    /**
     * @var \DateTimeInterface Creation Date
     *
     * @ORM\Column(name="created", type="datetime", options={"default": "CURRENT_TIMESTAMP"})
     * @Groups({"returns:read"})
     */
    public $created;

    /**
     * @var int|null Height of the Return
     *
     * @ORM\Column(name="height", type="smallint", options={"unsigned":true, "default": null}, nullable=true)
     */
    private $height;

    /**
     * @var int The entity Id
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(name="id", type="bigint", options={"unsigned"=true})
     * @Groups({"returns:read"})
     */
    public $id;

    /**
     * @ORM\ManyToOne(targetEntity=Users::class, inversedBy="returns")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     * @Groups({"returns:write"})
     * @SerializedName("user_id")
     */
    private $user;

    /**
     * @ORM\OneToMany(targetEntity=ReturnsImages::class, mappedBy="return", cascade={"All"})
     * @Groups({"returns:read", "returns:write"})
     */
    public $images;

    /**
     * @var int Invoiced flag
     *
     * @ORM\Column(name="invoiced", type="boolean", options={"unsigned"=true, "default"=0})
     * @Groups({"returns:read", "returns:write"})
     */
    public $invoiced = 0;

    /**
     * @var int|null Length of the Return
     *
     * @ORM\Column(name="length", type="smallint", options={"unsigned":true, "default": null}, nullable=true)
     */
    private $length;

    /**
     * @var string|null notes
     *
     * @ORM\Column(type="string", length=200, nullable=true)
     * @Groups({"returns:read", "returns:write"})
     */
    public $notes;

    /**
     * @var decimal The Process Fee
     *
     * @ORM\Column(name="process_fee", type="decimal", precision=3, scale=2)
     * @Groups({"returns:read", "returns:write"})
     */
    public $process_fee;

    /**
     * @var int|null reship_id
     *
     * @ORM\Column(name="reship_id", type="integer", nullable=true)
     * @Groups({"returns:read", "returns:write"})
     * @SerializedName("reshipment_id")
     */
    public $reship_id;

    /**
     * @var string|null The RMA number for this return.
     *
     * @ORM\Column(name="rma_number", type="string", length=32, nullable=true)
     * @Groups({"returns:read", "returns:write"})
     * @Assert\Length(max=32)
     */
    public $rma_number;

    /**
     * @var string status
     *
     * @ORM\Column(name="status", type="string", length=10, options={"fixed" = true})
     * @Groups({"returns:read", "returns:write"})
     */
    public $status;

    /**
     * @var string The tracking number for this return.
     *
     * @ORM\Column(name="tracking_number", type="string", length=40)
     * @Groups({"returns:read", "returns:write"})
     * @Assert\Length(max=40)
     */
    public $tracking_number;

    /**
     * @var decimal The Verify Fee
     *
     * @ORM\Column(name="verify_fee", type="decimal", precision=3, scale=2, options={"default": 0.0})
     * @Groups({"returns:read", "returns:write"})
     */
    public $verify_fee;

    /**
     * @var decimal The Weight
     *
     * @ORM\Column(name="weight", type="decimal", precision=5, scale=3, options={"unsigned"=true})
     * @Groups({"returns:read", "returns:write"})
     */
    public $weight;

    /**
     * @var int|null Width of the Return
     *
     * @ORM\Column(name="width", type="smallint", options={"unsigned":true, "default": null}, nullable=true)
     */
    private $width;

    public function __construct() {
        $this->created = new \DateTimeImmutable();
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

    public function jsonSerialize()
    {
        // Get related Image data.
        $imgs = $this->images;
        if (empty($imgs)) {
            $image_data = null;
        } else {
            $image_data = [];
            foreach ($imgs as $img) {
                $image_data[] = $img->getName();
            }
        }

        $data = [
            'created' => $this->created->format('Y-m-d h:m:s'),
            'id' => $this->id,
            'images' => $image_data,
            'notes' => $this->notes,
            'process_fee' => $this->process_fee,
            'reshipment_id' => $this->reship_id,
            'rma_number' => $this->rma_number,
            'status' => $this->status,
            'tracking_number' => $this->tracking_number,
            'verify_fee' => $this->verify_fee,
            'weight' => $this->weight,
        ];

        return ['return' => $data];
    }
}
