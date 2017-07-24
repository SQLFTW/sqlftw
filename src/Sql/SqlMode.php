<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql;

use SqlFtw\Platform\Mode;

class SqlMode extends \Dogma\StringSet
{

    public const ALLOW_INVALID_DATES = 'ALLOW_INVALID_DATES';
    public const ANSI_QUOTES = 'ANSI_QUOTES'; /// syntax
    public const ERROR_FOR_DIVISION_BY_ZERO = 'ERROR_FOR_DIVISION_BY_ZERO';
    public const HIGH_NOT_PRECEDENCE = 'HIGH_NOT_PRECEDENCE'; /// syntax
    public const IGNORE_SPACE = 'IGNORE_SPACE'; /// syntax
    public const NO_AUTO_CREATE_USER = 'NO_AUTO_CREATE_USER';
    public const NO_AUTO_VALUE_ON_ZERO = 'NO_AUTO_VALUE_ON_ZERO';
    public const NO_BACKSLASH_ESCAPES = 'NO_BACKSLASH_ESCAPES'; /// syntax
    public const NO_DIR_IN_CREATE = 'NO_DIR_IN_CREATE';
    public const NO_ENGINE_SUBSTITUTION = 'NO_ENGINE_SUBSTITUTION';
    public const NO_FIELD_OPTIONS = 'NO_FIELD_OPTIONS';
    public const NO_KEY_OPTIONS = 'NO_KEY_OPTIONS';
    public const NO_TABLE_OPTIONS = 'NO_TABLE_OPTIONS';
    public const NO_UNSIGNED_SUBTRACTION = 'NO_UNSIGNED_SUBTRACTION';
    public const NO_ZERO_DATE = 'NO_ZERO_DATE';
    public const NO_ZERO_IN_DATE = 'NO_ZERO_IN_DATE';
    public const ONLY_FULL_GROUP_BY = 'ONLY_FULL_GROUP_BY';
    public const PAD_CHAR_TO_FULL_LENGTH = 'PAD_CHAR_TO_FULL_LENGTH';
    public const PIPES_AS_CONCAT = 'PIPES_AS_CONCAT'; /// syntax
    public const REAL_AS_FLOAT = 'REAL_AS_FLOAT'; /// syntax
    public const STRICT_ALL_TABLES = 'STRICT_ALL_TABLES';
    public const STRICT_TRANS_TABLES = 'STRICT_TRANS_TABLES';
    public const TIME_TRUNCATE_FRACTIONAL = 'TIME_TRUNCATE_FRACTIONAL';

    public const TRADITIONAL = 'TRADITIONAL';

    public const ANSI = 'ANSI';
    public const DB2 = 'DB2';
    public const MAXDB = 'MAXDB';
    public const MSSQL = 'MSSQL';
    public const ORACLE = 'ORACLE';
    public const POSTGRESQL = 'POSTGRESQL';

    private $groups = [
        self::TRADITIONAL => [
            self::STRICT_TRANS_TABLES,
            self::STRICT_ALL_TABLES,
            self::NO_ZERO_IN_DATE,
            self::NO_ZERO_DATE,
            self::ERROR_FOR_DIVISION_BY_ZERO,
            self::NO_AUTO_CREATE_USER,
            self::NO_ENGINE_SUBSTITUTION,
        ],
        self::ANSI => [
            self::ANSI_QUOTES,
            self::IGNORE_SPACE,
            self::PIPES_AS_CONCAT,
            self::REAL_AS_FLOAT,
            self::ONLY_FULL_GROUP_BY
        ],
        self::DB2 => [
            self::ANSI_QUOTES,
            self::IGNORE_SPACE,
            self::PIPES_AS_CONCAT,
            self::NO_KEY_OPTIONS,
            self::NO_TABLE_OPTIONS,
            self::NO_FIELD_OPTIONS
        ],
        self::MAXDB => [
            self::ANSI_QUOTES,
            self::IGNORE_SPACE,
            self::PIPES_AS_CONCAT,
            self::NO_KEY_OPTIONS,
            self::NO_TABLE_OPTIONS,
            self::NO_FIELD_OPTIONS,
            self::NO_AUTO_CREATE_USER
        ],
        self::MSSQL => [
            self::ANSI_QUOTES,
            self::IGNORE_SPACE,
            self::PIPES_AS_CONCAT,
            self::NO_KEY_OPTIONS,
            self::NO_TABLE_OPTIONS,
            self::NO_FIELD_OPTIONS,
        ],
        self::ORACLE => [
            self::ANSI_QUOTES,
            self::IGNORE_SPACE,
            self::PIPES_AS_CONCAT,
            self::NO_KEY_OPTIONS,
            self::NO_TABLE_OPTIONS,
            self::NO_FIELD_OPTIONS,
            self::NO_AUTO_CREATE_USER,
        ],
        self::POSTGRESQL => [
            self::ANSI_QUOTES,
            self::IGNORE_SPACE,
            self::PIPES_AS_CONCAT,
            self::NO_KEY_OPTIONS,
            self::NO_TABLE_OPTIONS,
            self::NO_FIELD_OPTIONS,
        ],
    ];

    public function getMode(): Mode
    {
        static $translate = [
            self::ANSI_QUOTES => Mode::ANSI_QUOTES,
            self::IGNORE_SPACE => Mode::IGNORE_SPACE,
            self::NO_BACKSLASH_ESCAPES => Mode::NO_BACKSLASH_ESCAPES,
            self::PIPES_AS_CONCAT => Mode::PIPES_AS_CONCAT,
            self::REAL_AS_FLOAT => Mode::REAL_AS_FLOAT,
            self::HIGH_NOT_PRECEDENCE => Mode::HIGH_NOT_PRECEDENCE,
        ];

        $mode = 0;
        foreach ($this->getValues() as $value) {
            if (isset($translate[$value])) {
                $mode |= $translate[$value];
            }
        }

        return Mode::get($mode);
    }

}
