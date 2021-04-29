<?php

namespace App\Entity\Helper;

use App\Entity\Shipments;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Helper class for Shipments
 */
class Consignee
{
    /**
     * @var string The Consignee Name
     *
     * @Assert\NotBlank
     * @Assert\Type("string")
     * @Assert\Length(max=40)
     * @Groups({"shipments:read", "shipments:write", "orders:read", "orders:write"})
     */
    public $name;

    /**
     * @var string|null The Consignee Phone
     *
     * @Assert\Length(max=20)
     * @Groups({"shipments:read", "shipments:write", "orders:read", "orders:write"})
     */
    public $phone;

    /**
     * @var string|null The Consignee Email
     *
     * @Assert\Length(max=20)
     * @Assert\Email
     * @Groups({"shipments:read", "shipments:write", "orders:read", "orders:write"})
     */
    public $email;

    /**
     * @var string|null The Consignee Id
     *
     * @Assert\Length(max=20)
     * @Groups({"shipments:read", "shipments:write", "orders:read", "orders:write"})
     */
    public $id;

    /**
     * @var string The Consignee Street 1
     *
     * @Assert\NotBlank
     * @Assert\Type("string")
     * @Assert\Length(max=40)
     * @Groups({"shipments:read", "shipments:write", "orders:read", "orders:write"})
     */
    public $street1;

    /**
     * @var string|null The Consignee Street 2
     *
     * @Assert\Length(max=40)
     * @Groups({"shipments:read", "shipments:write", "orders:read", "orders:write"})
     */
    public $street2;

    /**
     * @var string The Consignee City
     *
     * @Assert\NotBlank
     * @Assert\Type("string")
     * @Assert\Length(max=40)
     * @Groups({"shipments:read", "shipments:write", "orders:read", "orders:write"})
     */
    public $city;

    /**
     * @var string|null The Consignee Province
     *
     * @Assert\Length(max=40)
     * @Groups({"shipments:read", "shipments:write", "orders:read", "orders:write"})
     */
    public $province;

    /**
     * @var string The Consignee Postal Code
     *
     * @Assert\NotBlank
     * @Assert\Type("string")
     * @Assert\Length(max=10)
     * @Groups({"shipments:read", "shipments:write", "orders:read", "orders:write"})
     */
    public $postal_code;

    /**
     * @var string The Consignee Country
     *
     * @Assert\Choice(callback="getCountryIsoCodes", message="The consignee country must be in the ISO 3166-1 alpha-2 format.")
     * @Groups({"shipments:read", "shipments:write", "orders:read", "orders:write"})
     */
    public $country;

    public function getName(): ?string {
        return $this->name;
    }

    public function setName(?string $name) {
        $this->name = $name;
    }

    public function getPhone(): ?string {
        return $this->phone;
    }

    public function setPhone(?string $phone) {
        $this->phone = $phone;
    }

    public function getEmail(): ?string {
        return $this->email;
    }

    public function setEmail(?string $email) {
        $this->email = $email;
    }

    public function getId(): ?string {
        return $this->id;
    }

    public function setId(?string $id) {
        $this->id = $id;
    }

    public function getStreet1(): ?string {
        return $this->street1;
    }

    public function setStreet1(?string $street1) {
        $this->street1 = $street1;
    }

    public function getStreet2(): ?string {
        return $this->street2;
    }

    public function setStreet2(?string $street2) {
        $this->street2 = $street2;
    }

    public function getCity(): ?string {
        return $this->city;
    }

    public function setCity(?string $city) {
        $this->city = $city;
    }

    public function getProvince(): ?string {
        return $this->province;
    }

    public function setProvince(?string $province) {
        $this->province = $province;
    }

    public function getPostalCode(): ?string {
        return $this->postal_code;
    }

    public function setPostalCode(?string $postal_code) {
        $this->postal_code = $postal_code;
    }

    public function getCountry(): ?string {
        return $this->country;
    }

    public function setCountry(?string $country) {
        $this->country = $country;
    }

    public static function getCountryIsoCodes() {
        return Shipments::getCountryIsoCodes();
    }
}
