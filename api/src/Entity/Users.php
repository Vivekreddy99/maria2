<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Entity\Apps;
use App\Entity\Charges;
use App\Entity\Inbound;
use App\Entity\Manifests;
use App\Entity\Overpacks;
use App\Entity\Packaging;
use App\Entity\Shipments;
use App\Entity\Shops;
use App\Entity\Statements;
use App\Entity\Tokens;
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
 *     normalizationContext={"groups"={"users:read"}},
 *     denormalizationContext={"groups"={"users:write"}},
 *     attributes={"route_prefix"="/v2"}
 * )
 * @ORM\Entity
 */
class Users implements UserInterface {

    /**
     * @var int The entity Id
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(name="id", type="integer", options={"unsigned"=true})
     * @Groups({"users:read", "users:write", "labels:read", "labels:write", "shipments:read", "shipments:write", "overpacks:read", "overpacks:write", "manifests:read", "manifests:write"})
     */
    private $id;

    /**
     * @var Apps
     * @ORM\OneToMany(targetEntity=Apps::class, mappedBy="user")
     */
    protected $apps;

    /**
     * @var Charges
     * @ORM\OneToMany(targetEntity=Charges::class, mappedBy="user")
     */
    protected $charges;

    /**
     * @var Inbound
     * @ORM\OneToMany(targetEntity=Inbound::class, mappedBy="user")
     */
    protected $inbound;

    /**
     * @var Statements
     * @ORM\OneToMany(targetEntity=Statements::class, mappedBy="user")
     */
    protected $statements;

    /**
     * @var Manifests
     * @ORM\OneToMany(targetEntity=Manifests::class, mappedBy="user")
     */
    protected $manifests;

    /**
     * @var Overpacks
     * @ORM\OneToMany(targetEntity=Overpacks::class, mappedBy="user")
     */
    protected $overpacks;

    /**
     * @var Packaging
     * @ORM\OneToMany(targetEntity=Packaging::class, mappedBy="user")
     */
    protected $packaging;

    /**
     * @var Products
     * @ORM\OneToMany(targetEntity=Products::class, mappedBy="user")
     */
    protected $products;

    /**
     * @var Reshipments
     * @ORM\OneToMany(targetEntity=Reshipments::class, mappedBy="user")
     */
    protected $reshipments;

    /**
     * @var Shipments
     * @ORM\OneToMany(targetEntity=Shipments::class, mappedBy="user")
     */
    protected $shipments;

    /**
     * @var Shipments
     * @ORM\OneToMany(targetEntity=Shops::class, mappedBy="user")
     */
    protected $shops;

    /**
     * @var Tokens
     * @ORM\OneToMany(targetEntity=Tokens::class, mappedBy="user")
     */
    protected $tokens;

    /**
     * @var WarehousesStorage
     * @ORM\OneToMany(targetEntity=WarehousesStorage::class, mappedBy="user")
     */
    protected $warehouses_storage;

    /**
     * @var Returns
     * @ORM\OneToMany(targetEntity=Returns::class, mappedBy="user")
     */
    protected $returns;

    /**
     * ORM\Column(type="string", unique=true, nullable=true)
     */
    private $apiToken;

    /**
     * @return string
     */
    public function getApiToken()
    {
        return $this->apiToken;
    }

    /**
     * @param string $apiToken
     */
    public function setApiToken($apiToken): self
    {
        $this->apiToken = $apiToken;

        return $this;
    }

    /**
     * @var string The User's Email
     *
     * @Groups({"users:read", "users:write"})
     * @ORM\Column(name="email", type="string", length=64)
     */
    private $email;

    /**
     * @var string The Username
     *
     * @ORM\Column(name="username", type="string", length=64)
     */
    private $username;

    /**
     * @var string The User's hashed password
     *
     * @ORM\Column(name="password", type="string", length=200)
     */
    private $password;

    /**
     * @var string The User's plain text password
     *
     * @Groups({"users:write"})
     * @SerializedName("password")
     */
    public $passNotEncrypted;

    private $roles;

    /**
     * @var int Active flag
     *
     * @ORM\Column(name="active", type="boolean", options={"unsigned"=true, "default"=1})
     * @Groups({"users:read", "users:write"})
     */
    private $active = 1;

    /**
     * @var string The User's first name
     *
     * @Groups({"users:read", "users:write"})
     * @ORM\Column(name="fname", type="string", length=20)
     */
    private $fname;

    /**
     * @var string The User's last name
     *
     * @Groups({"users:read", "users:write"})
     * @ORM\Column(name="lname", type="string", length=20)
     */
    private $lname;

    /**
     * @var string The User's phone
     *
     * @Groups({"users:read", "users:write"})
     * @ORM\Column(name="phone", type="string", length=20, nullable=true)
     */
    private $phone;

