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
     *
     * @param TokenList $tokenList
     * @return DataType
     */
    public function parseType(TokenList $tokenList): DataType
    {
        $keyword = $tokenList->mayConsumeAnyKeyword(Keyword::DOUBLE, Keyword::NATIONAL, Keyword::CHARACTER, Keyword::CHAR, Keyword::LONG);
        if ($keyword === Keyword::DOUBLE) {
            if ($tokenList->mayConsumeKeyword(Keyword::PRECISION)) {
                $dataType = BaseType::get(BaseType::DOUBLE_PRECISION);
            } else {
                $dataType = BaseType::get(BaseType::DOUBLE);
            }
        } elseif ($keyword === Keyword::NATIONAL) {
            $second = $tokenList->consumeAnyKeyword(Keyword::CHAR, Keyword::VARCHAR);
            $dataType = BaseType::get($keyword . ' ' . $second);
        } elseif ($keyword === Keyword::CHARACTER) {
            if ($tokenList->mayConsumeKeyword(Keyword::VARYING)) {
                $dataType = BaseType::get(BaseType::CHARACTER_VARYING);
            } else {
                $dataType = BaseType::get(BaseType::CHARACTER);
            }
        } elseif ($keyword === Keyword::CHAR) {
            if ($tokenList->mayConsumeKeyword(Keyword::BYTE)) {
                $dataType = BaseType::get(BaseType::CHAR_BYTE);
            } else {
                $dataType = BaseType::get(BaseType::CHAR);
            }
        } elseif ($keyword === Keyword::LONG) {
            $second = $tokenList->mayConsumeAnyKeyword(Keyword::VARCHAR, Keyword::VARBINARY);
            if ($second !== null) {
                $dataType = BaseType::get($keyword . ' ' . $second);
            } else {
                $dataType = BaseType::get(BaseType::LONG);
            }
        } else {
            /** @var BaseType $dataType */
            $dataType = $tokenList->consumeKeywordEnum(BaseType::class);
        }

        $settings = $tokenList->getSettings();
        if ($settings->canonicalizeTypes()) {
            $dataType = $dataType->canonicalize($settings);
        }

        $params = $charset = $collation = null;
        $unsigned = $zerofill = false;

        if ($dataType->hasLength()) {
            $length = $decimals = null;
            if ($tokenList->mayConsume(TokenType::LEFT_PARENTHESIS)) {
                $length = $tokenList->consumeInt();
                if ($dataType->hasDecimals()) {
                    $tokenList->consume(TokenType::COMMA);
                    $decimals = $tokenList->consumeInt();
                }
                $tokenList->consume(TokenType::RIGHT_PARENTHESIS);
            }
            if ($decimals !== null) {
                /** @var int[] $params */
                $params = [$length, $decimals];
            } else {
                $params = $length;
            }
        } elseif ($dataType->hasValues()) {
            $tokenList->consume(TokenType::LEFT_PARENTHESIS);
            $params = [];
            do {
                $params[] = $tokenList->consumeString();
            } while ($tokenList->mayConsumeComma());
            $tokenList->consume(TokenType::RIGHT_PARENTHESIS);
        }/* elseif ($dataType->hasFsp() && $tokenList->mayConsume(TokenType::LEFT_PARENTHESIS)) {
            // todo: fsp???
            $tokenList->consume(TokenType::RIGHT_PARENTHESIS);
        }*/

        if ($dataType->isNumber()) {
            $unsigned = (bool) $tokenList->mayConsumeKeyword(Keyword::UNSIGNED);
            $zerofill = (bool) $tokenList->mayConsumeKeyword(Keyword::ZEROFILL);
        }

        if ($dataType->hasCharset()) {
            if ($tokenList->mayConsumeKeywords(Keyword::CHARACTER, Keyword::SET)) {
                $charset = Charset::get($tokenList->consumeNameOrString());
            }
            if ($tokenList->mayConsumeKeyword(Keyword::COLLATE)) {
                $collation = Collation::get($tokenList->consumeNameOrString());
            }
        }

        return new DataType($dataType, $params, $unsigned, $charset, $collation, $zerofill);
    }

}
