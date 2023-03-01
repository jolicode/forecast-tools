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

#[ORM\Entity(repositoryClass: \App\Repository\InvoiceNotesRequirementRepository::class)]
class InvoiceNotesRequirement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'integer')]
    private int $harvestClientId;

    /**
     * @Gedmo\Timestampable(on="update")
     */
    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $updatedAt;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $updatedBy = null;

    #[ORM\Column(type: 'text')]
    private string $requirement;

    #[ORM\ManyToOne(targetEntity: HarvestAccount::class, inversedBy: 'invoiceNotesRequirements')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private HarvestAccount $harvestAccount;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getHarvestClientId(): ?int
    {
        return $this->harvestClientId;
    }

    public function setHarvestClientId(int $harvestClientId): self
    {
        $this->harvestClientId = $harvestClientId;

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

    public function getUpdatedBy(): ?User
    {
        return $this->updatedBy;
    }

    public function setUpdatedBy(?User $updatedBy): self
    {
        $this->updatedBy = $updatedBy;

        return $this;
    }

    public function getRequirement(): ?string
    {
        return $this->requirement;
    }

    public function setRequirement(string $requirement): self
    {
        $this->requirement = $requirement;

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
}
