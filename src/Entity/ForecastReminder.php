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
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ForecastReminderRepository")
 */
class ForecastReminder
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @AppAssert\CronExpression(message="The value ""{{ value }}"" is not a valid cron expression.")
     */
    private $cronExpression = '0 18 * * *';

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="forecastReminders")
     * @ORM\JoinColumn(nullable=false)
     */
    private $updatedBy;

    /**
     * @ORM\Column(type="datetime")
     * @Gedmo\Timestampable(on="update")
     */
    private $updatedAt;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $defaultActivityName = 'no activity defined';

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $timeOffActivityName = 'holidays (until %s)';

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\ProjectOverride", mappedBy="forecastReminder", orphanRemoval=true, cascade={"persist"})
     */
    private $projectOverrides;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\ClientOverride", mappedBy="forecastReminder", orphanRemoval=true, cascade={"persist"})
     */
    private $clientOverrides;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private $timeOffProjects = [];

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private $onlyUsers = [];

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private $exceptUsers = [];

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\ForecastAccount", inversedBy="forecastReminder", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $forecastAccount;

    public function __construct()
    {
        $this->projectOverrides = new ArrayCollection();
        $this->clientOverrides = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->name;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCronExpression(): ?string
    {
        return $this->cronExpression;
    }

    public function setCronExpression(string $cronExpression): self
    {
        $this->cronExpression = $cronExpression;

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

    public function getDefaultActivityName(): ?string
    {
        return $this->defaultActivityName;
    }

    public function setDefaultActivityName(string $defaultActivityName): self
    {
        $this->defaultActivityName = $defaultActivityName;

        return $this;
    }

    public function getTimeOffActivityName(): ?string
    {
        return $this->timeOffActivityName;
    }

    public function setTimeOffActivityName(string $timeOffActivityName): self
    {
        $this->timeOffActivityName = $timeOffActivityName;

        return $this;
    }

    /**
     * @return Collection|ProjectOverride[]
     */
    public function getProjectOverrides(): Collection
    {
        return $this->projectOverrides;
    }

    public function addProjectOverride(ProjectOverride $projectOverride): self
    {
        if (!$this->projectOverrides->contains($projectOverride)) {
            $this->projectOverrides[] = $projectOverride;
            $projectOverride->setForecastReminder($this);
        }

        return $this;
    }

    public function removeProjectOverride(ProjectOverride $projectOverride): self
    {
        if ($this->projectOverrides->contains($projectOverride)) {
            $this->projectOverrides->removeElement($projectOverride);
            // set the owning side to null (unless already changed)
            if ($projectOverride->getForecastReminder() === $this) {
                $projectOverride->setForecastReminder(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|ClientOverride[]
     */
    public function getClientOverrides(): Collection
    {
        return $this->clientOverrides;
    }

    public function addClientOverride(ClientOverride $clientOverride): self
    {
        if (!$this->clientOverrides->contains($clientOverride)) {
            $this->clientOverrides[] = $clientOverride;
            $clientOverride->setForecastReminder($this);
        }

        return $this;
    }

    public function removeClientOverride(ClientOverride $clientOverride): self
    {
        if ($this->clientOverrides->contains($clientOverride)) {
            $this->clientOverrides->removeElement($clientOverride);
            // set the owning side to null (unless already changed)
            if ($clientOverride->getForecastReminder() === $this) {
                $clientOverride->setForecastReminder(null);
            }
        }

        return $this;
    }

    public function getTimeOffProjects(): ?array
    {
        return $this->timeOffProjects;
    }

    public function setTimeOffProjects(?array $timeOffProjects): self
    {
        $this->timeOffProjects = $timeOffProjects;

        return $this;
    }

    public function getOnlyUsers(): ?array
    {
        return $this->onlyUsers;
    }

    public function setOnlyUsers(?array $onlyUsers): self
    {
        $this->onlyUsers = $onlyUsers;

        return $this;
    }

    public function getExceptUsers(): ?array
    {
        return $this->exceptUsers;
    }

    public function setExceptUsers(?array $exceptUsers): self
    {
        $this->exceptUsers = $exceptUsers;

        return $this;
    }

    public function getForecastAccount(): ?ForecastAccount
    {
        return $this->forecastAccount;
    }

    public function setForecastAccount(ForecastAccount $forecastAccount): self
    {
        $this->forecastAccount = $forecastAccount;

        return $this;
    }

    public function getIsMuted(): ?bool
    {
        $forecastAccountSlackTeams = $this->getForecastAccount()->getForecastAccountSlackTeams();

        foreach ($forecastAccountSlackTeams as $forecastAccountSlackTeam) {
            if (null !== $forecastAccountSlackTeam->getChannelId()) {
                return false;
            }
        }

        return true;
    }
}
