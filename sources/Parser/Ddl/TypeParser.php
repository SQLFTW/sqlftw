<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser\Ddl;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Parser\TokenList;
use SqlFtw\Parser\TokenType;
use SqlFtw\Sql\Charset;
use SqlFtw\Sql\Collation;
use SqlFtw\Sql\Ddl\BaseType;
use SqlFtw\Sql\Ddl\DataType;
use SqlFtw\Sql\Keyword;

class TypeParser
{
    use StrictBehaviorMixin;

    /**
     * data_type:
     *     BIT[(length)]
     *   | TINYINT[(length)] [UNSIGNED] [ZEROFILL]
     *   | SMALLINT[(length)] [UNSIGNED] [ZEROFILL]
     *   | MEDIUMINT[(length)] [UNSIGNED] [ZEROFILL]
     *   | INT[(length)] [UNSIGNED] [ZEROFILL]
     *   | INTEGER[(length)] [UNSIGNED] [ZEROFILL]
     *   | BIGINT[(length)] [UNSIGNED] [ZEROFILL]
     *   | REAL[(length,decimals)] [UNSIGNED] [ZEROFILL]
     *   | DOUBLE[(length,decimals)] [UNSIGNED] [ZEROFILL]
     *   | FLOAT[(length,decimals)] [UNSIGNED] [ZEROFILL]
     *   | DECIMAL[(length[,decimals])] [UNSIGNED] [ZEROFILL]
     *   | NUMERIC[(length[,decimals])] [UNSIGNED] [ZEROFILL]
     *   | DATE
     *   | TIME[(fsp)]
     *   | TIMESTAMP[(fsp)]
     *   | DATETIME[(fsp)]
     *   | YEAR
     *   | CHAR[(length)] [BINARY] [CHARACTER SET charset_name] [COLLATE collation_name]
     *   | VARCHAR(length) [BINARY] [CHARACTER SET charset_name] [COLLATE collation_name]
     *   | BINARY[(length)]
     *   | VARBINARY(length)
     *   | TINYBLOB
     *   | BLOB
     *   | MEDIUMBLOB
     *   | LONGBLOB
     *   | TINYTEXT [BINARY] [CHARACTER SET charset_name] [COLLATE collation_name]
     *   | TEXT [BINARY] [CHARACTER SET charset_name] [COLLATE collation_name]
     *   | MEDIUMTEXT [BINARY] [CHARACTER SET charset_name] [COLLATE collation_name]
     *   | LONGTEXT [BINARY] [CHARACTER SET charset_name] [COLLATE collation_name]
     *   | ENUM(value1,value2,value3, ...) [CHARACTER SET charset_name] [COLLATE collation_name]
     *   | SET(value1,value2,value3, ...) [CHARACTER SET charset_name] [COLLATE collation_name]
     *   | JSON
     *   | spatial_type
     *
     *   + aliases defined in BaseType class
     */
    public function parseType(TokenList $tokenList): DataType
    {
        $keyword = $tokenList->getAnyKeyword(Keyword::DOUBLE, Keyword::NATIONAL, Keyword::CHARACTER, Keyword::CHAR, Keyword::LONG);
        if ($keyword === Keyword::DOUBLE) {
            if ($tokenList->hasKeyword(Keyword::PRECISION)) {
                $dataType = BaseType::get(BaseType::DOUBLE_PRECISION);
            } else {
                $dataType = BaseType::get(BaseType::DOUBLE);
            }
        } elseif ($keyword === Keyword::NATIONAL) {
            $second = $tokenList->expectAnyKeyword(Keyword::CHAR, Keyword::VARCHAR);
            $dataType = BaseType::get($keyword . ' ' . $second);
        } elseif ($keyword === Keyword::CHARACTER) {
            if ($tokenList->hasKeyword(Keyword::VARYING)) {
                $dataType = BaseType::get(BaseType::CHARACTER_VARYING);
            } else {
                $dataType = BaseType::get(BaseType::CHARACTER);
            }
        } elseif ($keyword === Keyword::CHAR) {
            if ($tokenList->hasKeyword(Keyword::BYTE)) {
                $dataType = BaseType::get(BaseType::CHAR_BYTE);
            } else {
                $dataType = BaseType::get(BaseType::CHAR);
            }
        } elseif ($keyword === Keyword::LONG) {
            $second = $tokenList->getAnyKeyword(Keyword::VARCHAR, Keyword::VARBINARY);
            if ($second !== null) {
                $dataType = BaseType::get($keyword . ' ' . $second);
            } else {
                $dataType = BaseType::get(BaseType::LONG);
            }
        } else {
            /** @var BaseType $dataType */
            $dataType = $tokenList->expectKeywordEnum(BaseType::class);
        }

        $settings = $tokenList->getSettings();
        if ($settings->canonicalizeTypes()) {
            $dataType = $dataType->canonicalize($settings);
        }

        $params = $charset = $collation = null;
        $unsigned = $zerofill = false;

        if ($dataType->hasLength()) {
            $length = $decimals = null;
            if ($tokenList->has(TokenType::LEFT_PARENTHESIS)) {
                $length = $tokenList->expectInt();
                if ($dataType->hasDecimals()) {
                    $tokenList->expect(TokenType::COMMA);
                    $decimals = $tokenList->expectInt();
                }
                $tokenList->expect(TokenType::RIGHT_PARENTHESIS);
            }
            if ($decimals !== null) {
                /** @var int[] $params */
                $params = [$length, $decimals];
            } else {
                $params = $length;
            }
        } elseif ($dataType->hasValues()) {
            $tokenList->expect(TokenType::LEFT_PARENTHESIS);
            $params = [];
            do {
                $params[] = $tokenList->expectString();
            } while ($tokenList->hasComma());
            $tokenList->expect(TokenType::RIGHT_PARENTHESIS);
        } elseif ($dataType->hasFsp() && $tokenList->has(TokenType::LEFT_PARENTHESIS)) {
            $params = $tokenList->expectInt();
            $tokenList->expect(TokenType::RIGHT_PARENTHESIS);
        }

        if ($dataType->isNumber()) {
            $unsigned = $tokenList->hasKeyword(Keyword::UNSIGNED);
            $zerofill = $tokenList->hasKeyword(Keyword::ZEROFILL);
        }

        if ($dataType->hasCharset()) {
            if ($tokenList->hasKeywords(Keyword::CHARSET)) {
                $charset = $tokenList->expectNameOrStringEnum(Charset::class);
            } elseif ($tokenList->hasKeywords(Keyword::CHARACTER, Keyword::SET)) {
                $charset = $tokenList->expectNameOrStringEnum(Charset::class);
            }
            if ($tokenList->hasKeyword(Keyword::COLLATE)) {
                $collation = $tokenList->expectNameOrStringEnum(Collation::class);
            }
        }

        return new DataType($dataType, $params, $unsigned, $charset, $collation, $zerofill);
    }

}
