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

/**
 * @ORM\Entity(repositoryClass="App\Repository\ForecastAlertRepository")
 */
class ForecastAlert
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
     * @ORM\ManyToOne(targetEntity="App\Entity\ForecastAccount", inversedBy="alerts")
     * @ORM\JoinColumn(nullable=false)
     */
    private $forecastAccount;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $cronExpression = '0 18 * * *';

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="alerts")
     * @ORM\JoinColumn(nullable=false)
     */
    private $createdBy;

    /**
     * @ORM\Column(type="datetime")
     * @Gedmo\Timestampable(on="create")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $defaultActivityName = 'no activity defined';

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $timeOffActivityName = 'holidays (until %s)';

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\ProjectOverride", mappedBy="alert", orphanRemoval=true, cascade={"persist"})
     */
    private $projectOverrides;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\ClientOverride", mappedBy="alert", orphanRemoval=true, cascade={"persist"})
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
    private $slackWebHooks = [ 'TXXXXXXXX/BXXXXXXXXX/PXXXXXXXXXXXXXXXXXXXXXX' ];

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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

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

    public function getCronExpression(): ?string
    {
        return $this->cronExpression;
    }

    public function setCronExpression(string $cronExpression): self
    {
        $this->cronExpression = $cronExpression;

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

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

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
            $projectOverride->setAlert($this);
        }

        return $this;
    }

    public function removeProjectOverride(ProjectOverride $projectOverride): self
    {
        if ($this->projectOverrides->contains($projectOverride)) {
            $this->projectOverrides->removeElement($projectOverride);
            // set the owning side to null (unless already changed)
            if ($projectOverride->getAlert() === $this) {
                $projectOverride->setAlert(null);
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
            $clientOverride->setAlert($this);
        }

        return $this;
    }

    public function removeClientOverride(ClientOverride $clientOverride): self
    {
        if ($this->clientOverrides->contains($clientOverride)) {
            $this->clientOverrides->removeElement($clientOverride);
            // set the owning side to null (unless already changed)
            if ($clientOverride->getAlert() === $this) {
                $clientOverride->setAlert(null);
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

    public function getSlackWebHooks(): ?array
    {
        return $this->slackWebHooks;
    }

    public function setSlackWebHooks(?array $slackWebHooks): self
    {
        $this->slackWebHooks = $slackWebHooks;

        return $this;
    }
}
