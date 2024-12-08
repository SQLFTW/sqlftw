<?php declare(strict_types = 1);
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
use function array_values;
use function bin2hex;
use function explode;
use function implode;
use function ltrim;
use function preg_match;
use function str_replace;
use function strpos;

class MysqlNormalizer implements Normalizer
{

    private const MYSQL_ESCAPES = [
        '\\' => '\\\\',
        "\0" => '\0',
        "\x08" => '\b',
        "\n" => '\n',
        "\r" => '\r',
        "\t" => '\t',
        "\x1a" => '\Z', // legacy Win EOF
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
        bool $quoteAllNames = true,
        bool $escapeWhitespace = true
    ) {
        $this->platform = $platform;
        $this->session = $session;
        $this->quoteAllNames = $quoteAllNames;
        $this->escapeWhitespace = $escapeWhitespace;

        $escapes = self::MYSQL_ESCAPES;
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
        $sqlMode = $this->session->getMode();
        $quote = ($sqlMode->value & SqlMode::ANSI_QUOTES) !== 0 ? '"' : '`';
        $name = str_replace($quote, $quote . $quote, $name);
        $upper = strtoupper($name);

        $needsQuoting = $this->quoteAllNames
            || isset($this->platform->reserved[$upper])
            || strpos($name, $quote) !== false // contains quote
            || preg_match('~[\pL_]~u', $name) === 0 // does not contain letters
            || preg_match('~[\pC\pM\pS\pZ\p{Pd}\p{Pe}\p{Pf}\p{Pi}\p{Po}\p{Ps}]~u', ltrim($name, '@')) !== 0; // contains control, mark, symbols, whitespace, punctuation except _

        if ($needsQuoting && ($sqlMode->value & SqlMode::NO_BACKSLASH_ESCAPES) === 0) {
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

    public function formatBool(bool $value): string
    {
        return $value ? 'TRUE' : 'FALSE';
    }

    public function formatString(string $value): string
    {
        if (($this->session->getMode()->value & SqlMode::NO_BACKSLASH_ESCAPES) === 0) {
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
        return "X'" . bin2hex($value) . "'";
    }

}
