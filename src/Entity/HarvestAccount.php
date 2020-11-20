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

/**
 * @ORM\Entity(repositoryClass="App\Repository\HarvestAccountRepository")
 */
class HarvestAccount
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="integer")
     */
    private $harvestId;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $accessToken;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $refreshToken;

    /**
     * @ORM\Column(type="integer")
     */
    private $expires;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\ForecastAccount", inversedBy="harvestAccount", cascade={"persist", "remove"})
     */
    private $forecastAccount;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $baseUri;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private $doNotCheckTimesheetsFor = [];

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $hideSkippedUsers;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\InvoiceDueDelayRequirement", mappedBy="harvestAccount", orphanRemoval=true, cascade={"persist"})
     * @Assert\Valid
     * @AppAssert\UniqueClient(message="There is already a due delay requirement for the client ""{{ value }}"".")
     */
    private $invoiceDueDelayRequirements;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\InvoiceNotesRequirement", mappedBy="harvestAccount", orphanRemoval=true, cascade={"persist"})
     * @Assert\Valid
     */
    private $invoiceNotesRequirements;

    /**
     * @ORM\ManyToOne(targetEntity=ForecastAccountSlackTeam::class, cascade={"persist"})
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    private $timesheetReminderSlackTeam;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private $doNotSendTimesheetReminderFor = [];

    public function __construct()
    {
        $this->invoiceDueDelayRequirements = new ArrayCollection();
        $this->invoiceNotesRequirements = new ArrayCollection();
    }

    public function __toString()
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

    public function getBaseUri(): ?string
    {
        return $this->baseUri;
    }

    public function setBaseUri(string $baseUri): self
    {
        $this->baseUri = $baseUri;

        return $this;
    }

    public function getDoNotCheckTimesheetsFor(): ?array
    {
        return $this->doNotCheckTimesheetsFor;
    }

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
     * @return Collection|InvoiceDueDelayRequirement[]
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
     * @return Collection|InvoiceNotesRequirement[]
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

    public function getDoNotSendTimesheetReminderFor(): ?array
    {
        return $this->doNotSendTimesheetReminderFor;
    }

    public function setDoNotSendTimesheetReminderFor(array $doNotSendTimesheetReminderFor): self
    {
        $this->doNotSendTimesheetReminderFor = $doNotSendTimesheetReminderFor;

        return $this;
    }
}
