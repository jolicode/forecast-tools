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
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Symfony\Contracts\Cache\CacheInterface;
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
    private array $client = [];
    /**
     * @var mixed|\App\Entity\ForecastAccount
     */
    private $forecastAccount;
    private bool $cacheEnabled = true;
    private ?bool $cacheStatusForNextRequestOnly = null;

    public function __construct(private readonly RequestStack $requestStack, AdapterInterface|CacheInterface $pool, private readonly Security $security, private readonly UserRepository $userRepository)
    {
        $this->pool = $pool;
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

            if (null !== $this->security->getUser() && !$this->security->isGranted(AuthenticatedVoter::IS_IMPERSONATOR)) {
                $email = $this->security->getUser()->getUserIdentifier();
                $user = $this->userRepository->findOneBy(['email' => $email]);
            }

            if (null !== $user) {
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

    /**
     * @param array<string, mixed> $arguments
     */
    public function __call(string $name, array $arguments): mixed
    {
        $nodeName = array_pop($arguments);

        if ($this->cacheEnabled && false !== $this->cacheStatusForNextRequestOnly || true === $this->cacheStatusForNextRequestOnly) {
            $cacheKey = sprintf('%s-%s-%s', $this->__namespace(), $name, md5(serialize($arguments)));

            // The callable will only be executed on a cache miss.
            $this->__addKey($cacheKey);

            $response = $this->pool->get($cacheKey, fn (ItemInterface $item) => $this->call($name, $arguments, $nodeName));
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

    /**
     * @param array<string, mixed> $arguments
     */
    public function call(string $name, array $arguments, string $nodeName, mixed $responseToUpdate = null): mixed
    {
        $response = \call_user_func_array([
            $this->__client(),
            $name,
        ], $arguments);

        $getter = sprintf('get%s', ucfirst($nodeName));
        $setter = sprintf('set%s', ucfirst($nodeName));
        $expectedClass = sprintf('JoliCode\Forecast\Api\Model\%s', ucfirst($nodeName));

        if (Error::class === $response::class) {
            return $responseToUpdate ?? \call_user_func([new $expectedClass(), $setter], []);
        }

        $data = \call_user_func([$response, $getter]);
        $ids = array_map(fn ($a) => $a->getId(), $data);
        $indicedData = array_combine($ids, $data);

        if (null !== $responseToUpdate) {
            $indicedData = array_replace(\call_user_func([$responseToUpdate, $getter()]), $indicedData);
        }

        \call_user_func([$response, $setter], $indicedData);

        return $response;
    }
}
