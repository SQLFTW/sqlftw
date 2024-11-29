<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql;

use Dogma\Math\IntCalc;
use Dogma\Math\PowersOfTwo;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Platform\Platform;
use SqlFtw\Sql\Expression\IntValue;
use SqlFtw\Sql\Expression\StringValue;
use function array_filter;
use function array_search;
use function explode;
use function implode;
use function strtoupper;
use function trim;

/**
 * MySQL sql_mode
 *
 * @see: https://dev.mysql.com/doc/refman/8.0/en/sql-mode.html
 * @see: https://mariadb.com/kb/en/sql-mode/
 */
class SqlMode implements StringValue, IntValue
{

    public const REAL_AS_FLOAT = 1;
    public const PIPES_AS_CONCAT = 2;
    public const ANSI_QUOTES = 4;
    public const IGNORE_SPACE = 8;
    /** @deprecated */
    public const NOT_USED = 16;
    public const ONLY_FULL_GROUP_BY = 32;
    public const NO_UNSIGNED_SUBTRACTION = 64;
    public const NO_DIR_IN_CREATE = 128;
    /** @deprecated */
    public const POSTGRESQL = 256;
    /** @deprecated */
    public const ORACLE = 512;
    /** @deprecated */
    public const MSSQL = 1024;
    /** @deprecated */
    public const DB2 = 2048;
    /** @deprecated */
    public const MAXDB = 4096;
    /** @deprecated */
    public const NO_KEY_OPTIONS = 8192;
    /** @deprecated */
    public const NO_TABLE_OPTIONS = PowersOfTwo::_16K;
    /** @deprecated */
    public const NO_FIELD_OPTIONS = PowersOfTwo::_32K;
    /** @deprecated */
    public const MYSQL323 = PowersOfTwo::_64K;
    /** @deprecated */
    public const MYSQL40 = PowersOfTwo::_128K;
    public const ANSI = PowersOfTwo::_256K;
    public const NO_AUTO_VALUE_ON_ZERO = PowersOfTwo::_512K;
    public const NO_BACKSLASH_ESCAPES = PowersOfTwo::_1M;
    public const STRICT_TRANS_TABLES = PowersOfTwo::_2M;
    public const STRICT_ALL_TABLES = PowersOfTwo::_4M;
    public const NO_ZERO_IN_DATE = PowersOfTwo::_8M;
    public const NO_ZERO_DATE = PowersOfTwo::_16M;
    public const ALLOW_INVALID_DATES = PowersOfTwo::_32M;
    public const ERROR_FOR_DIVISION_BY_ZERO = PowersOfTwo::_64M;
    public const TRADITIONAL = PowersOfTwo::_128M;
    /** @deprecated */
    public const NO_AUTO_CREATE_USER = PowersOfTwo::_256M;
    public const HIGH_NOT_PRECEDENCE = PowersOfTwo::_512M;
    public const NO_ENGINE_SUBSTITUTION = PowersOfTwo::_1G;
    public const PAD_CHAR_TO_FULL_LENGTH = PowersOfTwo::_2G; // todo: here we break 32-bit again :sigh:
    public const TIME_TRUNCATE_FRACTIONAL = PowersOfTwo::_4G;

    public const DEFAULT = 'DEFAULT';

    /** @var array<int, string> */
    private const NAMES = [
        self::REAL_AS_FLOAT => 'REAL_AS_FLOAT',
        self::PIPES_AS_CONCAT => 'PIPES_AS_CONCAT',
        self::ANSI_QUOTES => 'ANSI_QUOTES',
        self::IGNORE_SPACE => 'IGNORE_SPACE',
        self::NOT_USED => 'NOT_USED',
        self::ONLY_FULL_GROUP_BY => 'ONLY_FULL_GROUP_BY',
        self::NO_UNSIGNED_SUBTRACTION => 'NO_UNSIGNED_SUBTRACTION',
        self::NO_DIR_IN_CREATE => 'NO_DIR_IN_CREATE',
        self::POSTGRESQL => 'POSTGRESQL',
        self::ORACLE => 'ORACLE',
        self::MSSQL => 'MSSQL',
        self::DB2 => 'DB2',
        self::MAXDB => 'MAXDB',
        self::NO_KEY_OPTIONS => 'NO_KEY_OPTIONS',
        self::NO_TABLE_OPTIONS => 'NO_TABLE_OPTIONS',
        self::NO_FIELD_OPTIONS => 'NO_FIELD_OPTIONS',
        self::MYSQL323 => 'MYSQL323',
        self::MYSQL40 => 'MYSQL40',
        self::ANSI => 'ANSI',
        self::NO_AUTO_VALUE_ON_ZERO => 'NO_AUTO_VALUE_ON_ZERO',
        self::NO_BACKSLASH_ESCAPES => 'NO_BACKSLASH_ESCAPES',
        self::STRICT_TRANS_TABLES => 'STRICT_TRANS_TABLES',
        self::STRICT_ALL_TABLES => 'STRICT_ALL_TABLES',
        self::NO_ZERO_IN_DATE => 'NO_ZERO_IN_DATE',
        self::NO_ZERO_DATE => 'NO_ZERO_DATE',
        self::ALLOW_INVALID_DATES => 'ALLOW_INVALID_DATES',
        self::ERROR_FOR_DIVISION_BY_ZERO => 'ERROR_FOR_DIVISION_BY_ZERO',
        self::TRADITIONAL => 'TRADITIONAL',
        self::NO_AUTO_CREATE_USER => 'NO_AUTO_CREATE_USER',
        self::HIGH_NOT_PRECEDENCE => 'HIGH_NOT_PRECEDENCE',
        self::NO_ENGINE_SUBSTITUTION => 'NO_ENGINE_SUBSTITUTION',
        self::PAD_CHAR_TO_FULL_LENGTH => 'PAD_CHAR_TO_FULL_LENGTH',
        self::TIME_TRUNCATE_FRACTIONAL => 'TIME_TRUNCATE_FRACTIONAL',
    ];

