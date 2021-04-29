<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Entity\EntryPoints;
use App\Entity\Helper\Service;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Validator\Exception;
use ApiPlatform\Core\Annotation\ApiFilter;
use App\Controller\EstimateController;


/**
 *
 * @ApiResource(
 *     collectionOperations={
 *         "get"={
 *              "path"="/estimate",
 *         }
 *     },
 *     itemOperations={
 *         "get"
 *     },
 *     normalizationContext={"groups"={"estimate:read"}},
 *     attributes={"route_prefix"="/v2"},
 * )
 * @ORM\Entity
 */
class Estimate
{
    /**
     * @var string Non-IRI Entry Point value
     *
     * @ORM\Column(name="entry_point", type="string", length=6, options={"fixed" = true})
     * @Groups({"estimate:read"})
     */
    public $entry_point;

    /**
     * @var string The 3-letter currency code. Default: "USD".
     *
     * @ORM\Column(name="currency", type="string", length=3, options={"fixed" = true, "default": "USD"})
     * @Groups({"estimate:read"})
     */
    public $currency = 'USD';

    /**
     * @var string
     *
     * @ORM\Column(name="services", type="json", nullable=true)
     * @Groups({"estimate:read"})
     */
    public $services;

    /**
     * @var Service[] Array of services
     *
     * @Assert\All(
     *     constraints = @Assert\Collection(
     *         fields = {
     *             "total_cost" = { @Assert\Positive, @Assert\Length(max=12, maxMessage="Value is too high.") },
     *             "chargeable_weight" = { @Assert\Positive, @Assert\Length(max=12, maxMessage="Weight value is too high.") }
     *         },
     *         allowMissingFields = true,
     *         allowExtraFields = true
     *     )
     * )
     */
    private $services_arr;


    /**
     * @var int The entity Id
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(name="id", type="bigint", options={"unsigned"=true})
     * @Groups({"estimate:read"})
     */
    public $id;

    /**
     * The following fields are used in filters.
     */

    /**
     * @var string The Country
     *
     * @ORM\Column(name="country", type="string", length=2, options={"fixed" = true})
     * @Groups({"estimate:read"})
     * @Assert\Choice(groups={"getValidation"},callback="getCountryIsoCodes", message="The country must be in the ISO 3166-1 alpha-2 format.")
     */
    public $country;

    /**
     * @var string[] The DG Codes
     */
    public $dg_codes = [];

    /**
     * @var string
     *
     * @ORM\Column(name="dg_code", type="string", length=255)
     * @Assert\Choice(callback="getDgCodeOptions", message="The dangerous goods code must be in the BoxC or IATA dangerous goods code format.")
     */
     public $dg_code;

    /**
     * @var decimal The Height as decimal
     *
     * @ORM\Column(name="height", type="decimal", precision=4, scale=1, options={"default"=0})
     */
    public $height = 0.0;

    /**
     * @var decimal The Length as decimal
     *
     * @ORM\Column(name="length", type="decimal", precision=4, scale=1, options={"default"=0})
     */
    public $length = 0.0;

    /**
     * @var string The Postal Code
     *
     * @ORM\Column(name="postal_code", type="string", length=10, nullable=true)
     * @Assert\Length(max=10)
     */
    public $postal_code;

    /**
     * @var string The Service used
     *
     * @ORM\Column(name="service", type="string", length=16)
     * @Assert\Length(max=16)
     * @Assert\Choice(choices={"BoxC Post", "BoxC Parcel", "BoxC Plus", "BoxC Priority"}, message="Service must be one of these options: BoxC Post, BoxC Parcel, BoxC Plus, BoxC Priority")
     */
    public $service;

    /**
     * @var boolean Request signature confirmation from the recipient upon delivery. Not available for all services or routes. Additional fees apply. Default is false.
     *
     * @ORM\Column(name="sig_con", type="boolean", options={"unsigned"=true, "default"=false})
     * @SerializedName("signature_confirmation")
     */
    public $signature_confirmation = false;

    /**
     * @var decimal The Weight as decimal
     *
     * @ORM\Column(name="weight", type="decimal", precision=7, scale=3)
     */
    public $weight = 0;

    /**
     * @var decimal The Width as decimal
     *
     * @ORM\Column(name="width", type="decimal", precision=4, scale=1)
     */
    public $width = 0;

    /**
     */
    private $user_id;

