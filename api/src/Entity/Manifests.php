<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Entity\Overpacks;
use App\Entity\Users;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;
use App\Entity\EntryPoints;
use ApiPlatform\Core\Annotation\ApiFilter;
use App\Filter\OrderByFixedPropertyFilter;
use App\Filter\DateInclusiveFilter;
use App\Filter\SearchFilterWithMapping;
use App\Exception\InvalidEntryException;
/**
 *
 * @ApiResource(
 *     normalizationContext={"groups"={"manifests:read"}},
 *     denormalizationContext={"groups"={"manifests:write"}},
 *     collectionOperations={
 *         "get"={
 *             "normalization_context"={"groups"={"manifests:readabbr"}}
 *         },
 *         "post"={
 *             "denormalization_context"={"groups"={"manifests:writeabbr"}}
 *         }
 *     },
 *     itemOperations={
 *         "get"
 *     },
 *     attributes={"route_prefix"="/v2"}
 * )
 * @ORM\Entity
 * @ApiFilter(OrderByFixedPropertyFilter::class, properties={"id": "ASC"}, arguments={"orderParameterName"="sort"})
 * @ApiFilter(DateInclusiveFilter::class, properties={"created"})
 * @ApiResource(attributes={"pagination_items_per_page"=50})
 * @ApiFilter(SearchFilterWithMapping::class, properties={"overpacks.ep.id": "partial"})
 */
class Manifests
{
    /**
     * @var string|null Name of Carrier
     *
     * @ORM\Column(name="inbound_carrier", type="string", length=32, nullable=true)
     * @Groups({"manifests:read", "manifests:readabbr", "manifests:write"})
     * @Assert\Length(max=32)
     * @SerializedName("carrier")
     */
    public $inbound_carrier;

    /**
     * @var \DateTimeInterface Creation Date
     *
     * @ORM\Column(name="created", type="datetime", options={"default": "CURRENT_TIMESTAMP"})
     * @Groups({"manifests:read", "manifests:readabbr"})
     */
    public $created;

    /**
     * @var string Entry point.
     *
     * @ORM\Column(name="ep_id", type="string", length=6, options={"fixed" = true}, nullable=true)
     * @Groups({"manifests:read", "manifests:readabbr"})
     * @SerializedName("entry_point")
     */
    public $ep;

    /**
     * @var int The entity Id
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(name="id", type="bigint", options={"unsigned"=true})
     * @Groups({"manifests:read", "manifests:readabbr", "manifests:write", "overpacks:write", "overpacks:item:get"})
     */
    private $id;

    /**
     * @var Users
     *
     * @ORM\ManyToOne(targetEntity=Users::class, inversedBy="manifests")
     * @Groups({"manifests:write", "manifests:writeabbr", "overpacks:read", "overpacks:write"})
     * @SerializedName("user_id")
     */
    protected $user;

    /**
     * @var int The Major Airlines Waybill Id
     *
     * @ORM\Column(name="mawb_id", type="integer", length=11, options={"unsigned"=true})
     * @Groups({"manifests:read", "manifests:readabbr"})

    public $mawb_id;

    /**
     * @var decimal The Weight as decimal
     *
     * @ORM\Column(name="weight", type="decimal", precision=5, scale=1, options={"default"=0.0}, nullable=true)

    private $weight;
    */

    /**
     * @var int Processed flag
     *
     * @ORM\Column(name="processed", type="boolean", options={"unsigned"=true, "default"=0})
     */
    private $processed = 0;

    /**
     * @var int Invoiced flag
     *
     * @ORM\Column(name="invoiced", type="boolean", options={"unsigned"=true, "default"=0})
     */
    private $invoiced = 0;

    /**
     * @var string Last event for the manifest
     *
     * @ORM\Column(name="last_event", type="string", length=128, nullable=true)
     */
    private $last_event;

    /**
     * @var int SLAC
     *
     * @ORM\Column(name="slac", type="smallint", options={"unsigned"=true})
    private $slac;
     */

    /**
     * @var string Last event for the manifest
     *
     * @ORM\Column(name="forms", type="string", length=400, nullable=true)
     */
    private $forms;

