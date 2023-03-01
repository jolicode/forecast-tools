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

#[ORM\Entity(repositoryClass: \App\Repository\SlackCallRepository::class)]
class SlackCall
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    /**
     * @Gedmo\Timestampable(on="create")
     */
    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: 'string', length: 255)]
    private string $url;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $requestBody = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $responseBody = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $statusCode = null;

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

    public function getRequestBody(): ?string
    {
        return $this->requestBody;
    }

    public function setRequestBody(?string $requestBody): self
    {
        $this->requestBody = $requestBody;

        return $this;
    }

    public function getResponseBody(): ?string
    {
        return $this->responseBody;
    }

    public function setResponseBody(?string $responseBody): self
    {
        $this->responseBody = $responseBody;

        return $this;
    }

    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    public function setStatusCode(?int $statusCode): self
    {
        $this->statusCode = $statusCode;

        return $this;
    }
}
