<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity(repositoryClass="App\Repository\InvoiceDueDelayRequirementRepository")
 * @UniqueEntity(
 *     fields={"harvestClientId"},
 *     errorPath="harvestClientId",
 *     message="There is already a constraint for the client ""{{ value }}""."
 * )
 */
class InvoiceDueDelayRequirement
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer", unique=true)
     */
    private $harvestClientId;

    /**
     * @ORM\Column(type="datetime")
     * @Gedmo\Timestampable(on="update")
     */
    private $updatedAt;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     */
    private $updatedBy;

    /**
     * @ORM\Column(type="integer")
     */
    private $delay;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\HarvestAccount", inversedBy="invoiceDueDelayRequirements")
     * @ORM\JoinColumn(nullable=false)
     */
    private $harvestAccount;

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

    public function getDelay(): ?int
    {
        return $this->delay;
    }

    public function setDelay(int $delay): self
    {
        $this->delay = $delay;

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
