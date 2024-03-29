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

#[ORM\Entity(repositoryClass: \App\Repository\UserRepository::class)]
#[UniqueEntity('email')]
#[UniqueEntity('forecastId')]
class User implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'integer', unique: true)]
    private int $forecastId;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    #[Assert\Email]
    private string $email;

    #[ORM\Column(type: 'string', length: 255)]
    private string $accessToken;

    #[ORM\Column(type: 'string', length: 255)]
    private string $refreshToken;

    #[ORM\Column(type: 'integer')]
    private int $expires;

    #[ORM\Column(type: 'boolean')]
    private bool $isEnabled = false;

    /**
     * @var Collection<int, ForecastReminder>
     */
    #[ORM\OneToMany(targetEntity: ForecastReminder::class, mappedBy: 'updatedBy')]
    private Collection $forecastReminders;

    /**
     * @Gedmo\Timestampable(on="create")
     */
    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    /**
     * @var Collection<int, PublicForecast>
     */
    #[ORM\OneToMany(targetEntity: PublicForecast::class, mappedBy: 'createdBy')]
    private Collection $publicForecasts;

    /**
     * @var Collection<int, UserForecastAccount>
     */
    #[ORM\OneToMany(targetEntity: UserForecastAccount::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $userForecastAccounts;

    /**
     * @var Collection<int, UserHarvestAccount>
     */
    #[ORM\OneToMany(targetEntity: UserHarvestAccount::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $userHarvestAccounts;

    #[ORM\ManyToOne(targetEntity: ForecastAccount::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?ForecastAccount $defaultForecastAccount = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isSuperAdmin = false;

    public function __construct()
    {
        $this->forecastReminders = new ArrayCollection();
        $this->publicForecasts = new ArrayCollection();
        $this->userForecastAccounts = new ArrayCollection();
        $this->userHarvestAccounts = new ArrayCollection();
    }

    public function __toString(): string
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
     * @return Collection<int, ForecastReminder>|ForecastReminder[]
     */
    public function getForecastReminders(): Collection
    {
        return $this->forecastReminders;
    }

    public function addForecastReminder(ForecastReminder $forecastReminder): self
    {
        if (!$this->forecastReminders->contains($forecastReminder)) {
            $this->forecastReminders[] = $forecastReminder;
            $forecastReminder->setUpdatedBy($this);
        }

        return $this;
    }

    public function removeForecastReminder(ForecastReminder $forecastReminder): self
    {
        if ($this->forecastReminders->contains($forecastReminder)) {
            $this->forecastReminders->removeElement($forecastReminder);
            // set the owning side to null (unless already changed)
            if ($forecastReminder->getUpdatedBy() === $this) {
                $forecastReminder->setUpdatedBy(null);
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
     * @return Collection<int, PublicForecast>|PublicForecast[]
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

    /**
     * @return Collection<int, UserForecastAccount>|UserForecastAccount[]
     */
    public function getUserForecastAccounts(): Collection
    {
        return $this->userForecastAccounts;
    }

    public function addUserForecastAccount(UserForecastAccount $userForecastAccount): self
    {
        if (!$this->userForecastAccounts->contains($userForecastAccount)) {
            $this->userForecastAccounts[] = $userForecastAccount;
            $userForecastAccount->setUser($this);
        }

        return $this;
    }

    public function removeUserForecastAccount(UserForecastAccount $userForecastAccount): self
    {
        if ($this->userForecastAccounts->contains($userForecastAccount)) {
            $this->userForecastAccounts->removeElement($userForecastAccount);
            // set the owning side to null (unless already changed)
            if ($userForecastAccount->getUser() === $this) {
                $userForecastAccount->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, UserHarvestAccount>|UserHarvestAccount[]
     */
    public function getUserHarvestAccounts(): Collection
    {
        return $this->userHarvestAccounts;
    }

    public function addUserHarvestAccount(UserHarvestAccount $userHarvestAccount): self
    {
        if (!$this->userHarvestAccounts->contains($userHarvestAccount)) {
            $this->userHarvestAccounts[] = $userHarvestAccount;
            $userHarvestAccount->setUser($this);
        }

        return $this;
    }

    public function removeUserHarvestAccount(UserHarvestAccount $userHarvestAccount): self
    {
        if ($this->userHarvestAccounts->contains($userHarvestAccount)) {
            $this->userHarvestAccounts->removeElement($userHarvestAccount);
            // set the owning side to null (unless already changed)
            if ($userHarvestAccount->getUser() === $this) {
                $userHarvestAccount->setUser(null);
            }
        }

        return $this;
    }

    public function getDefaultForecastAccount(): ?ForecastAccount
    {
        return $this->defaultForecastAccount;
    }

    public function setDefaultForecastAccount(?ForecastAccount $defaultForecastAccount): self
    {
        $this->defaultForecastAccount = $defaultForecastAccount;

        return $this;
    }

    public function getIsSuperAdmin(): ?bool
    {
        return $this->isSuperAdmin;
    }

    public function setIsSuperAdmin(bool $isSuperAdmin): self
    {
        $this->isSuperAdmin = $isSuperAdmin;

        return $this;
    }
}
