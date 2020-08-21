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
 * @ORM\Entity(repositoryClass="App\Repository\ForecastAccountSlackTeamRepository")
 */
class ForecastAccountSlackTeam
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\SlackTeam", inversedBy="forecastAccountSlackTeams")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $slackTeam;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    private $updatedBy;

    /**
     * @ORM\Column(type="datetime")
     * @Gedmo\Timestampable(on="update")
     */
    private $updatedAt;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $channel;

    /**
     * @ORM\Column(type="string", length=15, nullable=true)
     */
    private $channelId;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\ForecastAccount", inversedBy="forecastAccountSlackTeams")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $forecastAccount;

    public function __toString(): string
    {
        return sprintf('%s #%s', $this->slackTeam->getTeamName(), $this->channel);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSlackTeam(): ?SlackTeam
    {
        return $this->slackTeam;
    }

    public function setSlackTeam(?SlackTeam $slackTeam): self
    {
        $this->slackTeam = $slackTeam;

        return $this;
    }

    public function getChannel(): ?string
    {
        return $this->channel;
    }

    public function setChannel(?string $channel): self
    {
        $this->channel = $channel;

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

    public function getChannelId(): ?string
    {
        return $this->channelId;
    }

    public function setChannelId(?string $channelId): self
    {
        $this->channelId = $channelId;

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
