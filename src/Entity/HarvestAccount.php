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
}
