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

#[ORM\Entity(repositoryClass: \App\Repository\ForecastAccountSlackTeamRepository::class)]
class ForecastAccountSlackTeam implements \Stringable
{
    final public const MAX_ERRORS_ALLOWED = 3;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\ManyToOne(targetEntity: SlackTeam::class, inversedBy: 'forecastAccountSlackTeams')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private SlackTeam $slackTeam;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $updatedBy = null;

    /**
     * @Gedmo\Timestampable(on="update")
     */
    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $updatedAt;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $channel = null;

    #[ORM\Column(type: 'string', length: 15, nullable: true)]
    private ?string $channelId = null;

    #[ORM\ManyToOne(targetEntity: ForecastAccount::class, inversedBy: 'forecastAccountSlackTeams')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ForecastAccount $forecastAccount;

    #[ORM\Column(type: 'integer')]
    private int $errorsCount = 0;

    public function __toString(): string
    {
        return sprintf('%s #%s', $this->slackTeam->getTeamName(), $this->channel);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSlackTeam(): ?SlackTeam
    {
        return $this->slackTeam;
    }

    public function setSlackTeam(?SlackTeam $slackTeam): self
    {
        $this->slackTeam = $slackTeam;

        return $this;
    }

    public function getChannel(): ?string
    {
        return $this->channel;
    }

    public function setChannel(?string $channel): self
    {
        $this->channel = $channel;

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

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getChannelId(): ?string
    {
        return $this->channelId;
    }

    public function setChannelId(?string $channelId): self
    {
        $this->channelId = $channelId;

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

    public function getErrorsCount(): int
    {
        return $this->errorsCount;
    }

    public function increaseErrorsCount(int $errorsCount = 1): self
    {
        $this->errorsCount += $errorsCount;

        return $this;
    }

    public function setErrorsCount(int $errorsCount): self
    {
        $this->errorsCount = $errorsCount;

        return $this;
    }
}
