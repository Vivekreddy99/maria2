<?php
// api/src/Entity/Apps.php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;
use ApiPlatform\Core\Annotation\ApiProperty;

/**
 *
 * @ApiResource(
 *     normalizationContext={"groups"={"apps:read"}},
 *     denormalizationContext={"groups"={"apps:write"}},
 *     attributes={"route_prefix"="/v2"}
 * )
 * @ORM\Entity
 */
class Apps
{
    /**
     * @var int Active flag.
     *
     * @ORM\Column(name="active", type="boolean", options={"unsigned"=true, "default"=1})
     * @Groups({"apps:read", "apps:write"})
     */
    public $active = 1;

    /**
     * @var \DateTimeInterface Creation Date
     *
     * @ORM\Column(name="created", type="datetime", options={"default": "CURRENT_TIMESTAMP"})
     * @Groups({"apps:read"})
     */
    private $created;

    /**
     * @var string The entity Id
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="string", length=16, options={"fixed" = true})
     * @Groups({"apps:read", "apps:write"})
     * @Assert\Length(min=3,max=16)
     */
    private $id;

    /**
     * @var string App name.
     *
     * @ORM\Column(name="name", type="string", length=64, options={"fixed"=true})
     * @Groups({"apps:read", "apps:write"})
     * @Assert\Length(min=3,max=64)
     */
    public $name;

    /**
     * @var string|null Secret.
     *
     * @ORM\Column(name="secret", type="string", length=36, options={"fixed"=true}, nullable=true)
     * @Groups({"apps:read", "apps:write"})
     * @Assert\Length(min=3,max=36)
     */
    public $secret;

    /**
     * @var Tokens
     * @ORM\OneToMany(targetEntity=Tokens::class, mappedBy="app")
     */
    protected $tokens;

    /**
     * @ORM\ManyToOne(targetEntity=Users::class, inversedBy="apps")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=true)
     * @Groups({"apps:write"})
     * @SerializedName("user_id")
     */
    private $user;

    public function __construct() {
        $this->created = new \DateTimeImmutable();
    }

    public function getId(): ?string
    {
        return $this->id;
    }
}
