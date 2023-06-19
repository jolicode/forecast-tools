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

#[ORM\Entity(repositoryClass: \App\Repository\PublicForecastRepository::class)]
class PublicForecast
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ForecastAccount::class, inversedBy: 'publicForecasts')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ForecastAccount $forecastAccount;

    #[ORM\Column(type: 'string', length: 255)]
    private string $token;

    /**
     * @var array<array-key, int>
     */
    #[ORM\Column(type: 'array', nullable: true)]
    private ?array $clients = [];

    /**
     * @var array<array-key, int>
     */
    #[ORM\Column(type: 'array', nullable: true)]
    private ?array $projects = [];

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'publicForecasts')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $createdBy = null;

    /**
     * @Gedmo\Timestampable(on="create")
     */
    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    /**
     * @var array<array-key, int>
     */
    #[ORM\Column(type: 'array', nullable: true)]
    private ?array $people = [];

    /**
     * @var array<array-key, int>
     */
    #[ORM\Column(type: 'array', nullable: true)]
    private ?array $placeholders = [];

    public function getId(): ?int
    {
        return $this->id;
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

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(string $token): self
    {
        $this->token = $token;

        return $this;
    }

    /**
     * @return array<array-key, int>
     */
    public function getClients(): ?array
    {
        return $this->clients;
    }

    /**
     * @param array<array-key, int> $clients
     */
    public function setClients(?array $clients): self
    {
        $this->clients = $clients;

        return $this;
    }

    /**
     * @return array<array-key, int>
     */
    public function getProjects(): ?array
    {
        return $this->projects;
    }

    /**
     * @param array<array-key, int> $projects
     */
    public function setProjects(?array $projects): self
    {
        $this->projects = $projects;

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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return array<array-key, int>
     */
    public function getPeople(): ?array
    {
        return $this->people;
    }

    /**
     * @param array<array-key, int> $people
     */
    public function setPeople(?array $people): self
    {
        $this->people = $people;

        return $this;
    }

    /**
     * @return array<array-key, int>
     */
    public function getPlaceholders(): ?array
    {
        return $this->placeholders;
    }

    /**
     * @param array<array-key, int> $placeholders
     */
    public function setPlaceholders(?array $placeholders): self
    {
        $this->placeholders = $placeholders;

        return $this;
    }
}
