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
use SqlFtw\Sql\Dal\Kill\KillCommand;
use SqlFtw\Sql\Keyword;

class KillCommandParser
{
    use StrictBehaviorMixin;

    /**
     * KILL [CONNECTION | QUERY] processlist_id
     *
     * @param TokenList $tokenList
     * @return KillCommand
     */
    public function parseKill(TokenList $tokenList): KillCommand
    {
        $tokenList->consumeKeyword(Keyword::KILL);
        $tokenList->mayConsumeAnyKeyword(Keyword::CONNECTION, Keyword::QUERY);
        $id = $tokenList->consumeInt();
        $tokenList->expectEnd();

        return new KillCommand($id);
    }

}
