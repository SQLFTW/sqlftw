<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

// phpcs:disable Squiz.Arrays.ArrayDeclaration.ValueNoNewline

namespace SqlFtw\Sql\Expression;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Platform\Mode;
use SqlFtw\Platform\Platform;
use SqlFtw\Platform\PlatformSettings;
use SqlFtw\Sql\Feature;
use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\SqlEnum;
use function in_array;

class BaseType extends SqlEnum implements Feature
{

    // bitwise
    public const BIT = Keyword::BIT;

    // integers
    public const TINYINT = Keyword::TINYINT;
    public const SMALLINT = Keyword::SMALLINT;
    public const MEDIUMINT = Keyword::MEDIUMINT;
    public const INT = Keyword::INT;
    public const BIGINT = Keyword::BIGINT;
    public const BOOL = Keyword::BOOL; // TINYINT
    public const BOOLEAN = Keyword::BOOLEAN; // TINYINT
    public const INTEGER = Keyword::INTEGER; // INT
    public const MIDDLEINT = Keyword::MIDDLEINT; // MEDIUMINT
    public const INT1 = Keyword::INT1; // TINYINT
    public const INT2 = Keyword::INT2; // SMALLINT
    public const INT3 = Keyword::INT3; // MEDIUMINT
    public const INT4 = Keyword::INT4; // INT
    public const INT8 = Keyword::INT8; // BIGINT
    public const SERIAL = Keyword::SERIAL; // alias for: BIGINT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE

    // floats
    public const REAL = Keyword::REAL;
    public const FLOAT = Keyword::FLOAT;
    public const DOUBLE = Keyword::DOUBLE;
    public const FLOAT4 = Keyword::FLOAT4; // FLOAT
    public const FLOAT8 = Keyword::FLOAT8; // DOUBLE
    public const DOUBLE_PRECISION = Keyword::DOUBLE . ' ' . Keyword::PRECISION; // DOUBLE

    // decimal
    public const DECIMAL = Keyword::DECIMAL;
    public const NUMERIC = Keyword::NUMERIC; // DECIMAL
    public const DEC = Keyword::DEC; // DECIMAL
    public const FIXED = Keyword::FIXED; // DECIMAL

    // time
    public const YEAR = Keyword::YEAR;
    public const DATE = Keyword::DATE;
    public const DATETIME = Keyword::DATETIME;
    public const TIME = Keyword::TIME;
    public const TIMESTAMP = Keyword::TIMESTAMP;

    // texts
    public const CHAR = Keyword::CHAR;
    public const VARCHAR = Keyword::VARCHAR;
    public const TINYTEXT = Keyword::TINYTEXT;
    public const TEXT = Keyword::TEXT;
    public const MEDIUMTEXT = Keyword::MEDIUMTEXT;
    public const LONGTEXT = Keyword::LONGTEXT;
    public const CHARACTER = Keyword::CHARACTER; // CHAR
    public const NCHAR = Keyword::NCHAR; // CHAR
    public const NATIONAL_CHAR = Keyword::NATIONAL . ' ' . Keyword::CHAR; // CHAR
    public const CHARACTER_VARYING = Keyword::CHARACTER . ' ' . Keyword::VARYING; // VARCHAR
    public const NVARCHAR = Keyword::NVARCHAR; // VARCHAR
    public const NATIONAL_VARCHAR = Keyword::NATIONAL . ' ' . Keyword::VARCHAR; // VARCHAR
    public const LONG = Keyword::LONG; // MEDIUMTEXT
    public const LONG_VARCHAR = Keyword::LONG . ' ' . Keyword::VARCHAR; // MEDIUMTEXT

    // binary
    public const BINARY = Keyword::BINARY;
    public const VARBINARY = Keyword::VARBINARY;
    public const TINYBLOB = Keyword::TINYBLOB;
    public const BLOB = Keyword::BLOB;
    public const MEDIUMBLOB = Keyword::MEDIUMBLOB;
    public const LONGBLOB = Keyword::LONGBLOB;
    public const CHAR_BYTE = Keyword::CHAR . ' ' . Keyword::BYTE; // BINARY
    public const LONG_VARBINARY = Keyword::LONG . ' ' . Keyword::VARBINARY; // MEDIUMBLOB

    // sets
    public const ENUM = Keyword::ENUM;
    public const SET = Keyword::SET;

    // json
    public const JSON = Keyword::JSON;

