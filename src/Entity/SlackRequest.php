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

/**
 * @ORM\Entity(repositoryClass="App\Repository\SlackRequestRepository")
 */
class SlackRequest
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     * @Gedmo\Timestampable(on="create")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $url;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $response;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $requestPayload;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $XSlackSignature;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $XSlackRequestTimestamp;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isSignatureValid;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $requestContent;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getResponse(): ?string
    {
        return $this->response;
    }

    public function setResponse(string $response): self
    {
        $this->response = $response;

        return $this;
    }

    public function getRequestPayload(): ?string
    {
        return $this->requestPayload;
    }

    public function setRequestPayload(?string $requestPayload): self
    {
        $this->requestPayload = $requestPayload;

        return $this;
    }

    public function getXSlackSignature(): ?string
    {
        return $this->XSlackSignature;
    }

    public function setXSlackSignature(?string $XSlackSignature): self
    {
        $this->XSlackSignature = $XSlackSignature;

        return $this;
    }

    public function getXSlackRequestTimestamp(): ?string
    {
        return $this->XSlackRequestTimestamp;
    }

    public function setXSlackRequestTimestamp(?string $XSlackRequestTimestamp): self
    {
        $this->XSlackRequestTimestamp = $XSlackRequestTimestamp;

        return $this;
    }

    public function getIsSignatureValid(): ?bool
    {
        return $this->isSignatureValid;
    }

    public function setIsSignatureValid(bool $isSignatureValid): self
    {
        $this->isSignatureValid = $isSignatureValid;

        return $this;
    }

    public function getRequestContent(): ?string
    {
        return $this->requestContent;
    }

    public function setRequestContent(?string $requestContent): self
    {
        $this->requestContent = $requestContent;

        return $this;
    }
}
