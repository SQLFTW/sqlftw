<?php

namespace SqlFtw\Analyzer\Context\FileIterator;

use Generator;
use SqlFtw\Parser\Parser;
use function implode;
use function is_array;
use function preg_match;
use function stripcslashes;
use function substr;
use function token_get_all;
use function trim;
use const T_CONSTANT_ENCAPSED_STRING;
use const T_ENCAPSED_AND_WHITESPACE;

class PhpFilesIterator extends FileIterator
{

    public function getIterator(): Generator
    {
        $keywords = implode('|', Parser::STARTING_KEYWORDS);
        $statementStartRe = "~^({$keywords}|/\\*|--)[^\w]+~i";

        while ($result = $this->getNextFileContents()) {
            [$version, $contents] = $result;

            $statements = [];
            foreach (self::extractConstantStrings($contents) as $string) {
                if (preg_match($statementStartRe, $string) !== 0) {
                    $statements[] = $string;
                }
            }

            yield $version => $statements;
        }
    }

    /**
     * @return list<string>
     */
    public static function extractConstantStrings(string $code): array
    {
        $tokens = token_get_all($code);

        $strings = [];
        foreach ($tokens as $token) {
            if (!is_array($token)) {
                continue;
            }

            [$type, $string] = $token;

            if ($type !== T_CONSTANT_ENCAPSED_STRING // "foo", 'foo'
                && $type !== T_ENCAPSED_AND_WHITESPACE // constant parts of interpolated string and heredoc/nowdoc
            ) {
                continue;
            }

            if (str_starts_with($string, '"') && str_ends_with($string, '"')
                || str_starts_with($string, "'") && str_ends_with($string, "'")
            ) {
                $string = substr($string, 1, -1);
            }

            $strings[] = $type === T_CONSTANT_ENCAPSED_STRING
                ? trim(stripcslashes($string))
                : trim($string);
        }

        return $strings;
    }

}