    /** @var array<int, int> */
    private const GROUPS = [
        self::TRADITIONAL => self::STRICT_TRANS_TABLES
            | self::STRICT_ALL_TABLES
            | self::NO_ZERO_IN_DATE
            | self::NO_ZERO_DATE
            | self::ERROR_FOR_DIVISION_BY_ZERO
            | self::NO_AUTO_CREATE_USER
            | self::NO_ENGINE_SUBSTITUTION,
        self::ANSI => self::ANSI_QUOTES
            | self::IGNORE_SPACE
            | self::PIPES_AS_CONCAT
            | self::REAL_AS_FLOAT
            | self::ONLY_FULL_GROUP_BY,
        self::DB2 => self::ANSI_QUOTES
            | self::IGNORE_SPACE
            | self::PIPES_AS_CONCAT
            | self::NO_KEY_OPTIONS
            | self::NO_TABLE_OPTIONS
            | self::NO_FIELD_OPTIONS,
        self::MAXDB => self::ANSI_QUOTES
            | self::IGNORE_SPACE
            | self::PIPES_AS_CONCAT
            | self::NO_KEY_OPTIONS
            | self::NO_TABLE_OPTIONS
            | self::NO_FIELD_OPTIONS
            | self::NO_AUTO_CREATE_USER,
        self::MSSQL => self::ANSI_QUOTES
            | self::IGNORE_SPACE
            | self::PIPES_AS_CONCAT
            | self::NO_KEY_OPTIONS
            | self::NO_TABLE_OPTIONS
            | self::NO_FIELD_OPTIONS,
        self::ORACLE => self::ANSI_QUOTES
            | self::IGNORE_SPACE
            | self::PIPES_AS_CONCAT
            | self::NO_KEY_OPTIONS
            | self::NO_TABLE_OPTIONS
            | self::NO_FIELD_OPTIONS
            | self::NO_AUTO_CREATE_USER,
        self::POSTGRESQL => self::ANSI_QUOTES
            | self::IGNORE_SPACE
            | self::PIPES_AS_CONCAT
            | self::NO_KEY_OPTIONS
            | self::NO_TABLE_OPTIONS
            | self::NO_FIELD_OPTIONS,
    ];

    /** Value without groups expanded */
    public int $value = 0;

    /** Value with groups expanded */
    public int $fullValue = 0;

    public static function fromInt(int $int): self
    {
        if ($int < 0) {
            throw new InvalidDefinitionException("Invalid value for system variable @@sql_mode: {$int} - value must not be negative.");
        }
        $value = $fullValue = 0;
        foreach (IntCalc::binaryComponents($int) as $i) {
            if (isset(self::NAMES[$i])) {
                $value |= $i;
                $fullValue |= $i;
            } else {
                throw new InvalidDefinitionException("Invalid value for system variable @@sql_mode: {$int} - unknown component {$i}.");
            }
            if (isset(self::GROUPS[$i])) {
                $fullValue |= self::GROUPS[$i];
            }
        }

        $that = new self();
        $that->value = $value;
        $that->fullValue = $fullValue;

        return $that;
    }

    public static function fromString(string $string, Platform $platform): self
    {
        $string = trim($string);
        $parts = explode(',', strtoupper($string));
        $parts = array_filter($parts); // @phpstan-ignore arrayFilter.strict

        $value = 0;
        foreach ($parts as $part) {
            $part = strtoupper($part);
            if ($part === self::DEFAULT) {
                $value |= $platform->getDefaultSqlModeValue();
            } elseif (in_array($part, self::NAMES, true)) {
                $value |= array_search($part, self::NAMES, true);
            } else {
                throw new InvalidDefinitionException("Invalid value for system variable @@sql_mode: {$string}");
            }
        }

        return self::fromInt($value);
    }

    public function getValue(): string
    {
        return $this->asString();
    }

    public function asString(): string
    {
        $names = [];
        foreach (IntCalc::binaryComponents($this->value) as $i) {
            $names[] = self::NAMES[$i];
        }

        return implode(',', $names);
    }

    public function asInt(): int
    {
        return $this->value;
    }

    public function serialize(Formatter $formatter): string
    {
        return "'{$this->asString()}'";
    }

}