    // geometry
    public const GEOMETRY = Keyword::GEOMETRY;
    public const POINT = Keyword::POINT;
    public const LINESTRING = Keyword::LINESTRING;
    public const POLYGON = Keyword::POLYGON;
    public const GEOMETRYCOLLECTION = Keyword::GEOMETRYCOLLECTION;
    public const MULTIPOINT = Keyword::MULTIPOINT;
    public const MULTILINESTRING = Keyword::MULTILINESTRING;
    public const MULTIPOLYGON = Keyword::MULTIPOLYGON;

    public function canonicalize(PlatformSettings $settings): self
    {
        $platform = $settings->getPlatform()->getName();
        if ($platform === Platform::MARIA) {
            $platform = Platform::MYSQL;
        }
        $typeAliases = $settings->getPlatform()->getFeatures()->getTypeAliases();

        $value = $this->getValue();
        if ($platform === Platform::MYSQL && $value === self::REAL) {
            if ($settings->getMode()->containsAny(Mode::REAL_AS_FLOAT)) {
                return self::get(self::FLOAT);
            } else {
                return self::get(self::DOUBLE);
            }
        } elseif (isset($typeAliases[$value])) {
            return self::get($typeAliases[$value]);
        } else {
            return $this;
        }
    }

    public function serialize(Formatter $formatter): string
    {
        $settings = $formatter->getSettings();
        if ($settings->canonicalizeTypes()) {
            $settings->setCanonicalizeTypes(false); // prevent recursion
            $result = $this->canonicalize($settings)->serialize($formatter);
            $settings->setCanonicalizeTypes(true);

            return $result;
        }

        return parent::serialize($formatter);
    }

    public function isInteger(): bool
    {
        return in_array(
            $this->getValue(),
            [
                self::TINYINT, self::SMALLINT, self::MEDIUMINT, self::INT, self::BIGINT, self::YEAR,
                self::MIDDLEINT, self::INTEGER, self::INT1, self::INT2, self::INT3, self::INT4, self::INT8, self::BOOL, self::BOOLEAN,
            ],
            true
        );
    }

    public function isFloatingPointNumber(): bool
    {
        return in_array($this->getValue(), [self::FLOAT, self::DOUBLE, self::REAL, self::FLOAT4, self::FLOAT8, self::DOUBLE_PRECISION], true);
    }

    public function isDecimal(): bool
    {
        return in_array($this->getValue(), [self::DECIMAL, self::DEC, self::NUMERIC, self::FIXED], true);
    }

    public function isNumber(): bool
    {
        return $this->isInteger() || $this->isFloatingPointNumber() || $this->isDecimal() || $this->getValue() === self::BIT;
    }

    public function isText(): bool
    {
        return in_array(
            $this->getValue(),
            [
                self::CHAR, self::VARCHAR, self::TINYTEXT, self::TEXT, self::MEDIUMTEXT, self::LONGTEXT,
                self::CHARACTER, self::NCHAR, self::NATIONAL_CHAR, self::CHARACTER_VARYING, self::NVARCHAR,
                self::NATIONAL_VARCHAR, self::LONG, self::LONG_VARCHAR, self::ENUM, self::SET,
            ],
            true
        );
    }

    public function isBinary(): bool
    {
        return in_array(
            $this->getValue(),
            [
                self::BINARY, self::VARBINARY, self::TINYBLOB, self::BLOB, self::MEDIUMBLOB, self::LONGBLOB,
                self::CHAR_BYTE, self::LONG_VARBINARY,
            ],
            true
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
            [
                self::GEOMETRY, self::POINT, self::LINESTRING, self::POLYGON,
                self::GEOMETRYCOLLECTION, self::MULTIPOINT, self::MULTILINESTRING, self::MULTIPOLYGON,
            ],
            true
        );
    }

    public function isTime(): bool
    {
        return in_array(
            $this->getValue(),
            [self::DATE, self::TIME, self::DATETIME, self::TIMESTAMP],
            true
        );
    }

    public function hasLength(): bool
    {
        return $this->isNumber() || $this->needsLength() || $this->getValue() === self::BLOB || $this->getValue() === self::TEXT;
    }

    public function needsLength(): bool
    {
        return in_array(
            $this->getValue(),
            [
                self::CHAR, self::VARCHAR, self::BINARY, self::VARBINARY,
                self::CHARACTER, self::NCHAR, self::NATIONAL_CHAR, self::CHARACTER_VARYING, self::NVARCHAR, self::NATIONAL_VARCHAR, self::CHAR_BYTE,
            ],
            true
        );
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
        return in_array($this->getValue(), [self::ENUM, self::SET], true);
    }

    public function hasCharset(): bool
    {
        return $this->isText() || $this->hasValues();
    }

}
