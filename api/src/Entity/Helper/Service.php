<?php
// api/src/Entity/Helper/Service.php

namespace App\Entity\Helper;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Entity\EntryPoints;
use App\Entity\Manifests;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;
use ApiPlatform\Core\Annotation\ApiProperty;
use ArrayAccess;

/**
 * Helper class for Estimate
 */
class Service implements \Iterator, ArrayAccess
{

    /**
     * @var decimal The Chargeable Weight
     *
     * @ORM\Column(name="chargeable_weight", type="decimal", precision=7, scale=3, options={"unsigned"=true}, nullable=true)
     * @Groups({"estimate:read"})
     */
    private $chargeable_weight;

    /**
     * @var decimal The Cost (3.58)
     *
     * @ORM\Column(name="cost", type="decimal", precision=12, scale=2, options={"default": 0.0})
     * @Groups({"estimate:read"})
     */
    public $cost = 0.0;

    /**
     * @var decimal The Oversize Fee
     *
     * @ORM\Column(name="oversize_fee", type="decimal", precision=3, scale=2, options={"default": 0.0})
     * @Groups({"estimate:read"})
     */
    public $oversize_fee = 0.0;

    /**
     * @var string The Service used
     *
     * @ORM\Column(name="service", type="string", length=16)
     * @Groups({"estimate:read"})
     * @Assert\Length(max=16)
     * @Assert\Choice(callback="getServicesList", message="Service must be one of these options: BoxC Post, BoxC Parcel, BoxC Plus, BoxC Priority")
     */
    public $service;

    /**
     * @var decimal The Total Cost
     *
     * @ORM\Column(name="total_cost", type="decimal", precision=12, scale=2, options={"default": 0.0})
     * @Groups({"estimate:read"})
     */
    public $total_cost = 0.0;

    /**
     * @var int Transit Min
     *
     * @ORM\Column(name="transit_min", type="smallint", options={"unsigned":true, "default": 0})
     * @Groups({"estimate:read"})
     */
    public $transit_min = 0;

    /**
     * @var int Transit Max
     *
     * @ORM\Column(name="transit_min", type="smallint", options={"unsigned":true, "default": 0})
     * @Groups({"estimate:read"})
     */
    public $transit_max = 0;

    /**
     * @var string Metadata
     *
     * @ORM\Column(name="metadata", type="json")
     * @Groups({"estimate:read"})
     */
    public $meta_data;

    /**
     * @var int The entity Id
     *
     * @Groups({"estimate:read"})
     * @Assert\Valid()
     */
    private $id;

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return float
     */
    public function getChargeableWeight(): float
    {
        return $this->chargeable_weight;
    }

    /**
     * @param float $chargeable_weight
     */
    public function setChargeableWeight(float $chargeable_weight): void
    {
        $this->chargeable_weight = $chargeable_weight;
    }

    /**
     * @return float
     */
    public function getCost(): float
    {
        return $this->cost;
    }

    /**
     * @param float $cost
     */
    public function setCost(float $cost): void
    {
        $this->cost = $cost;
    }

    /**
     * @return float
     */
    public function getOversizeFee(): float
    {
        return $this->oversize_fee;
    }

    /**
     * @param float $oversize_fee
     */
    public function setOversizeFee(float $oversize_fee): void
    {
        $this->oversize_fee = $oversize_fee;
    }

    /**
     * @return string
     */
    public function getService(): string
    {
        return $this->service;
    }

    /**
     * @param string $service
     */
    public function setService(string $service): void
    {
        $this->service = $service;
    }

    /**
     * @return float
     */
    public function getTotalCost(): float
    {
        return $this->total_cost;
    }

    /**
     * @param float $total_cost
     */
    public function setTotalCost(float $total_cost): void
    {
        $this->total_cost = $total_cost;
    }

    /**
     * @return int
     */
    public function getTransitMin(): int
    {
        return $this->transit_min;
    }

    /**
     * @param int $transit_min
     */
    public function setTransitMin(int $transit_min): void
    {
        $this->transit_min = $transit_min;
    }

    /**
     * @return int
     */
    public function getTransitMax(): int
    {
        return $this->transit_max;
    }

    /**
     * @param int $transit_max
     */
    public function setTransitMax(int $transit_max): void
    {
        $this->transit_max = $transit_max;
    }

    public function getMetadata(): ?string {
        return $this->meta_data;
    }

    public function setMetadata(string $meta_data) {
        $this->meta_data = $meta_data;
    }

    public function offsetExists($offset)
    {
        if (isset($this->$offset)) {
            return true;
        } else {
            return false;
        }
    }

    public function offsetGet($offset)
    {
        return $this->$offset;
    }

    public function offsetSet($offset, $value)
    {
        // die("In Offset Set");
        // TODO: Implement offsetSet() method.
    }

    public function offsetUnset($offset)
    {
        // die("In Offset Unset");
        // TODO: Implement offsetUnset() method.
    }

    public function current()
    {
        // die("In Current");
        // TODO: Implement current() method.
    }

    public function next()
    {
        // die("In Next");
        // TODO: Implement next() method.
    }

    public function key()
    {
        // die("In Key");
        // TODO: Implement key() method.
    }

    public function valid()
    {
        // die("In Valid");
        // TODO: Implement valid() method.
    }

    public function rewind()
    {
        // die("In rewind.");
        // TODO: Implement rewind() method.
    }

    public static function getServicesList() {
        return ['BoxC Parcel', 'BoxC Post', 'BoxC Plus', 'BoxC Priority'];
    }

    /**
     * Retrieve the exchange rate for a currency
     *
     * @throws \Exception
     * @param string $from
     * @param string $to
     * @return float|int
     */
    public function exchangeRate($from, $to)
    {
        return 0;
    }

}
