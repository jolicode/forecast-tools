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

use App\Entity\SlackTeam;
use JoliCode\Slack\ClientFactory;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Contracts\Cache\ItemInterface;

class SlackClient extends AbstractClient
{
    private $clients = [];

    /** @var SlackTeam */
    private $slackTeam = null;

    public function __construct(AdapterInterface $pool)
    {
        $this->pool = $pool;
    }

    public function getSlackTeam(): SlackTeam
    {
        if (null === $this->slackTeam) {
            throw new \Exception('Please choose a Slack team');
        }

        return $this->slackTeam;
    }

    public function setSlackTeam(SlackTeam $slackTeam)
    {
        $this->slackTeam = $slackTeam;
    }

    protected function __client()
    {
        $slackTeam = $this->getSlackTeam();

        if (!isset($this->clients[$slackTeam->getId()])) {
            $this->clients[$slackTeam->getId()] = ClientFactory::create(
                $slackTeam->getAccessToken()
            );
        }

        return $this->clients[$slackTeam->getId()];
    }

    protected function __namespace()
    {
        return 'slack-' . $this->getSlackTeam()->getId();
    }

    public function __call(string $name, array $arguments)
    {
        $cacheKey = sprintf('%s-%s-%s', $this->__namespace(), $name, md5(serialize($arguments)));
        $this->__addKey($cacheKey);

        return $this->pool->get($cacheKey, function (ItemInterface $item) use ($name, $arguments) {
            return $this->call($name, $arguments);
        });
    }

    public function call(string $name, array $arguments)
    {
        return \call_user_func_array([
            $this->__client(),
            $name,
        ], $arguments);
    }
}
