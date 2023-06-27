<?php

/*
 * This file is part of JoliCode's Forecast Tools project.
 *
 * (c) JoliCode <coucou@jolicode.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

use App\Validator\Constraints as AppAssert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: \App\Repository\HarvestAccountRepository::class)]
class HarvestAccount implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'integer')]
    private int $harvestId;

    #[ORM\Column(type: 'string', length: 255)]
    private string $accessToken;

    #[ORM\Column(type: 'string', length: 255)]
    private string $refreshToken;

    #[ORM\Column(type: 'integer')]
    private int $expires;

    #[ORM\OneToOne(targetEntity: ForecastAccount::class, inversedBy: 'harvestAccount', cascade: ['persist', 'remove'])]
    private ?ForecastAccount $forecastAccount = null;

    /**
     * @var Collection<int, UserHarvestAccount>
     */
    #[ORM\OneToMany(targetEntity: UserHarvestAccount::class, mappedBy: 'harvestAccount', orphanRemoval: true)]
    private Collection $userHarvestAccounts;

    #[ORM\Column(type: 'string', length: 255)]
    private string $baseUri;

    /**
     * @var array<array-key, int>
     */
    #[ORM\Column(type: 'array', nullable: true)]
    private ?array $doNotCheckTimesheetsFor = [];

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $hideSkippedUsers = null;

    /**
     * @var Collection<int, InvoiceDueDelayRequirement>
     *
     * @AppAssert\UniqueClient(message="There is already a due delay requirement for the client ""{{ value }}"".")
     */
    #[ORM\OneToMany(targetEntity: InvoiceDueDelayRequirement::class, mappedBy: 'harvestAccount', orphanRemoval: true, cascade: ['persist'])]
    #[Assert\Valid]
    private Collection $invoiceDueDelayRequirements;

    /**
     * @var Collection<int, InvoiceNotesRequirement>
     */
    #[ORM\OneToMany(targetEntity: InvoiceNotesRequirement::class, mappedBy: 'harvestAccount', orphanRemoval: true, cascade: ['persist'])]
    #[Assert\Valid]
    private Collection $invoiceNotesRequirements;

    #[ORM\ManyToOne(targetEntity: ForecastAccountSlackTeam::class, cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?ForecastAccountSlackTeam $timesheetReminderSlackTeam = null;

    /**
     * @var array<array-key, int>
     */
    #[ORM\Column(type: 'array', nullable: true)]
    private ?array $doNotSendTimesheetReminderFor = [];

    public function __construct()
    {
        $this->invoiceDueDelayRequirements = new ArrayCollection();
        $this->invoiceNotesRequirements = new ArrayCollection();
        $this->userHarvestAccounts = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->name;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getHarvestId(): ?int
    {
        return $this->harvestId;
    }

    public function setHarvestId(int $harvestId): self
    {
        $this->harvestId = $harvestId;

        return $this;
    }

    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }

    public function setAccessToken(string $accessToken): self
    {
        $this->accessToken = $accessToken;

        return $this;
    }

    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }

    public function setRefreshToken(string $refreshToken): self
    {
        $this->refreshToken = $refreshToken;

        return $this;
    }

    public function getExpires(): ?int
    {
        return $this->expires;
    }

    public function setExpires(int $expires): self
    {
        $this->expires = $expires;

        return $this;
    }

    public function getForecastAccount(): ?ForecastAccount
    {
        return $this->forecastAccount;
    }

    public function setForecastAccount(?ForecastAccount $forecastAccount): self
    {
        $this->forecastAccount = $forecastAccount;

        return $this;
    }

    /**
     * @return Collection<int, UserHarvestAccount>|UserHarvestAccount[]
     */
    public function getUserHarvestAccounts(): Collection
    {
        return $this->userHarvestAccounts;
    }

    public function addUserHarvestAccount(UserHarvestAccount $userHarvestAccount): self
    {
        if (!$this->userHarvestAccounts->contains($userHarvestAccount)) {
            $this->userHarvestAccounts->add($userHarvestAccount);
            $userHarvestAccount->setHarvestAccount($this);
        }

        return $this;
    }

    public function removeUserHarvestAccount(UserHarvestAccount $userHarvestAccount): self
    {
        if ($this->userHarvestAccounts->contains($userHarvestAccount)) {
            $this->userHarvestAccounts->removeElement($userHarvestAccount);
            // set the owning side to null (unless already changed)
            if ($userHarvestAccount->getHarvestAccount() === $this) {
                $userHarvestAccount->setHarvestAccount(null);
            }
        }

        return $this;
    }

    public function getBaseUri(): ?string
    {
        return $this->baseUri;
    }

    public function setBaseUri(string $baseUri): self
    {
        $this->baseUri = $baseUri;

        return $this;
    }

    /**
     * @return array<array-key, int>
     */
    public function getDoNotCheckTimesheetsFor(): ?array
    {
        return $this->doNotCheckTimesheetsFor;
    }

    /**
     * @param array<array-key, int> $doNotCheckTimesheetsFor
     */
    public function setDoNotCheckTimesheetsFor(?array $doNotCheckTimesheetsFor): self
    {
        $this->doNotCheckTimesheetsFor = $doNotCheckTimesheetsFor;

        return $this;
    }

    public function getHideSkippedUsers(): ?bool
    {
        return $this->hideSkippedUsers;
    }

    public function setHideSkippedUsers(?bool $hideSkippedUsers): self
    {
        $this->hideSkippedUsers = $hideSkippedUsers;

        return $this;
    }

    /**
     * @return Collection<int, InvoiceDueDelayRequirement>|InvoiceDueDelayRequirement[]
     */
    public function getInvoiceDueDelayRequirements(): Collection
    {
        return $this->invoiceDueDelayRequirements;
    }

    public function addInvoiceDueDelayRequirement(InvoiceDueDelayRequirement $invoiceDueDelayRequirement): self
    {
        if (!$this->invoiceDueDelayRequirements->contains($invoiceDueDelayRequirement)) {
            $this->invoiceDueDelayRequirements[] = $invoiceDueDelayRequirement;
            $invoiceDueDelayRequirement->setHarvestAccount($this);
        }

        return $this;
    }

    public function removeInvoiceDueDelayRequirement(InvoiceDueDelayRequirement $invoiceDueDelayRequirement): self
    {
        if ($this->invoiceDueDelayRequirements->contains($invoiceDueDelayRequirement)) {
            $this->invoiceDueDelayRequirements->removeElement($invoiceDueDelayRequirement);
            // set the owning side to null (unless already changed)
            if ($invoiceDueDelayRequirement->getHarvestAccount() === $this) {
                $invoiceDueDelayRequirement->setHarvestAccount(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, InvoiceNotesRequirement>|InvoiceNotesRequirement[]
     */
    public function getInvoiceNotesRequirements(): Collection
    {
        return $this->invoiceNotesRequirements;
    }

    public function addInvoiceNotesRequirement(InvoiceNotesRequirement $invoiceNotesRequirement): self
    {
        if (!$this->invoiceNotesRequirements->contains($invoiceNotesRequirement)) {
            $this->invoiceNotesRequirements[] = $invoiceNotesRequirement;
            $invoiceNotesRequirement->setHarvestAccount($this);
        }

        return $this;
    }

    public function removeInvoiceNotesRequirement(InvoiceNotesRequirement $invoiceNotesRequirement): self
    {
        if ($this->invoiceNotesRequirements->contains($invoiceNotesRequirement)) {
            $this->invoiceNotesRequirements->removeElement($invoiceNotesRequirement);
            // set the owning side to null (unless already changed)
            if ($invoiceNotesRequirement->getHarvestAccount() === $this) {
                $invoiceNotesRequirement->setHarvestAccount(null);
            }
        }

        return $this;
    }

    public function getTimesheetReminderSlackTeam(): ?ForecastAccountSlackTeam
    {
        return $this->timesheetReminderSlackTeam;
    }

    public function setTimesheetReminderSlackTeam(?ForecastAccountSlackTeam $timesheetReminderSlackTeam): self
    {
        $this->timesheetReminderSlackTeam = $timesheetReminderSlackTeam;

        return $this;
    }

    /**
     * @return array<array-key, int>
     */
    public function getDoNotSendTimesheetReminderFor(): ?array
    {
        return $this->doNotSendTimesheetReminderFor;
    }

    /**
     * @param array<array-key, int> $doNotSendTimesheetReminderFor
     */
    public function setDoNotSendTimesheetReminderFor(array $doNotSendTimesheetReminderFor): self
    {
        $this->doNotSendTimesheetReminderFor = $doNotSendTimesheetReminderFor;

        return $this;
    }
}
