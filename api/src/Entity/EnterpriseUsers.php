<?php
// api/src/Entity/EnterpriseUsers.php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Entity\EntryPoints;
use App\Entity\Users;
use App\Entity\Manifests;
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
 *     normalizationContext={"groups"={"enterprise_users:read"}},
 *     denormalizationContext={"groups"={"enterprise_users:write"}},
 *     attributes={"route_prefix"="/v2"}
 * )
 * @ORM\Entity
 */
class EnterpriseUsers
{
    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity=Users::class)
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     * @Groups({"enterprise_users:write"})
     * @SerializedName("user_id")
     */
    public $user;

    /**
     * @var string FTP user
     *
     * @ORM\Column(name="ftp_usr", type="string", length=12)
     * @Groups({"enterprise_users:read", "enterprise_users:write"})
     * @Assert\Length(max=12)
     */
    public $ftp_usr;

    /**
     * @var decimal last_tracking_file
     *
     * @ORM\Column(name="last_tracking_file", type="decimal", precision=15, scale=5, options={"default": 0.00000})
     */
    public $last_tracking_file;

    public function getId(): ?int
    {
        return $this->id;
    }
}
