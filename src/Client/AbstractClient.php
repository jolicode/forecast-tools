<?php

/*
 * This file is part of JoliCode's Forecast Tools project.
 *
 * (c) JoliCode <coucou@jolicode.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Client;

use JoliCode\Forecast\Client as ForecastClient;
use JoliCode\Harvest\Api\Client as HarvestClient;
use JoliCode\Slack\Client as SlackClient;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Contracts\Cache\CacheInterface;

abstract class AbstractClient
{
    protected AdapterInterface|CacheInterface $pool;

    abstract protected function __client(): ForecastClient|HarvestClient|SlackClient;

    abstract protected function __namespace(): string;

    protected function __addKey(string $key): void
    {
        if ($this->pool->hasItem($this->__namespace())) {
            $item = $this->pool->getItem($this->__namespace());
            $value = $item->get();

            if (!\in_array($key, $value, true)) {
                $value[] = $key;
                $item->set($value);
                $this->pool->save($item);
            }
        } else {
            $this->pool->get($this->__namespace(), fn (): array => [$key]);
        }
    }

    public function __clearCache(): void
    {
        if ($this->pool->hasItem($this->__namespace())) {
            $value = $this->pool->getItem($this->__namespace())->get();

            if (\is_array($value)) {
                $value[] = $this->__namespace();
                $this->pool->deleteItems($value);
            }
        }
    }
}
