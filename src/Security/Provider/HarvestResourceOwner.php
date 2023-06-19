<?php

/*
 * This file is part of JoliCode's Forecast Tools project.
 *
 * (c) JoliCode <coucou@jolicode.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Security\Provider;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;

class HarvestResourceOwner implements ResourceOwnerInterface
{
    /**
     * Domain.
     */
    protected string $domain;

    /**
     * Creates new resource owner.
     *
     * @param array<string, mixed> $response
     */
    public function __construct(
        protected array $response = []
    ) {
    }

    /**
     * Get resource owner id.
     *
     * @return string|null
     */
    public function getId()
    {
        return $this->response['user']['id'] ?? null;
    }

    /**
     * Get resource owner email.
     *
     * @return string|null
     */
    public function getEmail()
    {
        return $this->response['user']['email'] ?? null;
    }

    /**
     * Get resource owner name.
     *
     * @return string|null
     */
    public function getName()
    {
        if (isset($this->response['user']['first_name']) || isset($this->response['user']['last_name'])) {
            return trim(sprintf(
                '%s %s',
                $this->response['user']['first_name'],
                $this->response['user']['last_name']
            ));
        }

        return null;
    }

    /**
     * Get resource owner avatar url.
     *
     * @return string|null
     */
    public function getAvatar()
    {
        return $this->response['user']['avatar_url'] ?? null;
    }

    /**
     * Set resource owner domain.
     */
    public function setDomain(string $domain): self
    {
        $this->domain = $domain;

        return $this;
    }

    /**
     * Return all of the owner details available as an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->response;
    }
}
