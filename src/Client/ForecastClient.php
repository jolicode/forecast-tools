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

use App\Entity\ForecastAccount;
use App\Repository\UserRepository;
use JoliCode\Forecast\Api\Model\Error;
use JoliCode\Forecast\Client;
use JoliCode\Forecast\ClientFactory;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * @method \JoliCode\Forecast\Api\Model\Assignments  listAssignments(array $options, string $nodeName)
 * @method \JoliCode\Forecast\Api\Model\Clients      listClients(string $nodeName)
 * @method \JoliCode\Forecast\Api\Model\People       listPeople(string $nodeName)
 * @method \JoliCode\Forecast\Api\Model\Placeholders listPlaceholders(string $nodeName)
 * @method \JoliCode\Forecast\Api\Model\Projects     listProjects(string $nodeName)
 */
class ForecastClient extends AbstractClient
{
    /** @var Client[] */
    private $client = [];
    private $requestStack;
    private $security;
    private $userRepository;
    private $forecastAccount = null;
    private bool $cacheEnabled = true;
    private $cacheStatusForNextRequestOnly = null;

    public function __construct(RequestStack $requestStack, AdapterInterface $pool, Security $security, UserRepository $userRepository)
    {
        $this->requestStack = $requestStack;
        $this->pool = $pool;
        $this->security = $security;
        $this->userRepository = $userRepository;
    }

    public function __disableCache(): void
    {
        $this->cacheEnabled = false;
    }

    public function __disableCacheForNextRequestOnly(): void
    {
        $this->cacheStatusForNextRequestOnly = false;
    }

    public function __enableCache(): void
    {
        $this->cacheEnabled = true;
    }

    public function __enableCacheForNextRequestOnly(): void
    {
        $this->cacheStatusForNextRequestOnly = true;
    }

    public function __client(): Client
    {
        $forecastAccount = $this->getForecastAccount();

        if (!isset($this->client[$forecastAccount->getForecastId()])) {
            $user = null;
            $forecastAccount = $this->getForecastAccount();

            if ($this->security->getUser()) {
                $email = $this->security->getUser()->getUserIdentifier();
                $user = $this->userRepository->findOneBy(['email' => $email]);
            }

            if ($user) {
                $accessToken = $user->getAccessToken();
            } else {
                $accessToken = $forecastAccount->getAccessToken();
            }

            $this->client[$forecastAccount->getForecastId()] = ClientFactory::create(
                $accessToken,
                (string) $forecastAccount->getForecastId()
            );
        }

        return $this->client[$forecastAccount->getForecastId()];
    }

    protected function __namespace(): string
    {
        return 'forecast-' . $this->getForecastAccount()->getId();
    }

    public function __call(string $name, array $arguments)
    {
        $nodeName = array_pop($arguments);

        if ($this->cacheEnabled && false !== $this->cacheStatusForNextRequestOnly || true === $this->cacheStatusForNextRequestOnly) {
            $cacheKey = sprintf('%s-%s-%s', $this->__namespace(), $name, md5(serialize($arguments)));

            // The callable will only be executed on a cache miss.
            $this->__addKey($cacheKey);

            $response = $this->pool->get($cacheKey, function (ItemInterface $item) use ($name, $arguments, $nodeName) {
                return $this->call($name, $arguments, $nodeName);
            });
        } else {
            $response = $this->call($name, $arguments, $nodeName);
        }

        if (null !== $this->cacheStatusForNextRequestOnly) {
            $this->cacheStatusForNextRequestOnly = null;
        }

        return $response;
    }

    public function getForecastAccount(): ForecastAccount
    {
        if (null === $this->forecastAccount) {
            $this->forecastAccount = $this->requestStack->getCurrentRequest()->attributes->get('forecastAccount');
        }

        return $this->forecastAccount;
    }

    public function setForecastAccount(ForecastAccount $forecastAccount): void
    {
        $this->forecastAccount = $forecastAccount;
    }

    public function call(string $name, array $arguments, string $nodeName, $responseToUpdate = null)
    {
        $response = \call_user_func_array([
            $this->__client(),
            $name,
        ], $arguments);

        $getter = sprintf('get%s', ucfirst($nodeName));
        $setter = sprintf('set%s', ucfirst($nodeName));
        $expectedClass = sprintf('JoliCode\Forecast\Api\Model\%s', ucfirst($nodeName));

        if (Error::class === \get_class($response)) {
            return $responseToUpdate ?? \call_user_func([new $expectedClass(), $setter], []);
        }

        $data = \call_user_func([$response, $getter]);
        $ids = array_map(function ($a) {
            return $a->getId();
        }, $data);
        $indicedData = array_combine($ids, $data);

        if (null !== $responseToUpdate) {
            $indicedData = array_replace(\call_user_func([$responseToUpdate, $getter()]), $indicedData);
        }

        \call_user_func([$response, $setter], $indicedData);

        return $response;
    }
}