    /**
     * @var string The User's website
     *
     * @Groups({"users:read", "users:write"})
     * @ORM\Column(name="website", type="string", length=64, nullable=true)
     */
    private $website;

    /**
     * @var decimal The User's balance as decimal
     *
     * @ORM\Column(name="balance", type="decimal", precision=9, scale=2, options={"default"=0.0})
     * @Groups({"users:read", "users:write"})
     */
    private $balance = 0.0;

    /**
     * @var decimal The User's credit limit as decimal
     *
     * @ORM\Column(name="credit_limit", type="decimal", precision=9, scale=2, options={"default"=0.0})
     * @Groups({"users:read", "users:write"})
    */
    private $credit_limit = 0.0;

    /**
     * @var decimal The User's discount as decimal
     *
     * @ORM\Column(name="discount", type="decimal", precision=9, scale=2, options={"default"=0.0})
     * @Groups({"users:read", "users:write"})
     */
    private $discount = 0.0;

    /**
     * @var decimal The User's FBB discount as decimal
     *
     * @ORM\Column(name="fbb_discount", type="decimal", precision=9, scale=2, options={"default"=0.0})
     * @Groups({"users:read", "users:write"})
     */
    private $fbb_discount = 0.0;

    /**
     * @var string The User's language
     *
     * @ORM\Column(name="language", type="string", length=2, options={"fixed" = true})
     * @Groups({"users:read", "users:write"})
     */
    private $language;

    /**
     * @var string The User's Xero id
     *
     * @ORM\Column(name="xero_id", type="string", length=36, options={"fixed" = true}, nullable=true)
     * @Groups({"users:read", "users:write"})
     */
    private $xero_id;

    /**
     * @var int Rbb daily flag
     *
     * @ORM\Column(name="rbb_daily", type="boolean", options={"unsigned"=true, "default"=1})
     */
    private $rbb_daily = 1;

    /**
     * @var int User's custom logo
     *
     * @ORM\Column(name="custom_logo", type="boolean", options={"unsigned"=true, "default"=0})
     */
    private $custom_logo = 0;

    /**
     * @var int User's balance alert
     *
     * @ORM\Column(name="balance_alert", type="smallint", options={"unsigned"=true, "default"=100})
     */
    private $balance_alert = 100;

    /**
     * @var int User's request count
     *
     * @ORM\Column(name="requests", type="smallint", options={"unsigned"=true, "default"=0})
     */
    private $requests = 0;

    /**
     * @var int User's maximum number of requests
     *
     * @ORM\Column(name="max_requests", type="smallint", options={"unsigned"=true, "default"=40})
     */
    private $max_requests = 40;

    /**
     * @var datetime User's last request date
     *
     * @ORM\Column(name="last_request", type="datetime", options={"default": "CURRENT_TIMESTAMP"})
     * @Groups({"users:read", "users:write"})
     */
    private $last_request;

    /**
     * @var string The User's Street 1
     *
     * @ORM\Column(name="street1", type="string", length=40, nullable=true)
     * @Groups({"users:read", "users:write"})
     * @Assert\Length(max=40)
     */
    private $street1;

    /**
     * @var string The User's Street 2
     *
     * @ORM\Column(name="street2", type="string", length=40, nullable=true)
     * @Groups({"users:read", "users:write"})
     * @Assert\Length(max=40)
     */
    private $street2;

    /**
     * @var string The User's City
     *
     * @ORM\Column(name="city", type="string", length=40, nullable=true)
     * @Groups({"users:read", "users:write"})
     * @Assert\Length(max=40)
     */
    private $city;

    /**
     * @var string The User's Province
     *
     * @ORM\Column(name="province", type="string", length=40, nullable=true)
     * @Groups({"users:read", "users:write"})
     * @Assert\Length(max=40)
     */
    private $province;

    /**
     * @var string The User's Postal Code
     *
     * @ORM\Column(name="postal_code", type="string", length=10, nullable=true)
     * @Groups({"users:read", "users:write"})
     * @Assert\Length(max=10)
     */
    private $postal_code;

    /**
     * @var string The User's Country
     *
     * @ORM\Column(name="country", type="string", length=2, options={"fixed" = true, "default" = "US"})
     * @Groups({"users:read", "users:write"})
     * @Assert\Choice(callback="getCountryIsoCodes", message="The country of origin or where the product was manufactured must be in the ISO 3166-1 alpha-2 format.")
     */
    private $country;

    /**
     * @var int CC Enabled flag
     *
     * @ORM\Column(name="cc_enabled", type="boolean", options={"unsigned"=true, "default"=1})
     * @Groups({"users:read", "users:write"})
     */
    private $cc_enabled = 1;

    /**
     * @var string The User's Company
     *
     * @ORM\Column(name="company", type="string", length=40, nullable=true)
     * @Groups({"users:read", "users:write"})
     * @Assert\Length(max=40)
     */
    private $company;

