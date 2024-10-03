<?php declare(strict_types = 1);

namespace SqlFtw\Util;

use Dogma\Str as DogmaStr;
use function ctype_cntrl;
use function ctype_print;
use function function_exists;
use function iconv_strlen;
use function mb_strlen;
use function ord;
use function strlen;

class Str extends DogmaStr
{

    /**
     * Utf-8 string length - speed optimized with slow fallback
     */
    public static function length(string $string): int
    {
        if (ctype_print($string) || ctype_cntrl($string)) {
            // ascii - fastest even with the two ctype functions in condition
            return strlen($string);
        } elseif (function_exists('mb_strlen')) {
            return mb_strlen($string, 'utf-8');
        } elseif (function_exists('iconv_strlen')) {
            return iconv_strlen($string, 'utf-8'); // @phpstan-ignore return.type (let it fail on false)
        } else {
            // calculate utf-8 char count the painfully slow way
            $bytes = strlen($string);
            $subtract = 0;
            for ($n = 0; $n < $bytes; $n++) {
                $byte = $string[$n];
                // does not match single-byte codes and multibyte sequence start codes, matches multibyte-sequence continuation codes
                if ((ord($byte) & 0b11000000) === 0b10000000) {
                    $subtract++;
                }
            }

            return $bytes - $subtract;
        }
    }

}
