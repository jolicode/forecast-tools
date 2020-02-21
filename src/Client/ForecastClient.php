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

use JoliCode\Forecast\ClientFactory;
use Symfony\Component\Cache\Adapter\TraceableAdapter;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Cache\ItemInterface;

class ForecastClient extends AbstractClient
{
    private $client = null;
    private $namespace = '';
    private $requestStack;

    public function __construct(RequestStack $requestStack, TraceableAdapter $pool)
    {
        $this->requestStack = $requestStack;
        $this->pool = $pool;
    }

    protected function __client()
    {
        if (null === $this->client) {
            $forecastAccount = $this->requestStack->getCurrentRequest()->attributes->get('forecastAccount');

            $this->client = ClientFactory::create(
                $forecastAccount->getAccessToken(),
                $forecastAccount->getForecastId()
            );
        }

        return $this->client;
    }

    protected function __namespace()
    {
        if ('' === $this->namespace) {
            $forecastAccount = $this->requestStack->getCurrentRequest()->attributes->get('forecastAccount');
            $this->namespace = 'forecast-' . $forecastAccount->getId();
        }

        return $this->namespace;
    }

    public function __call(string $name, array $arguments)
    {
        $nodeName = array_pop($arguments);
        $cacheKey = sprintf('%s-%s-%s', $this->__namespace(), $name, md5(serialize($arguments)));

        // The callable will only be executed on a cache miss.
        $this->__addKey($cacheKey);

        return $this->pool->get($cacheKey, function (ItemInterface $item) use ($name, $arguments, $nodeName) {
            return $this->call($name, $arguments, $nodeName);
        });
    }

    public function call(string $name, array $arguments, string $nodeName, $responseToUpdate = null)
    {
        $response = \call_user_func_array([
            $this->__client(),
            $name,
        ], $arguments);

        $getter = sprintf('get%s', ucfirst($nodeName));
        $setter = sprintf('set%s', ucfirst($nodeName));
        $data = $response->$getter();
        $ids = array_map(function ($a) {
            return $a->getId();
        }, $data);
        $indicedData = array_combine($ids, $data);

        if (null !== $responseToUpdate) {
            $indicedData = array_replace($responseToUpdate->$getter(), $indicedData);
        }

        $response->$setter($indicedData);

        return $response;
    }
}
