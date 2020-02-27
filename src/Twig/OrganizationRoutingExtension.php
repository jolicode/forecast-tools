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

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\AbstractExtension;
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Node;
use Twig\TwigFunction;

class OrganizationRoutingExtension extends AbstractExtension
{
    private $generator;
    private $requestStack;

    public function __construct(UrlGeneratorInterface $generator, RequestStack $requestStack)
    {
        $this->generator = $generator;
        $this->requestStack = $requestStack;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('organization_url', [$this, 'getUrl'], ['is_safe_callback' => [$this, 'isUrlGenerationSafe']]),
            new TwigFunction('organization_path', [$this, 'getPath'], ['is_safe_callback' => [$this, 'isUrlGenerationSafe']]),
        ];
    }

    public function getPath(string $name, array $parameters = [], bool $relative = false): string
    {
        return $this->generator->generate(
            'organization_' . $name,
            $this->addOrganizationParameter($parameters),
            $relative ? UrlGeneratorInterface::RELATIVE_PATH : UrlGeneratorInterface::ABSOLUTE_PATH
        );
    }

    public function getUrl(string $name, array $parameters = [], bool $schemeRelative = false): string
    {
        return $this->generator->generate(
            'organization_' . $name,
            $this->addOrganizationParameter($parameters),
            $schemeRelative ? UrlGeneratorInterface::NETWORK_PATH : UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    /**
     * Determines at compile time whether the generated URL will be safe and thus
     * saving the unneeded automatic escaping for performance reasons.
     *
     * The URL generation process percent encodes non-alphanumeric characters. So there is no risk
     * that malicious/invalid characters are part of the URL. The only character within an URL that
     * must be escaped in html is the ampersand ("&") which separates query params. So we cannot mark
     * the URL generation as always safe, but only when we are sure there won't be multiple query
     * params. This is the case when there are none or only one constant parameter given.
     * E.g. we know beforehand this will be safe:
     * - path('route')
     * - path('route', {'param': 'value'})
     * But the following may not:
     * - path('route', var)
     * - path('route', {'param': ['val1', 'val2'] }) // a sub-array
     * - path('route', {'param1': 'value1', 'param2': 'value2'})
     * If param1 and param2 reference placeholder in the route, it would still be safe. But we don't know.
     *
     * @param Node $argsNode The arguments of the path/url function
     *
     * @return array An array with the contexts the URL is safe
     */
    public function isUrlGenerationSafe(Node $argsNode): array
    {
        // support named arguments
        $paramsNode = $argsNode->hasNode('parameters') ? $argsNode->getNode('parameters') : ($argsNode->hasNode(1) ? $argsNode->getNode(1) : null);

        if (
            null === $paramsNode || $paramsNode instanceof ArrayExpression && \count($paramsNode) <= 2 &&
            (!$paramsNode->hasNode(1) || $paramsNode->getNode(1) instanceof ConstantExpression)
        ) {
            return ['html'];
        }

        return [];
    }

    private function addOrganizationParameter(array $parameters): array
    {
        $request = $this->requestStack->getMasterRequest();

        return array_merge($parameters, [
            'slug' => $request->attributes->get('forecastAccount')->getSlug(),
        ]);
    }
}
