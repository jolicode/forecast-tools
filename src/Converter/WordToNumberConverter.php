<?php

/*
 * This file is part of JoliCode's Forecast Tools project.
 *
 * (c) JoliCode <coucou@jolicode.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Converter;

class WordToNumberConverter
{
    final public const WORDS_NUMBER = [
        'one' => '1',
        'two' => '2',
        'three' => '3',
        'four' => '4',
        'five' => '5',
        'six' => '6',
        'seven' => '7',
        'eight' => '8',
        'nine' => '9',
        'ten' => '10',
        'eleven' => '11',
        'twelve' => '12',
        'thirteen' => '13',
        'fourteen' => '14',
        'fifteen' => '15',
        'sixteen' => '16',
        'seventeen' => '17',
        'eighteen' => '18',
        'nineteen' => '19',
        'twenty' => '20',
        'thirty' => '30',
        'forty' => '40',
        'fourty' => '40',
        'fifty' => '50',
        'sixty' => '60',
        'seventy' => '70',
        'eighty' => '80',
        'ninety' => '90',
        'hundred' => '100',
        'thousand' => '1000',
        'million' => '1000000',
        'billion' => '1000000000',
    ];

    public function convert(string $value): string
    {
        preg_match_all('#((?:^|and|,| |-)*(\b' . implode('\b|\b', array_keys(self::WORDS_NUMBER)) . '\b))+#i', $value, $tokens);
        $tokens = $tokens[0];
        usort($tokens, fn ($a, $b): int => \strlen((string) $a) - \strlen((string) $b));

        foreach ($tokens as $token) {
            $token = trim(strtolower((string) $token));

            if (str_starts_with($token, 'and ')) {
                $token = trim(substr($token, 4));
            }

            preg_match_all('#(?:(?:and|,| |-)*\b' . implode('\b|\b', array_keys(self::WORDS_NUMBER)) . '\b)+#', $token, $words);
            $words = $words[0];
            $num = '0';
            $total = 0;

            foreach ($words as $word) {
                $word = trim((string) $word);
                $val = self::WORDS_NUMBER[$word];

                if (-1 === bccomp($val, '100')) {
                    $num = bcadd($num, $val);
                    continue;
                } elseif (0 === bccomp($val, '100')) {
                    $num = bcmul($num, $val);
                    continue;
                }

                $num = bcmul($num, $val);
                $total = bcadd($total, $num);
                $num = '0';
            }

            $total = bcadd($total, $num);
            $value = preg_replace("#\b$token\b#i", $total, (string) $value);
        }

        return $value;
    }
}
