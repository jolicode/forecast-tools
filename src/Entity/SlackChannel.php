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

/**
 * @ORM\Entity(repositoryClass="App\Repository\SlackChannelRepository")
 */
class SlackChannel
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
    private $teamName;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $webhookChannel;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $webhookUrl;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $accessToken;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(nullable=false)
     */
    private $updatedBy;

    /**
     * @ORM\Column(type="datetime")
     * @Gedmo\Timestampable(on="update")
     */
    private $updatedAt;

    /**
     * @ORM\Column(type="string", length=15)
     */
    private $teamId;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $webhookConfigurationUrl;

    /**
     * @ORM\Column(type="string", length=15)
     */
    private $webhookChannelId;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\ForecastAccount", inversedBy="slackChannels")
     * @ORM\JoinColumn(nullable=false)
     */
    private $forecastAccount;

    public function __toString()
    {
        return sprintf('%s #%s', $this->teamName, $this->webhookChannel);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTeamName(): ?string
    {
        return $this->teamName;
    }

    public function setTeamName(string $teamName): self
    {
        $this->teamName = $teamName;

        return $this;
    }

    public function getWebhookChannel(): ?string
    {
        return $this->webhookChannel;
    }

    public function setWebhookChannel(string $webhookChannel): self
    {
        $this->webhookChannel = $webhookChannel;

        return $this;
    }

    public function getWebhookUrl(): ?string
    {
        return $this->webhookUrl;
    }

    public function setWebhookUrl(string $webhookUrl): self
    {
        $this->webhookUrl = $webhookUrl;

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

    public function getUpdatedBy(): ?User
    {
        return $this->updatedBy;
    }

    public function setUpdatedBy(?User $updatedBy): self
    {
        $this->updatedBy = $updatedBy;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getTeamId(): ?string
    {
        return $this->teamId;
    }

    public function setTeamId(string $teamId): self
    {
        $this->teamId = $teamId;

        return $this;
    }

    public function getWebhookConfigurationUrl(): ?string
    {
        return $this->webhookConfigurationUrl;
    }

    public function setWebhookConfigurationUrl(string $webhookConfigurationUrl): self
    {
        $this->webhookConfigurationUrl = $webhookConfigurationUrl;

        return $this;
    }

    public function getWebhookChannelId(): ?string
    {
        return $this->webhookChannelId;
    }

    public function setWebhookChannelId(string $webhookChannelId): self
    {
        $this->webhookChannelId = $webhookChannelId;

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
}
