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
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ForecastAccountRepository")
 * @UniqueEntity("slug")
 */
class ForecastAccount
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
    private $forecastId;

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
     * @ORM\Column(type="datetime")
     * @Gedmo\Timestampable(on="create")
     */
    private $createdAt;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\PublicForecast", mappedBy="forecastAccount", orphanRemoval=true)
     */
    private $publicForecasts;

    /**
     * @Gedmo\Slug(fields={"name"})
     * @ORM\Column(type="string", length=255, unique=true)
     */
    private $slug;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\UserForecastAccount", mappedBy="forecastAccount", orphanRemoval=true)
     */
    private $userForecastAccounts;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\ForecastReminder", mappedBy="forecastAccount", cascade={"persist", "remove"})
     */
    private $forecastReminder;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\SlackChannel", mappedBy="forecastAccount", orphanRemoval=true)
     */
    private $slackChannels;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\HarvestAccount", mappedBy="forecastAccount", cascade={"persist", "remove"})
     */
    private $harvestAccount;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\InvoicingProcess", mappedBy="forecastAccount", orphanRemoval=true)
     */
    private $invoicingProcesses;

    public function __construct()
    {
        $this->publicForecasts = new ArrayCollection();
        $this->userHarvestAccounts = new ArrayCollection();
        $this->slackChannels = new ArrayCollection();
        $this->invoicingProcesses = new ArrayCollection();
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

    public function getForecastId(): ?int
    {
        return $this->forecastId;
    }

    public function setForecastId(int $forecastId): self
    {
        $this->forecastId = $forecastId;

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

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return Collection|PublicForecast[]
     */
    public function getPublicForecasts(): Collection
    {
        return $this->publicForecasts;
    }

    public function addPublicForecast(PublicForecast $publicForecast): self
    {
        if (!$this->publicForecasts->contains($publicForecast)) {
            $this->publicForecasts[] = $publicForecast;
            $publicForecast->setForecastAccount($this);
        }

        return $this;
    }

    public function removePublicForecast(PublicForecast $publicForecast): self
    {
        if ($this->publicForecasts->contains($publicForecast)) {
            $this->publicForecasts->removeElement($publicForecast);
            // set the owning side to null (unless already changed)
            if ($publicForecast->getForecastAccount() === $this) {
                $publicForecast->setForecastAccount(null);
            }
        }

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * @return Collection|UserForecastAccount[]
     */
    public function getUserForecastAccounts(): Collection
    {
        return $this->userForecastAccounts;
    }

    public function addUserForecastAccount(UserForecastAccount $userForecastAccount): self
    {
        if (!$this->userForecastAccounts->contains($userForecastAccount)) {
            $this->userForecastAccounts[] = $userForecastAccount;
            $userForecastAccount->setForecastAccount($this);
        }

        return $this;
    }

    public function removeUserForecastAccount(UserForecastAccount $userForecastAccount): self
    {
        if ($this->userForecastAccounts->contains($userForecastAccount)) {
            $this->userForecastAccounts->removeElement($userForecastAccount);
            // set the owning side to null (unless already changed)
            if ($userForecastAccount->getForecastAccount() === $this) {
                $userForecastAccount->setForecastAccount(null);
            }
        }

        return $this;
    }

    public function getForecastReminder(): ?ForecastReminder
    {
        return $this->forecastReminder;
    }

    public function setForecastReminder(ForecastReminder $forecastReminder): self
    {
        $this->forecastReminder = $forecastReminder;

        // set the owning side of the relation if necessary
        if ($forecastReminder->getForecastAccount() !== $this) {
            $forecastReminder->setForecastAccount($this);
        }

        return $this;
    }

    /**
     * @return Collection|SlackChannel[]
     */
    public function getSlackChannels(): Collection
    {
        return $this->slackChannels;
    }

    public function addSlackChannel(SlackChannel $slackChannel): self
    {
        if (!$this->slackChannels->contains($slackChannel)) {
            $this->slackChannels[] = $slackChannel;
            $slackChannel->setForecastAccount($this);
        }

        return $this;
    }

    public function removeSlackChannel(SlackChannel $slackChannel): self
    {
        if ($this->slackChannels->contains($slackChannel)) {
            $this->slackChannels->removeElement($slackChannel);
            // set the owning side to null (unless already changed)
            if ($slackChannel->getForecastAccount() === $this) {
                $slackChannel->setForecastAccount(null);
            }
        }

        return $this;
    }

    public function getHarvestAccount(): ?HarvestAccount
    {
        return $this->harvestAccount;
    }

    public function setHarvestAccount(?HarvestAccount $harvestAccount): self
    {
        $this->harvestAccount = $harvestAccount;

        // set (or unset) the owning side of the relation if necessary
        $newForecastAccount = null === $harvestAccount ? null : $this;
        if ($harvestAccount->getForecastAccount() !== $newForecastAccount) {
            $harvestAccount->setForecastAccount($newForecastAccount);
        }

        return $this;
    }

    /**
     * @return Collection|InvoicingProcess[]
     */
    public function getInvoicingProcesses(): Collection
    {
        return $this->invoicingProcesses;
    }

    public function addInvoicingProcess(InvoicingProcess $invoicingProcess): self
    {
        if (!$this->invoicingProcesses->contains($invoicingProcess)) {
            $this->invoicingProcesses[] = $invoicingProcess;
            $invoicingProcess->setForecastAccount($this);
        }

        return $this;
    }

    public function removeInvoicingProcess(InvoicingProcess $invoicingProcess): self
    {
        if ($this->invoicingProcesses->contains($invoicingProcess)) {
            $this->invoicingProcesses->removeElement($invoicingProcess);
            // set the owning side to null (unless already changed)
            if ($invoicingProcess->getForecastAccount() === $this) {
                $invoicingProcess->setForecastAccount(null);
            }
        }

        return $this;
    }
}
