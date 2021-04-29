<?php
// api/src/Entity/Transactions.php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Validator\Exception;
use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;

/**
 * @ApiResource(
 *     collectionOperations={
 *         "get"
 *     },
 *     itemOperations={
 *         "get"
 *     },
 *     normalizationContext={"groups"={"transactions:read"}},
 *     attributes={"route_prefix"="/v2"}
 * )
 *
 * @ApiResource(attributes={"pagination_items_per_page"=50})
 */
class Transaction
{
    /**
     * @var float Amount
     *
     * @Groups({"transactions:read"})
     */
    public $amount;

    /**
     * @var float Balance
     *
     * @Groups({"transactions:read"})
     */
    public $balance;

    /**
     * @var \DateTimeInterface
     *
     * @Groups({"transactions:read"})
     */
    public $datetime;

    /**
     * @var string Transaction type
     *
     * @Groups({"transactions:read"})
     * @Assert\Choice(choices={"Credit", "Debit"}, message="Terms must be either Credit or Debit")
     */
    public $type;

    /**
     * @var string Transaction type
     *
     * @Groups({"transactions:read"})
     * @Assert\Choice(choices={"Credit", "Debit"}, message="Terms must be either Credit or Debit")
     */
    public $account;

    /**
     * @var string Currency
     *
     * @Groups({"transactions:read"})
     * @Assert\Length(min=3,max=3)
     */
    public $currency;

    /**
     * @var int The entity Id
     *
     * @Groups({"statements:read"})
     */
    public $id;

    /**
     * @ORM\ManyToOne(targetEntity=Users::class, inversedBy="statements")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     * @SerializedName("user_id")
     */
    private $user;

    public function getId(): ?int
    {
        return $this->id;
    }
    /*
    public function getUser(): Users
    {
        return $this->user;
    }
    */

    public function setUser(Users $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getAccount(): string
    {
        return $this->user->getName();
    }

}
