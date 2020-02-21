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

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\InvoicingProcessRepository")
 */
class InvoicingProcess
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     * @Gedmo\Timestampable(on="create")
     */
    private $createdAt;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(nullable=false)
     */
    private $createdBy;

    /**
     * @ORM\Column(type="date")
     * @Assert\LessThanOrEqual(propertyPath="billingPeriodEnd")
     */
    private $billingPeriodStart;

    /**
     * @ORM\Column(type="date")
     * @Assert\LessThanOrEqual("today")
     */
    private $billingPeriodEnd;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\ForecastAccount", inversedBy="invoicingProcesses")
     * @ORM\JoinColumn(nullable=false)
     */
    private $forecastAccount;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\HarvestAccount")
     * @ORM\JoinColumn(nullable=false)
     */
    private $harvestAccount;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $currentPlace;

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
}