    /**
     * @return string
     *
     * @Assert\Choice(groups={"getValidation"},callback="getCountryIsoCodes", message="The country must be in the ISO 3166-1 alpha-2 format.")
     */
    public function getCountry(): string
    {
        return $this->country;
    }

    /**
     * @param string $country
     */
    public function setCountry(string $country): void
    {
        if (in_array($country, self::getCountryIsoCodes())) {
            $this->country = $country;
        } else {
            // TODO: throw exception.
        }

    }

    public function setDgCodes(array $dg_codes) {

        foreach($dg_codes as $dg_code) {
            if (!in_array($dg_code, $this->getDgCodeOptions())) {
                unset($dg_codes[$dg_code]);
            } else {
                // TODO: throw exception.
            }
        }

        $this->dg_codes = $dg_codes;
    }


    /**
     * @return float
     */
    public function getHeight(): float
    {
        return $this->height;
    }

    /**
     * @param float $height
     * @param float $height
     */
    public function setHeight(float $height): void
    {
        $this->height = $height;
    }

    /**
     * @return float
     */
    public function getLength(): float
    {
        return $this->length;
    }

    /**
     * @param float $length
     */
    public function setLength(float $length): void
    {
        $this->length = $length;
    }

    /**
     * @return string
     */
    public function getPostalCode(): string
    {
        return $this->postal_code;
    }

    /**
     * @param string $postal_code
     */
    public function setPostalCode(string $postal_code): void
    {
        $this->postal_code = $postal_code;
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
     * @return boolean
     */
    public function getSignatureConfirmation()
    {
        return $this->signature_confirmation;
    }

    /**
     * @param boolean $signature_confirmation
     */
    public function setSignatureConfirmation(string $signature_confirmation): void
    {
        $this->signature_confirmation = $signature_confirmation == "true" ? true : false;
    }

    /**
     * @return float
     */
    public function getWeight(): float
    {
        return $this->weight;
    }

    /**
     * @param float $weight
     */
    public function setWeight(float $weight): void
    {
        $this->weight = $weight;
    }

    /**
     * @return float
     */
    public function getWidth(): float
    {
        return $this->width;
    }

    /**
     * @param float $width
     */
    public function setWidth(float $width): void
    {
        $this->width = $width;
    }

    public function __construct() {

    }

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     */
    public function setCurrency(string $currency): void
    {
        $this->currency = $currency;
    }

    public function getUser(): Users
    {
        return $this->user;
    }

    public function setUser(Users $user): self
    {
        $this->user = $user;

        return $this;
    }

    // From api/v2/addresses.json
    public static function getCountryIsoCodes() {
        return Shipments::getCountryIsoCodes();
    }

    /**
     * @param $dg_code
     *
     * @Assert\Choice(callback="getDgCodeOptions", message="The dangerous goods code must be in the BoxC or IATA dangerous goods code format.")
     */
    public function getDgCode() {
        return $this->dg_code;
    }


    public function setDgCode($dg_code) {
        $this->dg_code = $dg_code;
    }

    public function getDgCodeOptions() {
        return ['0965', '0966', '0967','0968','0969','0970', 'ORMD1', 'ORMD2', 'ORMD3'];
    }

    /**
     * @return string|null
     */
    public function getEntryPoint(): ?string {
        return $this->entry_point;
    }

    public function setEntryPoint(string $entry_point) {
        $this->entry_point = $entry_point;
    }

    public function getServices() {
        return $this->services;
    }

    public function setServices($services) {
        $this->services = $services;
    }

    public function getServicesArr() {

        return $this->services_arr;
    }

    public function setServicesArr() {

        // Get a list of all services.
        $list = Service::getServicesList();

        // If service has been identified, just return that one.
        if (in_array($this->service, $list)) {
            $list = [$this->service];
        }

        $arr = [];
        foreach ($list as $svc) {
            $temp = new Service();
            $temp->setChargeableWeight(0.0);
            $temp->setCost(0.0);
            $temp->setMetadata("");
            $temp->setOversizeFee(0.0);
            $temp->setService($svc);
            $temp->setTotalCost(0.0);
            $temp->setTransitMax(0);
            $temp->setTransitMin(0);
            $this->services_arr[] = $temp;

            $arr[] = json_encode($temp);
        }

        $this->setServices($arr);

        return $this;
    }
}
