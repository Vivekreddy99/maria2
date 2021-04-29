<?php
// api/src/Entity/Tokens.php

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
 *     normalizationContext={"groups"={"tokens:read"}},
 *     denormalizationContext={"groups"={"tokens:write"}},
 *     attributes={"route_prefix"="/v2"}
 * )
 * @ORM\Entity
 */
class Tokens
{

    /**
     * @ORM\ManyToOne(targetEntity=Apps::class, inversedBy="tokens")
     * @Groups({"tokens:read", "tokens:write"})
     * @SerializedName("app_id")
     */
    public $app;

    /**
     * @var \DateTimeInterface Creation Date
     *
     * @ORM\Column(name="created", type="datetime", options={"default": "CURRENT_TIMESTAMP"})
     * @Groups({"tokens:read"})
     */
    private $created;

    /**
     * @var string The entity Id
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="string", length=64, options={"fixed" = true})
     * @Groups({"tokens:read", "tokens:write"})
     * @Assert\Length(min=3,max=64)
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Users::class, inversedBy="tokens")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=true)
     * @Groups({"tokens:write"})
     * @SerializedName("user_id")
     */
    public $user;

    /**
     * @var string|null nonce
     *
     * @ORM\Column(name="nonce", type="string", length=32, options={"fixed" = true}, nullable=true)
     * @Groups({"tokens:read", "tokens:write"})
     * @Assert\Length(min=3,max=32)
     */
    public $nonce;

    public function __construct() {
        $this->created = new \DateTimeImmutable();
    }

    public function getId(): ?string
    {
        return $this->id;
    }
}