    /**
     * @var string The User's Consignor Name
     *
     * @ORM\Column(name="con_name", type="string", length=40)
     * @Groups({"users:read", "users:write"})
     * @Assert\Length(max=40)
     */
    public $con_name;

    /**
     * @var string The User's Consignor Street 1
     *
     * @ORM\Column(name="con_street1", type="string", length=40, nullable=true)
     * @Groups({"users:read", "users:write"})
     * @Assert\Length(max=40)
     */
    private $con_street1;

    /**
     * @var string The User's Consignor Street 2
     *
     * @ORM\Column(name="con_street2", type="string", length=40, nullable=true)
     * @Groups({"users:read", "users:write"})
     * @Assert\Length(max=40)
     */
    private $con_street2;

    /**
     * @var string The User's Consignor City
     *
     * @ORM\Column(name="con_city", type="string", length=40, nullable=true)
     * @Groups({"users:read", "users:write"})
     * @Assert\Length(max=40)
     */
    private $con_city;

    /**
     * @var string The User's Consignor Province
     *
     * @ORM\Column(name="con_province", type="string", length=40, nullable=true)
     * @Groups({"users:read", "users:write"})
     * @Assert\Length(max=40)
     */
    private $con_province;

    /**
     * @var string The User's Consignor Postal Code
     *
     * @ORM\Column(name="con_postal_code", type="string", length=10, nullable=true)
     * @Groups({"users:read", "users:write"})
     * @Assert\Length(max=10)
     */
    private $con_postal_code;

    /**
     * @var string The User's Consignor Country
     *
     * @ORM\Column(name="con_country", type="string", length=2, options={"fixed" = true}, nullable=true)
     * @Groups({"users:read", "users:write"})
     * @Assert\Choice(callback="getCountryIsoCodes", message="The country must be in the ISO 3166-1 alpha-2 format.")
     */
    private $con_country;

    /**
     * @var datetime User's created date
     *
     * @ORM\Column(name="created", type="datetime", options={"default": "CURRENT_TIMESTAMP"})
     * @Groups({"users:read", "users:write"})
     */
    private $created;

    /**
     * @var string User metadata
     *
     * @ORM\Column(name="metadata", type="json", nullable=true)
     * @Groups({"users:read", "users:write"})
     */
    public $metadata;

    public function __construct() {
        $this->created = new \DateTimeImmutable();
        $this->last_request = new \DateTimeImmutable();
        $this->shipments = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email) :self
    {
        $this->email = $email;

        return $this;
    }

    public function setUsername(string $username) :self
    {
        $this->username = $username;

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @return string
     */
    public function getPassNotEncrypted(): ?string
    {
        return $this->passNotEncrypted;
    }

    public function setPassNotEncrypted(string $passNotEncrypted): self
    {
        $this->passNotEncrypted = $passNotEncrypted;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials() {
        //If you temporarily store any sensitive data on the user, clear it.
        $this->passNotEncrypted = null;
    }

    public function getActive(): int
    {
        return $this->active;
    }

    public function getFname(): string
    {
        return $this->fname;
    }

    public function setFname(string $fname)
    {
        $this->fname = $fname;

        return $this;
    }

    public function getLname(): string
    {
        return $this->lname;
    }

    public function setLname(string $lname)
    {
        $this->lname = $lname;

        return $this;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function setLanguage(string $language)
    {
        $this->language = $language;

        return $this;
    }

    public function getCountry(): string
    {
        return $this->country;
    }

    public function setCountry(string $country) :self
    {
        $this->country = $country;

        return $this;
    }

    public function getConName(): string
    {
        return $this->con_name;
    }

    public function setConName(string $con_name) {
        $this->con_name = $con_name;

        return $this;
    }

    public function getMetadata(): ?string
    {
        return $this->metadata;
    }

    public function setMetadata(string $metadata) {
        $this->metadata = $metadata;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function getBalance(): float
    {
        return $this->balance;
    }

    public function getCreditLimit(): float
    {
        return $this->credit_limit;
    }

    public function getDiscount(): float
    {
        return $this->discount;
    }

    public function getFbbDiscount(): float
    {
        return $this->fbb_discount;
    }

    public function getMaxRequests(): int
    {
        return $this->max_requests;
    }

    public function getRequests(): int
    {
        return $this->requests;
    }

    public static function getCountryIsoCodes() {
        return Shipments::getCountryIsoCodes();
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;

        // Guarantee every user has a role
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @see UserInterface
     */
    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getSalt()
    {
        // Not needed when implementing "bcrypt" algorithm in security.yaml.
    }

    /**
     * @see UserInterface
     */
    public function getUsername()
    {
        return (string) $this->email;
    }

    public function getUserObject()
    {
        return $this;
    }

    public function getShipments() :Collection
    {
         return $this->shipments;
    }

    public function __toString() {
        return (string) $this->email;
    }
}
