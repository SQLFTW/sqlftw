<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl;

use SqlFtw\Sql\Keyword;

class BaseType extends \SqlFtw\Sql\SqlEnum
{

    public const BIT = Keyword::BIT;

    public const TINYINT = Keyword::BIGINT;
    public const SMALLINT = Keyword::SMALLINT;
    public const MEDIUMINT = Keyword::MEDIUMINT;
    public const INT = Keyword::INT;
    public const BIGINT = Keyword::BIGINT;

    public const REAL = Keyword::REAL;
    public const FLOAT = Keyword::FLOAT;
    public const DOUBLE = Keyword::DOUBLE;

    public const DECIMAL = Keyword::DECIMAL;
    public const NUMERIC = Keyword::NUMERIC;

    public const YEAR = Keyword::YEAR;
    public const DATE = Keyword::DATE;
    public const DATETIME = Keyword::DATETIME;
    public const TIME = Keyword::TIME;
    public const TIMESTAMP = Keyword::TIMESTAMP;

    public const CHAR = Keyword::CHAR;
    public const VARCHAR = Keyword::VARCHAR;
    public const TINYTEXT = Keyword::TINYTEXT;
    public const TEXT = Keyword::TEXT;
    public const MEDIUMTEXT = Keyword::MEDIUMTEXT;
    public const LONGTEXT = Keyword::LONGTEXT;

    public const BINARY = Keyword::BINARY;
    public const VARBINARY = Keyword::VARBINARY;
    public const TINYBLOB = Keyword::TINYBLOB;
    public const BLOB = Keyword::BLOB;
    public const MEDIUMBLOB = Keyword::MEDIUMBLOB;
    public const LONGBLOB = Keyword::LONGBLOB;

    public const ENUM = Keyword::ENUM;
    public const SET = Keyword::SET;

    public const JSON = Keyword::JSON;

    public const GEOMETRY = Keyword::GEOMETRY;
    public const POINT = Keyword::POINT;
    public const LINESTRING = Keyword::LINESTRING;
    public const POLYGON = Keyword::POLYGON;

    public const GEOMETRYCOLLECTION = Keyword::GEOMETRYCOLLECTION;
    public const MULTIPOINT = Keyword::MULTIPOINT;
    public const MULTILINESTRING = Keyword::MULTILINESTRING;
    public const MULTIPOLYGON = Keyword::MULTIPOLYGON;

    public function isInteger(): bool
    {
        return in_array(
            $this->getValue(),
            [self::TINYINT, self::SMALLINT, self::MEDIUMINT, self::INT, self::BIGINT, self::YEAR]
        );
    }

    public function isFloatingPointNumber(): bool
    {
        return in_array($this->getValue(), [self::REAL, self::FLOAT, self::DOUBLE]);
    }

    public function isDecimal(): bool
    {
        return in_array($this->getValue(), [self::DECIMAL, self::NUMERIC]);
    }

    public function isNumber(): bool
    {
        return $this->isInteger() || $this->isFloatingPointNumber() || $this->isDecimal();
    }

    public function isText(): bool
    {
        return in_array(
            $this->getValue(),
            [self::CHAR, self::VARCHAR, self::TINYTEXT, self::TEXT, self::MEDIUMTEXT, self::LONGTEXT]
        );
    }

    public function isBinary(): bool
    {
        return in_array(
            $this->getValue(),
            [self::BINARY, self::VARBINARY, self::TINYBLOB, self::BLOB, self::MEDIUMBLOB, self::LONGBLOB]
        );
    }

    public function isString(): bool
    {
        return $this->isText() || $this->isBinary();
    }

    public function isSpatial(): bool
    {
        return in_array(
            $this->getValue(),
            [self::GEOMETRY, self::POINT, self::LINESTRING, self::POLYGON, self::GEOMETRYCOLLECTION, self::MULTIPOINT, self::MULTILINESTRING, self::MULTIPOLYGON]
        );
    }

    public function isTime(): bool
    {
        return in_array(
            $this->getValue(),
            [self::DATE, self::TIME, self::DATETIME, self::TIMESTAMP]
        );
    }

    public function hasLength(): bool
    {
        return $this->isNumber() || $this->needsLength();
    }

    public function needsLength(): bool
    {
        return in_array($this->getValue(), [self::CHAR, self::VARCHAR, self::BINARY, self::VARBINARY]);
    }

    public function hasDecimals(): bool
    {
        return $this->isFloatingPointNumber() || $this->isDecimal();
    }

    public function hasFsp(): bool
    {
        return $this->isTime() && $this->getValue() !== self::DATE;
    }

    public function hasValues(): bool
    {
        return in_array($this->getValue(), [self::ENUM, self::SET]);
    }

    public function hasCharset(): bool
    {
        return $this->isText() || $this->hasValues();
    }

}
