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
use SqlFtw\Sql\Expression\BaseType;
use SqlFtw\Sql\Expression\DataType;
use SqlFtw\Sql\Keyword;

class TypeParser
{
    use StrictBehaviorMixin;

    /**
     * data_type:
     *     BIT[(length)]
     *   | TINYINT[(length)] [UNSIGNED | SIGNED] [ZEROFILL]
     *   | SMALLINT[(length)] [UNSIGNED | SIGNED] [ZEROFILL]
     *   | MEDIUMINT[(length)] [UNSIGNED | SIGNED] [ZEROFILL]
     *   | INT[(length)] [UNSIGNED | SIGNED] [ZEROFILL]
     *   | INTEGER[(length)] [UNSIGNED | SIGNED] [ZEROFILL]
     *   | BIGINT[(length)] [UNSIGNED | SIGNED] [ZEROFILL]
     *   | REAL[(length,decimals)] [UNSIGNED | SIGNED] [ZEROFILL]
     *   | DOUBLE[(length,decimals)] [UNSIGNED | SIGNED] [ZEROFILL]
     *   | FLOAT[(length,decimals)] [UNSIGNED | SIGNED] [ZEROFILL]
     *   | FLOAT[(precision)] [UNSIGNED | SIGNED] [ZEROFILL]
     *   | DECIMAL[(length[,decimals])] [UNSIGNED | SIGNED] [ZEROFILL]
     *   | NUMERIC[(length[,decimals])] [UNSIGNED | SIGNED] [ZEROFILL]
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
     *   | BLOB[(length)]
     *   | MEDIUMBLOB
     *   | LONGBLOB
     *   | TINYTEXT [BINARY] [CHARACTER SET charset_name] [COLLATE collation_name]
     *   | TEXT[(length)] [BINARY] [CHARACTER SET charset_name] [COLLATE collation_name]
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
        $dataType = $tokenList->expectMultiKeywordsEnum(BaseType::class);

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
                    if ($dataType->equalsAny(BaseType::NUMERIC, BaseType::DECIMAL, BaseType::FLOAT)) {
                        if ($tokenList->has(TokenType::COMMA)) {
                            $decimals = $tokenList->expectInt();
                        }
                    } else {
                        $tokenList->expect(TokenType::COMMA);
                        $decimals = $tokenList->expectInt();
                    }
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
            if ($unsigned === false) {
                $tokenList->passKeyword(Keyword::SIGNED);
            }
            $zerofill = $tokenList->hasKeyword(Keyword::ZEROFILL);
        }

        if ($tokenList->hasKeywords(Keyword::CHARSET)) {
            $charset = $tokenList->expectCharsetName();
        } elseif ($tokenList->hasKeywords(Keyword::CHARACTER, Keyword::SET)) {
            if ($tokenList->hasKeyword(Keyword::BINARY)) {
                $charset = Charset::get(Charset::BINARY);
            } else {
                $charset = $tokenList->expectCharsetName();
            }
        } elseif ($tokenList->hasKeyword(Keyword::BINARY)) {
            $charset = Charset::get(Charset::BINARY);
        }
        if ($tokenList->hasKeyword(Keyword::COLLATE)) {
            $collation = $tokenList->expectCollationName();
        }

        return new DataType($dataType, $params, $unsigned, $charset, $collation, $zerofill);
    }

}
