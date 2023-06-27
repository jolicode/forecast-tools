<?php

/*
 * This file is part of JoliCode's Forecast Tools project.
 *
 * (c) JoliCode <coucou@jolicode.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig;

use Symfony\Bridge\Twig\Extension\RoutingExtension;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class OrganizationRoutingExtension extends AbstractExtension
{
    public function __construct(
        private readonly UrlGeneratorInterface $generator,
        private readonly RequestStack $requestStack,

        #[Autowire(service: 'twig.extension.routing')]
        private readonly RoutingExtension $routingExtension
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('organization_url', $this->getUrl(...), ['is_safe_callback' => $this->routingExtension->isUrlGenerationSafe(...)]),
            new TwigFunction('organization_path', $this->getPath(...), ['is_safe_callback' => $this->routingExtension->isUrlGenerationSafe(...)]),
        ];
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function getPath(string $name, array $parameters = [], bool $relative = false): string
    {
        return $this->generator->generate(
            'organization_' . $name,
            $this->addOrganizationParameter($parameters),
            $relative ? UrlGeneratorInterface::RELATIVE_PATH : UrlGeneratorInterface::ABSOLUTE_PATH
        );
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function getUrl(string $name, array $parameters = [], bool $schemeRelative = false): string
    {
        return $this->generator->generate(
            'organization_' . $name,
            $this->addOrganizationParameter($parameters),
            $schemeRelative ? UrlGeneratorInterface::NETWORK_PATH : UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    /**
     * @param array<string, mixed> $parameters
     *
     * @return array<string, mixed>
     */
    private function addOrganizationParameter(array $parameters): array
    {
        $request = $this->requestStack->getMainRequest();

        return [...$parameters, 'slug' => $request->attributes->get('forecastAccount')->getSlug()];
    }
}
