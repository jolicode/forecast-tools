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
 * @ORM\Entity(repositoryClass="App\Repository\StandupMeetingReminderRepository")
 */
class StandupMeetingReminder
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     * @Gedmo\Timestampable(on="update")
     */
    private $updatedAt;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isEnabled;

    /**
     * @ORM\Column(type="string", length=15)
     */
    private $channelId;

    /**
     * @ORM\Column(type="array")
     */
    private $forecastProjects = [];

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $updatedBy;

    /**
     * @ORM\Column(type="string", length=5)
     */
    private $time;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\SlackTeam", inversedBy="standupMeetingReminders")
     * @ORM\JoinColumn(nullable=false)
     */
    private $slackTeam;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getIsEnabled(): ?bool
    {
        return $this->isEnabled;
    }

    public function setIsEnabled(bool $isEnabled): self
    {
        $this->isEnabled = $isEnabled;

        return $this;
    }

    public function getChannelId(): ?string
    {
        return $this->channelId;
    }

    public function setChannelId(string $channelId): self
    {
        $this->channelId = $channelId;

        return $this;
    }

    public function getForecastProjects(): ?array
    {
        return $this->forecastProjects;
    }

    public function setForecastProjects(array $forecastProjects): self
    {
        $this->forecastProjects = $forecastProjects;

        return $this;
    }

    public function getUpdatedBy(): ?string
    {
        return $this->updatedBy;
    }

    public function setUpdatedBy(string $updatedBy): self
    {
        $this->updatedBy = $updatedBy;

        return $this;
    }

    public function getTime(): ?string
    {
        return $this->time;
    }

    public function setTime(string $time): self
    {
        $this->time = $time;

        return $this;
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
}
