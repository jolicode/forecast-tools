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

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class SlackMrkdwnExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter(
                'slack_mrkdwn',
                $this->convertSlackMrkdwn(...),
                ['is_safe' => ['html']]
            ),
        ];
    }

    public function convertSlackMrkdwn(string $string): string
    {
        // disclaimer: do not use this hacky solution for a robust mrkdwn -> html transformation
        // it is convenient in our case but may break for larger strings
        $patterns = [
            '/<(.*)\|(.*)>/' => '<a href="$1">$2</a>',
            '/\*(.*)\*/' => '<strong>$1</strong>',
            '/_(.*)_/' => '<em>$1</em>',
            '/\\n/' => '<br />',
        ];
        $string = preg_replace(array_keys($patterns), $patterns, $string);

        return $string;
    }
}
