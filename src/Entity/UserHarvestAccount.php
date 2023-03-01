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

#[ORM\Entity(repositoryClass: \App\Repository\UserHarvestAccountRepository::class)]
class UserHarvestAccount implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'userHarvestAccounts')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: HarvestAccount::class, inversedBy: 'userHarvestAccounts')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private HarvestAccount $harvestAccount;

    #[ORM\Column(type: 'integer')]
    private int $harvestId;

    #[ORM\Column(type: 'boolean')]
    private bool $isAdmin;

    #[ORM\Column(type: 'boolean')]
    private bool $isEnabled;

    public function __toString(): string
    {
        return sprintf('%s%s%s',
            $this->harvestAccount->getName(),
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

    public function getHarvestAccount(): ?HarvestAccount
    {
        return $this->harvestAccount;
    }

    public function setHarvestAccount(?HarvestAccount $harvestAccount): self
    {
        $this->harvestAccount = $harvestAccount;

        return $this;
    }

    public function getHarvestId(): ?int
    {
        return $this->harvestId;
    }

    public function setHarvestId(int $harvestId): self
    {
        $this->harvestId = $harvestId;

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
}
