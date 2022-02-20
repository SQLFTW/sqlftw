<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser\Dal;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Parser\TokenList;
use SqlFtw\Sql\Charset;
use SqlFtw\Sql\Collation;
use SqlFtw\Sql\Dal\Set\SetCharacterSetCommand;
use SqlFtw\Sql\Dal\Set\SetNamesCommand;
use SqlFtw\Sql\Keyword;

class CharsetCommandsParser
{
    use StrictBehaviorMixin;

    /**
     * SET {CHARACTER SET | CHARSET}
     *     {'charset_name' | DEFAULT}
     */
    public function parseSetCharacterSet(TokenList $tokenList): SetCharacterSetCommand
    {
        $tokenList->expectKeyword(Keyword::SET);
        $keyword = $tokenList->expectAnyKeyword(Keyword::CHARACTER, Keyword::CHARSET);
        if ($keyword === Keyword::CHARACTER) {
            $tokenList->expectKeyword(Keyword::SET);
        }
        if ($tokenList->hasKeyword(Keyword::DEFAULT)) {
            $charset = null;
        } else {
            $charset = Charset::get($tokenList->expectNameOrString());
        }
        $tokenList->expectEnd();

        return new SetCharacterSetCommand($charset);
    }

    /**
     * SET NAMES {'charset_name'
     *     [COLLATE 'collation_name'] | DEFAULT}
     */
    public function parseSetNames(TokenList $tokenList): SetNamesCommand
    {
        $tokenList->expectKeywords(Keyword::SET, Keyword::NAMES);
        $charset = $collation = null;
        if ($tokenList->hasKeyword(Keyword::DEFAULT) === null) {
            $charset = Charset::get($tokenList->expectNameOrString());
            if ($tokenList->hasKeyword(Keyword::COLLATE) !== null) {
                $collation = Collation::get($tokenList->expectNameOrString());
            }
        }
        $tokenList->expectEnd();

        return new SetNamesCommand($charset, $collation);
    }

}