    /**
     * @var array List of overpack ids
     *
     * @Groups({"manifests:read"})
     * @SerializedName("overpacks")
     */
    public $overpacks_ids;

    /**
     * @var int Total number of overpacks
     *
     * @Groups({"manifests:readabbr"})
     */
    public $total_overpacks;

    /**
     * @ORM\OneToMany(targetEntity=Overpacks::class, mappedBy="manifest")
     * @Groups({"manifests:readabbr", "manifests:writeabbr"})
     * @SerializedName("overpacks_details")
     */
    private $overpacks;

    /**
     * @var int Total Number of Shipments
     *
     * @ORM\Column(name="ctns", type="smallint", options={"unsigned"=true, "default"=0})
     * @Groups({"manifests:readabbr"})
     * @SerializedName("total_shipments")
     */
    public $ctns = 0;

    /**
     * @var string|null Inbound Tracking Number
     *
     * @ORM\Column(name="inbound_tracking", type="string", length=40, nullable=true)
     * @Groups({"manifests:read", "manifests:readabbr", "manifests:write"})
     * @Assert\Length(max=40)
     * @SerializedName("tracking_number")
     */
    public $inbound_tracking;

    /**
     * @var string|null The Warehouse Number
     *
     * @ORM\Column(name="warehouse_no", type="string", length=12, options={"fixed" = true}, nullable=true)
     * @Groups({"manifests:read", "manifests:readabbr"})
     */
    public $warehouse_no;

    public function __construct()
    {
        $this->overpacks = new ArrayCollection();
        $this->created = new \DateTimeImmutable();
    }

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return Collection|Overpacks[]
     */
    public function getOverpacks(): Collection
    {
        return $this->overpacks;
    }

    public function addOverpack(Overpacks $overpack): self
    {
        // Verify that the user of Overpacks and Shipments match.
        // TODO: not working for POST where user_id has not yet been entered into Manifests table.
        // $same_user = $this->getUser()->getId() == $overpack->getUser()->getId();

        // Verify that manifest id has not been set already.
        $manifest_id_set = !empty($overpack->getManifestId());

        // Verify that overpack has at least one Shipment.
        $has_one_or_more_shipments = !empty($overpack->getTotalShipments());

        if (!$this->overpacks->contains($overpack))
        {
            $oid = $overpack->getId();

            if ($manifest_id_set) {
                throw new InvalidEntryException(sprintf('Already manifested Overpacks (overpack id: %s) cannot be added.', $overpack->getId()));
            }

            if (!$has_one_or_more_shipments) {
                throw new InvalidEntryException(sprintf('Overpacks with no Shipments (overpack id: %s) cannot be manifested.', $overpack->getId()));
            }

            $this->overpacks[] = $overpack;
            $overpack->setManifest($this);
        }

        return $this;
    }

    public function getOverpacksIds(): ?array
    {
        $ovps = $this->getOverpacks();
        foreach($ovps as $ovp) {
            $this->overpacks_ids[] = $ovp->getId();
        }

        return $this->overpacks_ids;
    }

    public function getTotalOverpacks(): ?int
    {
        $ovps = $this->getOverpacks();
        foreach($ovps as $ovp) {
            $this->total_overpacks += 1;
        }

        return $this->total_overpacks;
    }

    public function getEp(): ?string
    {
        $retval = null;
        $ovps = $this->getOverpacks();

        foreach($ovps as $ovp) {
            if ($ovp->ep->getId() !== null) {
                $retval = $ovp->ep->getId();
                break;
            }
        }

        return $retval;
    }

    public function getEntryPoint(): string
    {
        $temp = $this->getEp();

        return $temp == null ? "" : $temp;
    }

    public function removeOverpack(Overpacks $overpack): self
    {
        if ($this->overpacks->contains($overpack)) {
            $this->overpacks->removeElement($overpack);
            // set the owning side to null (unless already changed)
            if ($overpack->getManifest() === $this) {
                $overpack->setManifest(null);
            }
        }

        return $this;
    }

    public function getUser(): ?Users
    {
        return $this->user;
    }

    public function setUser(Users $user): self
    {
        $this->user = $user;

        return $this;
    }
}
