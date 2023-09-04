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

#[ORM\Entity(repositoryClass: \App\Repository\ForecastReminderRepository::class)]
class ForecastReminder implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    /**
     * @AppAssert\CronExpression(message="The value ""{{ value }}"" is not a valid cron expression.")
     */
    #[ORM\Column(type: 'string', length: 255)]
    private string $cronExpression = '0 18 * * *';

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'forecastReminders')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $updatedBy = null;

    /**
     * @Gedmo\Timestampable(on="update")
     */
    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $lastTimeSentAt = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $defaultActivityName = 'no activity defined';

    #[ORM\Column(type: 'string', length: 255)]
    private string $timeOffActivityName = 'holidays (until %s)';

    /**
     * @var Collection<int, ProjectOverride>
     */
    #[ORM\OneToMany(targetEntity: ProjectOverride::class, mappedBy: 'forecastReminder', orphanRemoval: true, cascade: ['persist'])]
    private Collection $projectOverrides;

    /**
     * @var Collection<int, ClientOverride>
     */
    #[ORM\OneToMany(targetEntity: ClientOverride::class, mappedBy: 'forecastReminder', orphanRemoval: true, cascade: ['persist'])]
    private Collection $clientOverrides;

    /**
     * @var array<array-key, int>
     */
    #[ORM\Column(type: 'array', nullable: true)]
    private ?array $timeOffProjects = [];

    /**
     * @var array<array-key, int>
     */
    #[ORM\Column(type: 'array', nullable: true)]
    private ?array $onlyUsers = [];

    /**
     * @var array<array-key, int>
     */
    #[ORM\Column(type: 'array', nullable: true)]
    private ?array $exceptUsers = [];

    #[ORM\OneToOne(targetEntity: ForecastAccount::class, inversedBy: 'forecastReminder', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ForecastAccount $forecastAccount;

    public function __construct()
    {
        $this->projectOverrides = new ArrayCollection();
        $this->clientOverrides = new ArrayCollection();
    }

    public function __toString(): string
    {
        return sprintf('Reminder for "%s"', $this->forecastAccount->getName());
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

    public function getLastTimeSentAt(): ?\DateTimeInterface
    {
        return $this->lastTimeSentAt;
    }

    public function setLastTimeSentAt(\DateTimeInterface $lastTimeSentAt): self
    {
        $this->lastTimeSentAt = $lastTimeSentAt;

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
     * @return Collection<int, ProjectOverride>|ProjectOverride[]
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
        }

        return $this;
    }

    /**
     * @return Collection<int, ClientOverride>|ClientOverride[]
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
        }

        return $this;
    }

    /**
     * @return array<array-key, int>
     */
    public function getTimeOffProjects(): ?array
    {
        return $this->timeOffProjects;
    }

    /**
     * @param array<array-key, int> $timeOffProjects
     */
    public function setTimeOffProjects(?array $timeOffProjects): self
    {
        $this->timeOffProjects = $timeOffProjects;

        return $this;
    }

    /**
     * @return array<array-key, int>
     */
    public function getOnlyUsers(): ?array
    {
        return $this->onlyUsers;
    }

    /**
     * @param array<array-key, int> $onlyUsers
     */
    public function setOnlyUsers(?array $onlyUsers): self
    {
        $this->onlyUsers = $onlyUsers;

        return $this;
    }

    /**
     * @return array<array-key, int>
     */
    public function getExceptUsers(): ?array
    {
        return $this->exceptUsers;
    }

    /**
     * @param array<array-key, int> $exceptUsers
     */
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
