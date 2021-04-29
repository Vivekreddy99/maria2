<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;


/**
 *
 * @ApiResource(
 *     collectionOperations={"get"},
 *     itemOperations={"get"},
 *     attributes={"route_prefix"="/v2"}
 * )
 * @ORM\Entity
 * @ORM\Table(name="entry_points")
 */
class EntryPoints
{
    /**
     * @var string Entry Point city
     *
     * @ORM\Column(name="city", type="string", length=40)
     */
    public $city;

    /**
     * @var string The Entry Point country
     *
     * @ORM\Column(name="country", type="string", length=2, options={"fixed" = true})
     */
    public $country;

    /**
     * @var string Entry Point delivery address
     *
     * @ORM\Column(name="delivery_address", type="string", length=128)
     */
    public $delivery_address;

    /**
     * @var string Entry Point delivery address
     *
     * @ORM\Column(name="address", type="string", length=128)
     */
    public $address;

    /**
     * @var string The entity Id
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="string", length=6, options={"fixed" = true})
     */
    public $id;

    /**
     * @var string Entry Point name
     *
     * @ORM\Column(name="name", type="string", length=40)
     */
    public $name;

    /**
     * @var string Notes
     *
     * @ORM\Column(name="notes", type="string", length=128, nullable=true)
     */
    public $notes;

    /**
     * @var string Postal code
     *
     * @ORM\Column(name="postal_code", type="string", length=10, options={"fixed" = true}, nullable=true)
     */
    public $postal_code;

    /**
     * @var string Province
     *
     * @ORM\Column(name="province", type="string", length=40, nullable=true)
     */
    public $province;

    /**
     * @var string Street address 1
     *
     * @ORM\Column(name="street1", type="string", length=40)
     */
    public $street1;

    /**
     * @var string Street address 2
     *
     * @ORM\Column(name="street2", type="string", length=40, nullable=true)
     */
    public $street2;

    /**
     * @var int Active flag
     *
     * @ORM\Column(name="active", type="boolean", options={"unsigned"=true})
     */
    private $active;

    /**
     * @var decimal Entry Point latitude
     *
     * @ORM\Column(name="latitude", type="decimal", precision=8, scale=5)
     */
    private $latitude;

    /**
     * @var decimal Entry Point longitude
     *
     * @ORM\Column(name="longitude", type="decimal", precision=8, scale=5)
     */
    private $longitude;

    /**
     * @var string Entry Point timezone
     *
     * @ORM\Column(name="timezone", type="string", length=30)
     */
    private $timezone;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function setActive($active)
    {
        $this->active = $active;
    }

    public function setLatitude($latitude)
    {
        $this->latitude = $latitude;
    }

    public function setLongitude($longitude)
    {
        $this->longitude = $longitude;
    }

    public function setTimezone($timezone)
    {
        $this->timezone = $timezone;
    }
}
