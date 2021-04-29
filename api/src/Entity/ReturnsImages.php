<?php
// api/src/Entity/ReturnsImages.php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 *
 * @ApiResource(
 *     collectionOperations={
 *         "get",
 *     },
 *     itemOperations={
 *         "get",
 *         "delete",
 *         "put"
 *     },
 *     normalizationContext={"groups"={"returnsimages:read"}},
 *     denormalizationContext={"groups"={"returnsimages:write"}},
 *     attributes={"route_prefix"="/v2"}
 * )
 * @ORM\Entity
 */
class ReturnsImages
{
    private $img_root = "https://boxc.com/i/";

    /**
     * @var int The entity Id
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="string", length=32, options={"fixed" = true})
     * @Groups({"returnsimages:write"})
     * @Assert\Length(min=3,max=32)
     */
    public $id;

    /**
     * @var string Name of image.
     *
     * @Groups({"returns:read"})
     */
    public $name;

    /**
     * @var int Returns Id
     *
     * @ORM\ManyToOne(targetEntity=Returns::class, inversedBy="images")
     * @Groups({"returnsimages:write"})
     */
    public $return;

    /**
     * @var string ext
     *
     * @ORM\Column(name="ext", type="string", length=3, options={"fixed" = true})
     */
    public $ext;

    /**
     * @var \DateTimeInterface Creation Date
     *
     * @ORM\Column(name="created", type="datetime", options={"default": "CURRENT_TIMESTAMP"})
     * @Groups({"returnsimages:read"})
     */
    public $created;

    public function __construct() {
        $this->created = new \DateTimeImmutable();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->img_root . $this->id . $this->ext;
    }
}
