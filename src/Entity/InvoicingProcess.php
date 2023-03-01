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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: \App\Repository\InvoicingProcessRepository::class)]
class InvoicingProcess
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    /**
     * @Gedmo\Timestampable(on="create")
     */
    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $createdBy = null;

    #[ORM\Column(type: 'date')]
    #[Assert\LessThanOrEqual(propertyPath: 'billingPeriodEnd')]
    private \DateTimeInterface $billingPeriodStart;

    #[ORM\Column(type: 'date')]
    #[Assert\LessThanOrEqual('today')]
    private \DateTimeInterface $billingPeriodEnd;

    #[ORM\ManyToOne(targetEntity: ForecastAccount::class, inversedBy: 'invoicingProcesses')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ForecastAccount $forecastAccount;

    #[ORM\ManyToOne(targetEntity: HarvestAccount::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private HarvestAccount $harvestAccount;

    #[ORM\Column(type: 'string', length: 255)]
    private string $currentPlace;

    /**
     * @var Collection<InvoiceExplanation>
     */
    #[ORM\OneToMany(targetEntity: InvoiceExplanation::class, mappedBy: 'invoicingProcess', orphanRemoval: true)]
    private Collection $invoiceExplanations;

    public function __construct()
    {
        $this->invoiceExplanations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getBillingPeriodStart(): ?\DateTimeInterface
    {
        return $this->billingPeriodStart;
    }

    public function setBillingPeriodStart(\DateTimeInterface $billingPeriodStart): self
    {
        $this->billingPeriodStart = $billingPeriodStart;

        return $this;
    }

    public function getBillingPeriodEnd(): ?\DateTimeInterface
    {
        return $this->billingPeriodEnd;
    }

    public function setBillingPeriodEnd(\DateTimeInterface $billingPeriodEnd): self
    {
        $this->billingPeriodEnd = $billingPeriodEnd;

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

    public function getHarvestAccount(): ?HarvestAccount
    {
        return $this->harvestAccount;
    }

    public function setHarvestAccount(?HarvestAccount $harvestAccount): self
    {
        $this->harvestAccount = $harvestAccount;

        return $this;
    }

    public function getCurrentPlace(): ?string
    {
        return $this->currentPlace;
    }

    public function setCurrentPlace(string $currentPlace): self
    {
        $this->currentPlace = $currentPlace;

        return $this;
    }

    /**
     * @return Collection|InvoiceExplanation[]
     */
    public function getInvoiceExplanations(): Collection
    {
        return $this->invoiceExplanations;
    }

    public function addInvoiceExplanation(InvoiceExplanation $invoiceExplanation): self
    {
        if (!$this->invoiceExplanations->contains($invoiceExplanation)) {
            $this->invoiceExplanations[] = $invoiceExplanation;
            $invoiceExplanation->setInvoicingProcess($this);
        }

        return $this;
    }

    public function removeInvoiceExplanation(InvoiceExplanation $invoiceExplanation): self
    {
        if ($this->invoiceExplanations->contains($invoiceExplanation)) {
            $this->invoiceExplanations->removeElement($invoiceExplanation);
            // set the owning side to null (unless already changed)
            if ($invoiceExplanation->getInvoicingProcess() === $this) {
                $invoiceExplanation->setInvoicingProcess(null);
            }
        }

        return $this;
    }
}
