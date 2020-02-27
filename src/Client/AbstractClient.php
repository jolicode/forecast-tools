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

use Symfony\Contracts\Cache\ItemInterface;

abstract class AbstractClient
{
    protected $pool;

    abstract protected function __client();

    abstract protected function __namespace();

    protected function __addKey(string $key)
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
            $this->pool->get($this->__namespace(), function (ItemInterface $item) use ($key) {
                return [$key];
            });
        }
    }

    public function __clearCache()
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
