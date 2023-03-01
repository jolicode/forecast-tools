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

#[ORM\Entity(repositoryClass: \App\Repository\UserForecastAccountRepository::class)]
class UserForecastAccount implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'userForecastAccounts')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: ForecastAccount::class, inversedBy: 'userForecastAccounts')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ForecastAccount $forecastAccount;

    #[ORM\Column(type: 'boolean')]
    private bool $isAdmin;

    #[ORM\Column(type: 'boolean')]
    private bool $isEnabled;

    #[ORM\Column(type: 'integer')]
    private int $forecastId;

    public function __toString(): string
    {
        return sprintf('%s%s%s',
            $this->forecastAccount->getName(),
            $this->isAdmin ? ' (admin)' : '',
            !$this->isEnabled ? ' (disabled)' : ''
        );
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

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

    public function getIsAdmin(): ?bool
    {
        return $this->isAdmin;
    }

    public function setIsAdmin(bool $isAdmin): self
    {
        $this->isAdmin = $isAdmin;

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

    public function getForecastId(): ?int
    {
        return $this->forecastId;
    }

    public function setForecastId(int $forecastId): self
    {
        $this->forecastId = $forecastId;

        return $this;
    }
}
