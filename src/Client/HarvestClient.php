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

use App\Repository\UserRepository;
use JoliCode\Harvest\Api\Client;
use JoliCode\Harvest\ClientFactory;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Cache\ItemInterface;

class HarvestClient extends AbstractClient
{
    private $client = null;
    private $namespace = '';
    private $requestStack;
    private $security;
    private $userRepository;
    private bool $cacheEnabled = true;
    private $cacheStatusForNextRequestOnly = null;

    public function __construct(RequestStack $requestStack, AdapterInterface $pool, Security $security, UserRepository $userRepository)
    {
        $this->requestStack = $requestStack;
        $this->pool = $pool;
        $this->security = $security;
        $this->userRepository = $userRepository;
    }

    public function __disableCache()
    {
        $this->cacheEnabled = false;
    }

    public function __disableCacheForNextRequestOnly()
    {
        $this->cacheStatusForNextRequestOnly = false;
    }

    public function __enableCache()
    {
        $this->cacheEnabled = true;
    }

    public function __enableCacheForNextRequestOnly()
    {
        $this->cacheStatusForNextRequestOnly = true;
    }

    public function __client(): Client
    {
        if (null === $this->client) {
            $email = $this->security->getUser()->getUsername();
            $user = $this->userRepository->findOneBy(['email' => $email]);
            $forecastAccount = $this->requestStack->getCurrentRequest()->attributes->get('forecastAccount');

            if ($user) {
                $accessToken = $user->getAccessToken();
            } else {
                $accessToken = $forecastAccount->getAccessToken();
            }

            $this->client = ClientFactory::create(
                $accessToken,
                $forecastAccount->getHarvestAccount()->getHarvestId()
            );
        }

        return $this->client;
    }

    protected function __namespace()
    {
        if ('' === $this->namespace) {
            $forecastAccount = $this->requestStack->getCurrentRequest()->attributes->get('forecastAccount');
            $this->namespace = 'harvest-' . $forecastAccount->getId();
        }

        return $this->namespace;
    }

    public function __call(string $name, array $arguments)
    {
        $nodeName = array_pop($arguments);

        if (!\is_array(end($arguments))) {
            $arguments[] = [];
        }

        if ($this->cacheEnabled && false !== $this->cacheStatusForNextRequestOnly || true === $this->cacheStatusForNextRequestOnly) {
            $cacheKey = sprintf('%s-%s-%s', $this->__namespace(), $name, md5(serialize($arguments)));

            // The callable will only be executed on a cache miss.
            $this->__addKey($cacheKey);
            $value = $this->pool->get($cacheKey, function (ItemInterface $item) use ($name, $arguments, $nodeName) {
                $response = $this->call($name, $arguments, $nodeName);

                return [
                    'time' => new \DateTime(),
                    'response' => $response,
                ];
            });

            $response = $value['response'];
            $now = new \DateTime();

            // if more than 60 seconds, try to check if something has changed
            if ($now->getTimestamp() - $value['time']->getTimestamp() > 60) {
                // get the last updated_at from the current objects
                $getter = sprintf('get%s', ucfirst($nodeName));
                $lastUpdated = array_reduce($response->$getter(), function ($carry, $item) {
                    if (!method_exists($item, 'getUpdatedAt')) {
                        return null;
                    }

                    if (null === $carry || $carry->getUpdatedAt() < $item->getUpdatedAt()) {
                        return $item;
                    }

                    return $carry;
                }, null);

                if (null !== $lastUpdated) {
                    $arguments[\count($arguments) - 1]['updated_since'] = $lastUpdated->getUpdatedAt()->format('c');
                    $response = $this->call($name, $arguments, $nodeName, $response);

                    // set this in cache for key $cacheKey
                    $item = $this->pool->getItem($cacheKey);
                    $item->set([
                        'time' => $now,
                        'response' => $response,
                    ]);

                    $this->pool->save($item);
                }
            }
        } else {
            $response = $this->call($name, $arguments, $nodeName);
        }

        if (null !== $this->cacheStatusForNextRequestOnly) {
            $this->cacheStatusForNextRequestOnly = null;
        }

        return $response;
    }

    public function call(string $name, array $arguments, string $nodeName, $responseToUpdate = null)
    {
        $getter = sprintf('get%s', ucfirst($nodeName));
        $setter = sprintf('set%s', ucfirst($nodeName));
        $nextPage = 1;
        $page = 0;
        $accumulator = [];
        $argumentsKey = \count($arguments) - 1;

        if (null !== $responseToUpdate) {
            $accumulator = $responseToUpdate->$getter();
        }

        while ($nextPage > $page) {
            $response = \call_user_func_array([
                $this->__client(),
                $name,
            ], $arguments);

            $toAccumulate = $response->$getter();
            $ids = array_map(function ($a) {
                if (method_exists($a, 'getId')) {
                    return $a->getId();
                }

                return $a->getProjectId();
            }, $toAccumulate);
            $accumulator = array_replace($accumulator, array_combine($ids, $response->$getter()));
            $arguments[$argumentsKey]['page'] = $response->getNextPage();

            $nextPage = $response->getNextPage();
            $page = $response->getPage();
        }

        $response->$setter($accumulator);

        return $response;
    }
}
