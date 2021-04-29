<?php
// api/src/Entity/subscriptions.php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Core\Annotation\ApiResource;

/**
 * ...
 *
 * @ApiResource
 */
class Subscriptions
{
    /**
     * @var string id
     *
     * @ORM\Column(type="char")
     */
    private $id;

    /**
     * @var int user_id
     *
     * @ORM\Column(type="int")
     */
    public $user_id;

    /**
     * @var string app_id
     *
     * @ORM\Column(type="char")
     */
    public $app_id;

    /**
     * @var string name
     *
     * @ORM\Column(type="varchar")
     */
    public $name;

    /**
     * @var string status
     *
     * @ORM\Column(type="char")
     */
    public $status;

    /**
     * @var decimal amount
     *
     * @ORM\Column(type="decimal")

    public $amount;
     */

    /**
     * @var string currency
     *
     * @ORM\Column(type="char")
     */
    public $currency;

    /**
     * @var \DateTimeInterface created
     *
     * @ORM\Column(type="datetime")
     */
    public $created;

    /**
     * @var \DateTimeInterface|null cancelled_at
     *
     * @ORM\Column(nullable=true)
     */
    public $cancelled_at;

    /**
     * @var \DateTimeInterface current_period_end
     *
     * @ORM\Column(type="datetime")
     */
    public $current_period_end;

    /**
     * @var \DateTimeInterface current_period_start
     *
     * @ORM\Column(type="datetime")
     */
    public $current_period_start;

    public function getId(): ?string
    {
        return $this->id;
    }
}
