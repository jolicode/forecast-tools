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
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 * @UniqueEntity("email")
 * @UniqueEntity("forecastId")
 */
class User
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer", unique=true)
     */
    private $forecastId;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     * @Assert\Email
     */
    private $email;

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
     * @ORM\Column(type="boolean")
     */
    private $isEnabled = false;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\ForecastAccount", mappedBy="users")
     */
    private $forecastAccounts;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\ForecastAlert", mappedBy="createdBy")
     */
    private $alerts;

    /**
     * @ORM\Column(type="datetime")
     * @Gedmo\Timestampable(on="create")
     */
    private $createdAt;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\PublicForecast", mappedBy="createdBy")
     */
    private $publicForecasts;

    public function __construct()
    {
        $this->forecastAccounts = new ArrayCollection();
        $this->alerts = new ArrayCollection();
        $this->publicForecasts = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->name;
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

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

    public function getIsEnabled(): ?bool
    {
        return $this->isEnabled;
    }

    public function setIsEnabled(bool $isEnabled): self
    {
        $this->isEnabled = $isEnabled;

        return $this;
    }

    /**
     * @return Collection|ForecastAccount[]
     */
    public function getForecastAccounts(): Collection
    {
        return $this->forecastAccounts;
    }

    public function addForecastAccount(ForecastAccount $forecastAccount): self
    {
        if (!$this->forecastAccounts->contains($forecastAccount)) {
            $this->forecastAccounts[] = $forecastAccount;
            $forecastAccount->addUser($this);
        }

        return $this;
    }

    public function removeForecastAccount(ForecastAccount $forecastAccount): self
    {
        if ($this->forecastAccounts->contains($forecastAccount)) {
            $this->forecastAccounts->removeElement($forecastAccount);
            $forecastAccount->removeUser($this);
        }

        return $this;
    }

    public function hasForecastAccount(ForecastAccount $forecastAccount): bool
    {
        foreach ($this->getForecastAccounts() as $account) {
            if ($account->getForecastId() === $forecastAccount->getForecastId()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return Collection|ForecastAlert[]
     */
    public function getAlerts(): Collection
    {
        return $this->alerts;
    }

    public function addAlert(ForecastAlert $alert): self
    {
        if (!$this->alerts->contains($alert)) {
            $this->alerts[] = $alert;
            $alert->setCreatedBy($this);
        }

        return $this;
    }

    public function removeAlert(ForecastAlert $alert): self
    {
        if ($this->alerts->contains($alert)) {
            $this->alerts->removeElement($alert);
            // set the owning side to null (unless already changed)
            if ($alert->getCreatedBy() === $this) {
                $alert->setCreatedBy(null);
            }
        }

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
            $publicForecast->setCreatedBy($this);
        }

        return $this;
    }

    public function removePublicForecast(PublicForecast $publicForecast): self
    {
        if ($this->publicForecasts->contains($publicForecast)) {
            $this->publicForecasts->removeElement($publicForecast);
            // set the owning side to null (unless already changed)
            if ($publicForecast->getCreatedBy() === $this) {
                $publicForecast->setCreatedBy(null);
            }
        }

        return $this;
    }
}
