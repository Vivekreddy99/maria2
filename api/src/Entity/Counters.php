<?php
// api/src/Entity/Counters.php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Core\Annotation\ApiResource;

/**
 * ...
 *
 * @ApiResource
 */
class Counters
{
    /**
     * @var string carrier
     *
     * @ORM\Column(type="varchar")
     */
    public $carrier;

    /**
     * @var int number
     *
     * @ORM\Column(type="bigint")
     */
    public $number;

    /**
     * @var string|null description
     *
     * @ORM\Column(nullable=true)
     */
    public $description;

    /**
     * @var \DateTimeInterface|null last_reset
     *
     * @ORM\Column(nullable=true)
     */
    public $last_reset;

    public function getId(): ?int
    {
        return $this->id;
    }
}
