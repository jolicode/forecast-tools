<?php

/*
 * This file is part of JoliCode's Forecast Tools project.
 *
 * (c) JoliCode <coucou@jolicode.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class RedirectException extends HttpException
{
    /**
     * @param string|null           $message  The internal exception message
     * @param \Throwable|null       $previous The previous exception
     * @param int                   $code     The internal exception code
     * @param array<string, string> $headers  The internal exception code
     */
    public function __construct(string $location, ?string $message = '', \Throwable $previous = null, int $code = 0, array $headers = [])
    {
        $headers['Location'] = $location;
        parent::__construct(302, $message, $previous, $headers, $code);
    }
}
