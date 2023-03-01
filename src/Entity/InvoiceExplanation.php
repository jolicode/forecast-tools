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

#[ORM\Entity(repositoryClass: \App\Repository\InvoiceExplanationRepository::class)]
class InvoiceExplanation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 20)]
    private string $explanationKey;

    #[ORM\Column(type: 'text')]
    private string $explanation;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $createdBy = null;

    /**
     * @Gedmo\Timestampable(on="create")
     */
    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    #[ORM\ManyToOne(targetEntity: InvoicingProcess::class, inversedBy: 'invoiceExplanations')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private InvoicingProcess $invoicingProcess;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getExplanationKey(): ?string
    {
        return $this->explanationKey;
    }

    public function setExplanationKey(string $explanationKey): self
    {
        $this->explanationKey = $explanationKey;

        return $this;
    }

    public function getExplanation(): ?string
    {
        return $this->explanation;
    }

    public function setExplanation(string $explanation): self
    {
        $this->explanation = $explanation;

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

    public function getInvoicingProcess(): ?InvoicingProcess
    {
        return $this->invoicingProcess;
    }

    public function setInvoicingProcess(?InvoicingProcess $invoicingProcess): self
    {
        $this->invoicingProcess = $invoicingProcess;

        return $this;
    }
}
