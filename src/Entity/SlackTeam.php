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

#[ORM\Entity(repositoryClass: \App\Repository\SlackTeamRepository::class)]
class SlackTeam implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 15)]
    private string $teamId;

    #[ORM\Column(type: 'string', length: 255)]
    private string $teamName;

    #[ORM\Column(type: 'string', length: 255)]
    private string $accessToken;

    /**
     * @var Collection<int, ForecastAccountSlackTeam>
     */
    #[ORM\OneToMany(targetEntity: ForecastAccountSlackTeam::class, mappedBy: 'slackTeam', orphanRemoval: true)]
    private Collection $forecastAccountSlackTeams;

    /**
     * @var Collection<int, StandupMeetingReminder>
     */
    #[ORM\OneToMany(targetEntity: StandupMeetingReminder::class, mappedBy: 'slackTeam', orphanRemoval: true)]
    private Collection $standupMeetingReminders;

    public function __construct()
    {
        $this->forecastAccountSlackTeams = new ArrayCollection();
        $this->standupMeetingReminders = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->teamName;
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

    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }

    public function setAccessToken(string $accessToken): self
    {
        $this->accessToken = $accessToken;

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

    /**
     * @return Collection<int, ForecastAccountSlackTeam>|ForecastAccountSlackTeam[]
     */
    public function getForecastAccountSlackTeams(): Collection
    {
        return $this->forecastAccountSlackTeams;
    }

    public function addForecastAccountSlackTeam(ForecastAccountSlackTeam $forecastAccountSlackTeam): self
    {
        if (!$this->forecastAccountSlackTeams->contains($forecastAccountSlackTeam)) {
            $this->forecastAccountSlackTeams[] = $forecastAccountSlackTeam;
            $forecastAccountSlackTeam->setSlackTeam($this);
        }

        return $this;
    }

    public function removeForecastAccountSlackTeam(ForecastAccountSlackTeam $forecastAccountSlackTeam): self
    {
        if ($this->forecastAccountSlackTeams->contains($forecastAccountSlackTeam)) {
            $this->forecastAccountSlackTeams->removeElement($forecastAccountSlackTeam);
            // set the owning side to null (unless already changed)
            if ($forecastAccountSlackTeam->getSlackTeam() === $this) {
                $forecastAccountSlackTeam->setSlackTeam(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, StandupMeetingReminder>|StandupMeetingReminder[]
     */
    public function getStandupMeetingReminders(): Collection
    {
        return $this->standupMeetingReminders;
    }

    public function addStandupMeetingReminder(StandupMeetingReminder $standupMeetingReminder): self
    {
        if (!$this->standupMeetingReminders->contains($standupMeetingReminder)) {
            $this->standupMeetingReminders[] = $standupMeetingReminder;
            $standupMeetingReminder->setSlackTeam($this);
        }

        return $this;
    }

    public function removeStandupMeetingReminder(StandupMeetingReminder $standupMeetingReminder): self
    {
        if ($this->standupMeetingReminders->contains($standupMeetingReminder)) {
            $this->standupMeetingReminders->removeElement($standupMeetingReminder);
            // set the owning side to null (unless already changed)
            if ($standupMeetingReminder->getSlackTeam() === $this) {
                $standupMeetingReminder->setSlackTeam(null);
            }
        }

        return $this;
    }
}
