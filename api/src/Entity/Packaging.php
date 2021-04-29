<?php
// api/src/Entity/Packaging.php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Entity\EntryPoints;
use App\Entity\Shipments;
use App\Entity\Manifests;
use App\Entity\Warehouses;
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
 *     normalizationContext={"groups"={"packaging:read"}},
 *     denormalizationContext={"groups"={"packaging:write"}},
 *     attributes={"route_prefix"="/v2"}
 * )
 * @ORM\Entity
 */
class Packaging
{
    /**
     * @var int The entity Id
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(name="id", type="integer", options={"unsigned"=true})
     * @Groups({"packaging:read", "packaging:write"})
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Users::class, inversedBy="packaging")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     * @Groups({"packaging:write"})
     * @SerializedName("user_id")
     */
    private $user;

    /**
     * @var string Description.
     *
     * @ORM\Column(name="description", type="string", length=255, nullable=true)
     * @Groups({"packaging:read", "packaging:write"})
     * @Assert\Length(max=255)
     */
    public $description;

    /**
     * @var decimal The weight.
     *
     * @ORM\Column(name="weight", type="decimal", precision=5, scale=3, options={"unsigned"=true, "default"=0.000})
     * @Groups({"packaging:read", "packaging:write"})
     */
    public $weight = 0.000;

    /**
     * @var decimal The length.
     *
     * @ORM\Column(name="length", type="decimal", precision=4, scale=1, options={"unsigned"=true, "default"=0.0})
     * @Groups({"packaging:read", "packaging:write"})
     */
    public $length = 0.0;

    /**
     * @var decimal width
     *
     * @ORM\Column(name="width", type="decimal", precision=4, scale=1, options={"unsigned"=true, "default"=0.0})
     * @Groups({"packaging:read", "packaging:write"})
     */
    public $width = 0.0;

    /**
     * @var decimal height
     *
     * @ORM\Column(name="height", type="decimal", precision=4, scale=1, options={"unsigned"=true, "default"=0.0})
     * @Groups({"packaging:read", "packaging:write"})
     */
    public $height = 0.0;

    /**
     * @var \DateTimeInterface Creation Date
     *
     * @ORM\Column(name="created", type="datetime", options={"default": "CURRENT_TIMESTAMP"})
     * @Groups({"packaging:read"})
     */
    public $created;

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
}
