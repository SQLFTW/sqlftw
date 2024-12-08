<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Platform\Normalizer;

use SqlFtw\Platform\Platform;
use SqlFtw\Session\Session;
use SqlFtw\Sql\SqlMode;
use function array_keys;
use function array_map;
use function array_values;
use function bin2hex;
use function explode;
use function implode;
use function ltrim;
use function preg_match;
use function str_replace;
use function strpos;

/**
 * https://www.postgresql.org/docs/current/runtime-config-compatible.html
 */
class PostgreNormalizer implements Normalizer
{

    private const PG_ESCAPES = [
        '\\' => '\\\\',
        // \0 cannot be stored in char, only in bytea
        "\x08" => '\b',
        "\f" => '\f',
        "\n" => '\n',
        "\r" => '\r',
        "\t" => '\t',
    ];

    private Platform $platform;

    private Session $session;

    private bool $quoteAllNames;

    private bool $escapeWhitespace;

    /** @var list<string> */
    private array $escapeNoWsKeys;

    /** @var list<string> */
    private array $escapeNoWsValues;

    /** @var list<string> */
    private array $escapeWsKeys;

    /** @var list<string> */
    private array $escapeWsValues;

    public function __construct(
        Platform $platform,
        Session $session,
        bool $quoteAllNames = false,
        bool $escapeWhitespace = true
    ) {
        $this->platform = $platform;
        $this->session = $session;
        $this->quoteAllNames = $quoteAllNames;
        $this->escapeWhitespace = $escapeWhitespace;

        $escapes = self::PG_ESCAPES;
        $this->escapeWsKeys = array_keys($escapes);
        $this->escapeWsValues = array_values($escapes);
        unset($escapes["\n"], $escapes["\r"], $escapes["\t"]);
        $this->escapeNoWsKeys = array_keys($escapes);
        $this->escapeNoWsValues = array_values($escapes);
    }

    public function quoteAllNames(bool $quote): void
    {
        $this->quoteAllNames = $quote;
    }

    public function escapeWhitespace(bool $escape): void
    {
        $this->escapeWhitespace = $escape;
    }

    public function formatName(string $name): string
    {
        if ($name === '*') {
            return '*';
        }

        $quote = '"';
        // todo: backslash_quote variable
        $noBackslashEscapes = true;

        $name = str_replace($quote, $quote . $quote, $name);
        $needsQuoting = $this->quoteAllNames
            || strpos($name, $quote) !== false // contains quote
            || preg_match('~[\pL_]~u', $name) === 0 // does not contain letters
            || preg_match('~[\pC\pM\pS\pZ\p{Pd}\p{Pe}\p{Pf}\p{Pi}\p{Po}\p{Ps}]~u', ltrim($name, '@')) !== 0 // contains control, mark, symbols, whitespace, punctuation except _
            || isset($this->platform->reserved[$name]);

        if ($needsQuoting && !$noBackslashEscapes) {
            $name = str_replace($this->escapeNoWsKeys, $this->escapeNoWsValues, $name);
        }

        return $needsQuoting ? $quote . $name . $quote : $name;
    }

    public function formatQualifiedName(string $name): string
    {
        $parts = array_map(function (string $name): string {
            return $this->formatName($name);
        }, explode('.', $name));

        return implode('.' , $parts);
    }

    /**
     * https://www.postgresql.org/docs/current/datatype-boolean.html
     */
    public function formatBool(bool $value): string
    {
        return $value ? '1' : '0';
    }

    public function formatString(string $value): string
    {
        // todo: backslash_quote variable
        $noBackslashEscapes = true;
        if (!$noBackslashEscapes) {
            if ($this->escapeWhitespace) {
                $value = str_replace($this->escapeWsKeys, $this->escapeWsValues, $value);
            } else {
                $value = str_replace($this->escapeNoWsKeys, $this->escapeNoWsValues, $value);
            }
        }

        return "'" . str_replace("'", "''", $value) . "'";
    }

    public function formatBinary(string $value): string
    {
        return "'\x" . bin2hex($value) . "'";
    }

}
